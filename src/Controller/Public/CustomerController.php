<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

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
class CustomerController extends AbstractController
{
    #[Route('/mes-reservations/{uuid}', name: 'customer_reservations', methods: ['GET'])]
    public function reservations(string $uuid, ReservationRepository $repo): Response
    {
        $current = $repo->findOneByUuid($uuid);
        if ($current === null) {
            throw new NotFoundHttpException('Réservation introuvable.');
        }

        $reservations = $repo->findBy(
            ['customerEmail' => $current->getCustomerEmail()],
            ['createdAt' => 'DESC'],
        );

        return $this->render('customer/reservations.html.twig', [
            'current'      => $current,
            'reservations' => $reservations,
        ]);
    }
}
