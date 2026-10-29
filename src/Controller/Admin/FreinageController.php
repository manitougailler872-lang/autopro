<?php

namespace App\Controller\Admin;

use App\Entity\FreinageSystem;
use App\Form\FreinageType;
use App\Repository\FreinageRepository;
use App\Service\AdminActivityLogger;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/FreinageSystems')]
class FreinageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FreinageRepository $repo,
        private readonly SluggerInterface $slugger,
        private readonly FileUploadService $uploader,
        private readonly AdminActivityLogger $logger,
        private readonly string $kernelProjectDir,
    ) {}

    #[Route('', name: 'admin_FreinageSystem_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/FreinageSystem/index.html.twig', [
            'FreinageSystems' => $this->repo->findBy([], ['position' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_FreinageSystem_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new FreinageSystem(), $request, isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_FreinageSystem_edit', methods: ['GET','POST'])]
    public function edit(FreinageSystem $FreinageSystem, Request $request): Response
    {
        return $this->handleForm($FreinageSystem, $request, isNew: false);
    }

    #[Route('/{id}/delete', name: 'admin_FreinageSystem_delete', methods: ['POST'])]
    public function delete(FreinageSystem $FreinageSystem, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('FreinageSystem_delete_'.$FreinageSystem->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_FreinageSystem_index');
        }
        $id = $FreinageSystem->getId();
        $this->em->remove($FreinageSystem);
        $this->em->flush();
        $this->logger->log('FreinageSystem.delete', 'FreinageSystem', $id);
        $this->addFlash('success', 'FreinageSystem supprimé.');
        return $this->redirectToRoute('admin_FreinageSystem_index');
    }

    private function handleForm(FreinageSystem $FreinageSystem, Request $request, bool $isNew): Response
    {
        $form = $this->createForm(FreinageType::class, $FreinageSystem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $FreinageSystem->setSlug($this->makeSlug($FreinageSystem->getName(), $FreinageSystem->getId()));
            $this->em->persist($FreinageSystem);
            $this->em->flush();
            $this->logger->log($isNew ? 'FreinageSystem.create' : 'FreinageSystem.update', 'FreinageSystem', $FreinageSystem->getId());
            $this->addFlash('success', $isNew ? 'FreinageSystem créée.' : 'FreinageSystem enregistrée.');
            return $this->redirectToRoute('admin_FreinageSystem_index');
        }

        return $this->render('admin/FreinageSystem/form.html.twig', ['form' => $form, 'FreinageSystem' => $FreinageSystem, 'is_new' => $isNew]);
    }

    private function makeSlug(string $source, ?int $excludeId = null): string
    {
        $base = strtolower((string) $this->slugger->slug($source));
        $base = trim(preg_replace('/-+/', '-', $base) ?? $base, '-') ?: 'FreinageSystem';
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
        $qb = $this->repo->createQueryBuilder('f')->select('COUNT(f.id)')->andWhere('f.slug = :s')->setParameter('s', $slug);
        if ($excludeId !== null) $qb->andWhere('f.id <> :id')->setParameter('id', $excludeId);
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
