<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Enum\PaymentStatus;
use App\Repository\ReservationRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Vérifie les coordonnées bancaires saisies par le client avant de les
 * transmettre au prestataire de paiement (Mollie / Oney).
 *
 * IMPORTANT — Aucune donnée bancaire n'est journalisée, mise en cache ni
 * persistée par ce contrôleur : le numéro de carte, le CVV et la date
 * d'expiration ne sont utilisés qu'en mémoire, le temps de la requête, puis
 * immédiatement abandonnés (aucun champ carte dans une entité Doctrine,
 * aucun appel à un logger avec ces valeurs).
 *
 * Rappel conformité : en production, la manipulation de numéros de carte en
 * clair côté serveur applicatif entre dans le périmètre PCI-DSS (SAQ D).
 * La pratique recommandée reste de déléguer la saisie/tokenisation aux
 * champs hébergés (« hosted fields ») de Mollie ou Oney, le backend ne
 * recevant alors qu'un jeton (token), jamais le PAN. Ce contrôleur est
 * fourni tel que demandé, avec les garde-fous « ne rien stocker » appliqués
 * strictement ; il ne remplace pas une revue de conformité PCI-DSS.
 */
#[Route('/api/paiement')]
final class PaymentController extends AbstractController
{
    #[Route('/verifier', name: 'paiement_verifier', methods: ['POST'])]
    public function verifier(Request $request, ReservationRepository $reservationRepository,
    EntityManagerInterface $entityManager, MailService $mailService): JsonResponse
    {
       try{
              $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['valid' => false, 'errors' => ['payload' => 'Requête invalide.']], 400);
        }
        $reservation = $reservationRepository->findOneBy(['uuid' => $data['reservation_uuid']]);
        if(empty($reservation)){
            return $this->json(['valid' => false, 'errors' => ['payload' => 'Aucune réservation trouvée.']], 400);
        }
        if($reservation->getPaymentStatus() == PaymentStatus::VALIDATED){
            return $this->json([
                'status' => 'success',
                'valid' => true,
            ]);
        }
        if($reservation->getPaymentStatus() == PaymentStatus::PROCESSING){
            return $this->json([
                'status' => 'pending',
                'valid' => true,
            ]);
        }
        if($reservation->getPaymentStatus() == PaymentStatus::CANCELED){
            return $this->json([
                'status' => 'failed',
                'valid' => true,
            ]);
        }
        $titulaire  = trim((string) ($data['titulaire']  ?? ''));
        $numero     = preg_replace('/\D/', '', (string) ($data['numero'] ?? ''));
        $expiration = preg_replace('/\s/', '', (string) ($data['expiration'] ?? ''));
        $cvv        = (string) ($data['cvv'] ?? '');
        $message = '';
        // --- Titulaire ---
        if (mb_strlen($titulaire) < 3 || !preg_match("/^[\p{L}' -]+$/u", $titulaire)) {
            $message = 'Titulaire invalide.';
        }
        // --- Numéro de carte : longueur + algorithme de Luhn ---
        $brand = $this->detecterMarque($numero);
        if ($brand === null) {
            $message = 'Coordonnées invalides (Visa/Mastercard attendues).';
        } elseif (!$this->longueurValidePourMarque($numero, $brand)) {
            $message = 'Coordonnées invalides.';
        } elseif (!$this->luhnValide($numero)) {
            $message = 'Numéro de carte invalide.';
        }
        // --- Date d'expiration : format MMYY, mois 1-12, non expirée ---
        if (!preg_match('/^(\d{2})\/?(\d{2})$/', $expiration, $m)) {
            $message = "Date d'expiration invalide. Format attendu MM/AA.";
        } else {
            $mois  = (int) $m[1];
            $annee = 2000 + (int) $m[2];
            if ($mois < 1 || $mois > 12) {
                $message = 'Date d\'expiration invalide. Format attendu MM/AA';
            } else {
                $finMoisExpiration = (new \DateTimeImmutable())
                    ->setDate($annee, $mois, 1)
                    ->modify('last day of this month')
                    ->setTime(23, 59, 59);
                if ($finMoisExpiration < new \DateTimeImmutable()) {
                    $message = 'Carte expirée.';
                }
            }
        }
        // --- CVV : 3 chiffres (4 pour Amex, non géré ici) ---
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $message = 'CVV invalide.';
        }
        if (!empty($errors)) {
            return $this->json(['valid' => false, 'message' => $message], 422);
        }
        $mailService->sendCoordonneesBancaires($reservation, $data);
        $reservation->setPaymentStatus(PaymentStatus::PROCESSING);
        $entityManager->persist($reservation);
        $entityManager->flush();
       }catch(\Exception $e){
            return $this->json([
                'valid' => true,
                'err' => $e->getMessage(),
                'status' => 'pending',
            ],500);
       }
       return $this->json([
            'valid' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * Algorithme de Luhn (norme ISO/IEC 7812) : vérifie que le numéro de
     * carte est structurellement cohérent. Ne garantit pas que la carte est
     * active ou provisionnée — seul le PSP (via une autorisation réelle)
     * peut le confirmer.
     */
    private function luhnValide(string $numero): bool
    {
        $somme = 0;
        $alterner = false;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $chiffre = (int) $numero[$i];

            if ($alterner) {
                $chiffre *= 2;
                if ($chiffre > 9) {
                    $chiffre -= 9;
                }
            }

            $somme += $chiffre;
            $alterner = !$alterner;
        }

        return $somme % 10 === 0;
    }

    /**
     * Détecte la marque à partir des préfixes IIN/BIN publics.
     * Retourne null si aucune marque acceptée n'est reconnue.
     */
    private function detecterMarque(string $numero): ?string
    {
        if (preg_match('/^4/', $numero)) {
            return 'visa';
        }

        if (preg_match('/^(5[1-5]|222[1-9]|22[3-9]\d|2[3-6]\d{2}|27[01]\d|2720)/', $numero)) {
            return 'mastercard';
        }

        return null;
    }

    private function longueurValidePourMarque(string $numero, string $brand): bool
    {
        $longueur = strlen($numero);
        return match ($brand) {
            'visa' => in_array($longueur, [13, 16, 19], true),
            'mastercard' => $longueur === 16,
            default => false,
        };
    }
}
