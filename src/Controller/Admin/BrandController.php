<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Form\BrandType;
use App\Repository\BrandRepository;
use App\Service\AdminActivityLogger;
use App\Service\FileUploadService;
use App\Service\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/brands')]
class BrandController extends AbstractController
{
  public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BrandRepository $repo,
        private readonly SluggerInterface $slugger,
        private readonly FileUploadService $uploader,
        private readonly AdminActivityLogger $logger,
        private readonly string $kernelProjectDir,
    ) {}

    #[Route('', name: 'admin_brand_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/brand/index.html.twig', [
            'brands' => $this->repo->findBy([], ['position' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_brand_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new Brand(), $request, isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_brand_edit', methods: ['GET','POST'])]
    public function edit(Brand $brand, Request $request): Response
    {
        return $this->handleForm($brand, $request, isNew: false);
    }

    #[Route('/{id}/delete', name: 'admin_brand_delete', methods: ['POST'])]
    public function delete(Brand $brand, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('brand_delete_'.$brand->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_brand_index');
        }
        $id = $brand->getId();
        $this->em->remove($brand);
        $this->em->flush();
        $this->logger->log('brand.delete', 'Brand', $id);
        $this->addFlash('success', 'Marque supprimée.');
        return $this->redirectToRoute('admin_brand_index');
    }

    private function handleForm(Brand $brand, Request $request, bool $isNew): Response
    {
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
          $brand->setSlug($this->makeSlug($brand->getName(), $brand->getId()));

            /** @var UploadedFile|null $logo */
            $logo = $form->get('logo')->getData();
            if ($logo instanceof UploadedFile) {
                try {
                    $result = $this->uploader->upload(
                        $logo,
                        $this->kernelProjectDir.'/public/uploads/brands',
                        FileUploadService::IMAGE_MIME_TYPES,
                        FileUploadService::IMAGE_MAX_SIZE,
                    );
                    $brand->setLogoFilename($result['filename']);
                } catch (\Throwable $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->render('admin/brand/form.html.twig', ['form' => $form, 'brand' => $brand, 'is_new' => $isNew]);
                }
            }

            $this->em->persist($brand);
            $this->em->flush();
            $this->logger->log($isNew ? 'brand.create' : 'brand.update', 'Brand', $brand->getId());
            $this->addFlash('success', $isNew ? 'Marque créée.' : 'Marque enregistrée.');
            return $this->redirectToRoute('admin_brand_index');
        }

        return $this->render('admin/brand/form.html.twig', ['form' => $form, 'brand' => $brand, 'is_new' => $isNew]);
    }

    private function makeSlug(string $source, ?int $excludeId = null): string
    {
        $base = strtolower((string) $this->slugger->slug($source));
        $base = trim(preg_replace('/-+/', '-', $base) ?? $base, '-') ?: 'brand';
        $candidate = $base;
        $i = 2;
        while ($this->slugExists($candidate, $excludeId)) {
            $candidate = $base.'-'.$i++;
            if ($i > 500) { $candidate = $base.'-'.bin2hex(random_bytes(3)); break; }
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
