<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Blog;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use App\Service\AdminActivityLogger;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/blogs')]
class BlogController extends AbstractController
{
  public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BlogRepository $repo,
        private readonly SluggerInterface $slugger,
        private readonly FileUploadService $uploader,
        private readonly AdminActivityLogger $logger,
        private readonly string $kernelProjectDir,
    ) {}

    #[Route('', name: 'admin_blog_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/blog/index.html.twig', [
            'blogs' => $this->repo->findBy([], []),
        ]);
    }

    #[Route('/new', name: 'admin_blog_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new Blog(), $request, isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_blog_edit', methods: ['GET','POST'])]
    public function edit(Blog $blog, Request $request): Response
    {
        return $this->handleForm($blog, $request, isNew: false);
    }

    #[Route('/{id}/delete', name: 'admin_blog_delete', methods: ['POST'])]
    public function delete(Blog $blog, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('blog_delete_'.$blog->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_blog_index');
        }
        $id = $blog->getId();
        $this->em->remove($blog);
        $this->em->flush();
        $this->logger->log('blog.delete', 'Blog', $id);
        $this->addFlash('success', 'Blog supprimée.');
        return $this->redirectToRoute('admin_blog_index');
    }

    private function handleForm(Blog $blog, Request $request, bool $isNew): Response
    {
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
          $blog->setSlug($this->makeSlug($blog->getName(), $blog->getId()));

            /** @var UploadedFile|null $logo */
            $logo = $form->get('frontImage')->getData();
            if ($logo instanceof UploadedFile) {
                try {
                    $result = $this->uploader->upload(
                        $logo,
                        $this->kernelProjectDir.'/public/uploads/blogs',
                        FileUploadService::IMAGE_MIME_TYPES,
                        FileUploadService::IMAGE_MAX_SIZE,
                    );
                    $blog->setFrontImage  ($result['filename']);
                } catch (\Throwable $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->render('admin/blog/form.html.twig', ['form' => $form, 'blog' => $blog, 'is_new' => $isNew]);
                }
            }

            $this->em->persist($blog);
            $this->em->flush();
            $this->logger->log($isNew ? 'blog.create' : 'blog.update', 'Blog', $blog->getId());
            $this->addFlash('success', $isNew ? 'Blog créée.' : 'Blog enregistrée.');
            return $this->redirectToRoute('admin_blog_index');
        }

        return $this->render('admin/blog/form.html.twig', ['form' => $form, 'blog' => $blog, 'is_new' => $isNew]);
    }

    private function makeSlug(string $source, ?int $excludeId = null): string
    {
        $base = strtolower((string) $this->slugger->slug($source));
        $base = trim(preg_replace('/-+/', '-', $base) ?? $base, '-') ?: 'blog';
        $candidate = $base;
        $i = 2;
        while ($this->slugExists($candidate, $excludeId)) {
            $candidate = $base.'-'.$i++;
            if ($i > 500) { 
                $candidate = $base.'-'.bin2hex(random_bytes(3));
                break;
            }
        }
        return $candidate;
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $qb = $this->repo->createQueryBuilder('b')->select('COUNT(b.id)')->andWhere('b.slug = :s')->setParameter('s', $slug);
        if ($excludeId !== null) $qb->andWhere('b.id <> :id')->setParameter('id', $excludeId);
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
