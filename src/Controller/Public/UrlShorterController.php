<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Repository\UrlShorterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class UrlShorterController extends AbstractController
{
    #[Route('/u/{code}', name: 'launch_url', methods: ['GET'])]
    public function index(
        Request $request,
        RateLimiterFactory $publicFormLimiter,
        UrlShorterRepository $urlShorterRepository,
        string $code
    ): Response {
        $limiter = $publicFormLimiter->create($request->getClientIp() ?? 'anon');
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(60, 'Trop de tentatives.');
        }
        $shorter = $urlShorterRepository->findOneBy(['code' => $code]);
        if(empty($shorter)){
            return $this->redirectToRoute('home');
        }
        $url = $shorter->getUrl();
        if(!empty($url)){
            return $this->redirect($url);
        }
        return $this->redirectToRoute('home');
    }
}
