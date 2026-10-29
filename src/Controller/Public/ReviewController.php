<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Entity\Review;
use App\Enum\ReviewStatus;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/avis', name: 'review_index', methods: ['GET','POST'])]
    public function index(
        Request $request,
        ReviewRepository $repo,
        EntityManagerInterface $em,
        RateLimiterFactory $publicFormLimiter,
    ): Response {
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $publicFormLimiter->create($request->getClientIp() ?? 'anon');
            if (!$limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException(60, 'Trop de tentatives.');
            }

            $review->setStatus(ReviewStatus::PENDING);
            $em->persist($review);
            $em->flush();

            $this->addFlash('success', 'Merci. Votre avis a bien été envoyé et sera publié après vérification.');
            return $this->redirectToRoute('review_index');
        }

        return $this->render('review/index.html.twig', [
            'reviews' => $repo->findPublished(30),
            'form'    => $form,
        ]);
    }
}
