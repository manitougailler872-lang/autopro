<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\LbcLink;
use App\Form\LbcLinkType;
use App\Repository\LbcLinkRepository;
use App\Repository\ReservationRepository;
use App\Service\AdminActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/lbc')]
class LbcLinkController extends AbstractController
{
   public function __construct(
    private readonly LbcLinkRepository $repo, 
    private readonly EntityManagerInterface $em,
    private readonly ReservationRepository $reservationRepository,
    private readonly AdminActivityLogger $logger ) {}

    #[Route('', name: 'admin_lbc_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/lbc/index.html.twig', [
            'links' => $this->repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_lbc_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new LbcLink(), $request, isNew: true);
    }

    #[Route('/{id}/delete', name: 'admin_lbc_delete', methods: ['POST'])]
    public function delete(LbcLink $link, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('lbc_delete_'.$link->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_lbc_index');
        }
        $id = $link->getId();
        $this->em->remove($link);
        $this->em->flush();
        $this->logger->log('lbc.delete', 'Link', $id);
        $this->addFlash('success', 'Lbc link supprime.');
        return $this->redirectToRoute('admin_lbc_index');
    }


    private function handleForm(LbcLink $link, Request $request, bool $isNew,
    ): Response
    {
        $form = $this->createForm(LbcLinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $reservationNumber = $form->get('reservationNumber')->getData();
            if(empty($reservationNumber)){
                $form->addError(new FormError('La référence de la réservation est obligatoire'));
                return $this->render('admin/lbc/form.html.twig', ['form' => $form, 'link' => $link, 'is_new' => true]);
            }
            $reservation = $this->reservationRepository->findOneBy(['reservationNumber' => $reservationNumber]);
            if(empty($reservation)){
                $form->addError(new FormError('La réservation n\'existe pas'));
                return $this->render('admin/lbc/form.html.twig', ['form' => $form, 'link' => $link, 'is_new' => true]);
            }
            try{
                $data = $form->getData();
                $data->setReservation($reservation);
                $this->em->persist($data);
                $this->em->flush();
                $this->logger->log($isNew ? 'lbc.create' : 'lbc.update', 'Link', $link->getId());
                $this->addFlash('success','Link créé.');
            }catch(\Exception $e){
                $this->addFlash('error','Lien non créé.');
                return $this->render('admin/lbc/form.html.twig', ['form' => $form, 'link' => $link, 'is_new' => true]);
            }
            return $this->redirectToRoute('admin_lbc_index');
        }
        return $this->render('admin/lbc/form.html.twig', ['form' => $form, 'link' => $link, 'is_new' => true]);
    }
}
