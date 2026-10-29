<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Expediteur;
use App\Form\ExpediteurType;
use App\Repository\ExpediteurRepository;
use App\Repository\MailRepository;
use App\Service\AdminActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/expediteur')]
class ExpediteurController extends AbstractController
{
  public function __construct(
    private readonly ExpediteurRepository $repo, 
    private readonly EntityManagerInterface $em,
    private readonly AdminActivityLogger $logger ) {}

    #[Route('', name: 'admin_sender_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/sender/index.html.twig', [
            'senders' => $this->repo->findAll([]),
        ]);
    }

    #[Route('/new', name: 'admin_sender_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new Expediteur(), $request, isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_sender_edit', methods: ['GET','POST'])]
    public function edit(Expediteur $expediteur, Request $request): Response
    {
        return $this->handleForm($expediteur, $request, isNew: false);
    }

    #[Route('/{id}/delete', name: 'admin_sender_delete', methods: ['POST'])]
    public function delete(Expediteur $expediteur, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('sender_delete_'.$expediteur->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_sender_index');
        }
        $id = $expediteur->getId();
        $this->em->remove($expediteur);
        $this->em->flush();
        $this->logger->log('mail.delete','Expéditeur', $id);
        $this->addFlash('success', 'Expéditeur supprime.');
        return $this->redirectToRoute('admin_sender_index');
    }
    
    private function handleForm(Expediteur $expediteur, Request $request, bool $isNew): Response
    {
        $form = $this->createForm(ExpediteurType::class, $expediteur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($expediteur);
            $this->em->flush();
            $this->logger->log($isNew ? 'expediteur.create' : 'expediteur.update','Expéditeur', $expediteur->getId());
            $this->addFlash('success','Expéditeur créé.');
            return $this->redirectToRoute('admin_sender_index');
        }
        return $this->render('admin/sender/form.html.twig', ['form' => $form,'expditeur' => $expediteur, 'is_new' => $isNew]);
    }
}
