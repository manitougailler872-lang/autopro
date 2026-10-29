<?php

declare(strict_types=1);

namespace App\Controller\Public;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    #[Route('/blogs', name: 'blogs', methods: ['GET','POST'])]
    public function request(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        return $this->render('blog/index.html.twig', [
            'blogs'=> []
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_detail', methods: ['GET','POST'])]
    public function blogDetail(
        Request $request,
        EntityManagerInterface $em,
        ?string $slug
    ): Response {

        return $this->render('blog/blog_detail.html.twig', []);
    }
}
