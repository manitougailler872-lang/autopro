<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Enum\ReviewStatus;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Service\AdminActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/reviews')]
class ReviewController extends AbstractController
{
    #[Route('', name: 'admin_review_index', methods: ['GET','POST'])]
    public function index(Request $request, ReviewRepository $repo, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        // Allow admin to add a published review directly from this page
        $newReview = new Review();
        $form = $this->createForm(ReviewType::class, $newReview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newReview->setStatus(ReviewStatus::PUBLISHED)
                      ->setPublishedAt(new \DateTimeImmutable());
            $em->persist($newReview);
            $em->flush();
            $logger->log('review.create_by_admin', 'Review', $newReview->getId());
            $this->addFlash('success', 'Avis publié.');
            return $this->redirectToRoute('admin_review_index');
        }

        return $this->render('admin/review/index.html.twig', [
            'reviews' => $repo->findBy([], ['createdAt' => 'DESC']),
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/status', name: 'admin_review_status', methods: ['POST'])]
    public function changeStatus(Review $review, Request $request, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('review_status_'.$review->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_review_index');
        }
        $status = ReviewStatus::tryFrom((string) $request->request->get('status', ''));
        if ($status === null) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_review_index');
        }
        $review->setStatus($status);
        if ($status === ReviewStatus::PUBLISHED && $review->getPublishedAt() === null) {
            $review->setPublishedAt(new \DateTimeImmutable());
        }
        $em->flush();
        $logger->log('review.status_change', 'Review', $review->getId(), ['status' => $status->value]);
        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('admin_review_index');
    }

    #[Route('/{id}/edit-comment', name: 'admin_review_edit_comment', methods: ['POST'])]
    public function editComment(Review $review, Request $request, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('review_edit_'.$review->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_review_index');
        }
        $review->setComment((string) $request->request->get('comment', $review->getComment()));
        $em->flush();
        $logger->log('review.edit', 'Review', $review->getId());
        $this->addFlash('success', 'Avis modifié.');
        return $this->redirectToRoute('admin_review_index');
    }

    #[Route('/{id}/delete', name: 'admin_review_delete', methods: ['POST'])]
    public function delete(Review $review, Request $request, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('review_delete_'.$review->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_review_index');
        }
        $id = $review->getId();
        $em->remove($review);
        $em->flush();
        $logger->log('review.delete', 'Review', $id);
        $this->addFlash('success', 'Avis supprimé.');
        return $this->redirectToRoute('admin_review_index');
    }
}
