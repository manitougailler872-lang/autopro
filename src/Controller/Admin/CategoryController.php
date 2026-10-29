<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\AdminActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/categories')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryRepository $repo,
        private readonly SluggerInterface $slugger,
        private readonly AdminActivityLogger $logger,
    ) {}

    #[Route('', name: 'admin_category_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $this->repo->findBy([], ['position' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new Category(), $request, isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET','POST'])]
    public function edit(Category $c, Request $request): Response
    {
        return $this->handleForm($c, $request, isNew: false);
    }

    #[Route('/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(Category $c, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('category_delete_'.$c->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_category_index');
        }
        $id = $c->getId();
        $this->em->remove($c);
        $this->em->flush();
        $this->logger->log('category.delete', 'Category', $id);
        $this->addFlash('success', 'Catégorie supprimée.');
        return $this->redirectToRoute('admin_category_index');
    }

    private function handleForm(Category $c, Request $request, bool $isNew): Response
    {
        $form = $this->createForm(CategoryType::class, $c);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $c->setSlug($this->makeSlug($c->getName(), $c->getId()));
            $this->em->persist($c);
            $this->em->flush();
            $this->logger->log($isNew ? 'category.create' : 'category.update', 'Category', $c->getId());
            $this->addFlash('success', $isNew ? 'Catégorie créée.' : 'Catégorie enregistrée.');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', ['form' => $form, 'category' => $c, 'is_new' => $isNew]);
    }

    private function makeSlug(string $source, ?int $excludeId): string
    {
        $base = strtolower((string) $this->slugger->slug($source));
        $base = trim(preg_replace('/-+/', '-', $base) ?? $base, '-') ?: 'category';
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
        $qb = $this->repo->createQueryBuilder('c')->select('COUNT(c.id)')->andWhere('c.slug = :s')->setParameter('s', $slug);
        if ($excludeId !== null) $qb->andWhere('c.id <> :id')->setParameter('id', $excludeId);
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
