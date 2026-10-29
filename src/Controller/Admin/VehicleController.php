<?php

declare(strict_types=1);
namespace App\Controller\Admin;

use App\Entity\Vehicle;
use App\Entity\VehicleImage;
use App\Form\VehicleType;
use App\Enum\VehicleTypeEnum;
use App\Repository\VehicleImageMailRepository;
use App\Repository\VehicleImagePlusRepository;
use App\Repository\VehicleImageRepository;
use App\Repository\VehicleRepository;
use App\Service\AdminActivityLogger;
use App\Service\SlugService;
use App\Service\VehicleImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/vehicles')]
class VehicleController extends AbstractController
{

    #[Route('', name: 'admin_vehicle_index', methods: ['GET'])]
    public function index(VehicleRepository $repo,EntityManagerInterface $entityManager): Response
    {
       /* $vehicles = $repo->findAll();
        foreach ($vehicles as $vehicle) {
            $vehicle->setVehicleType(VehicleTypeEnum::CAR->value);
            $entityManager->persist($vehicle);
            $entityManager->flush();
        }*/
        return $this->render('admin/vehicle/index.html.twig', [
            'vehicles' => $repo->findBy([
                'vehicleType'  => VehicleTypeEnum::CAR->value
            ], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/pieces', name: 'admin_piece_index', methods: ['GET'])]
    public function pieceList(VehicleRepository $vehicleRepository):Response
    {
        return $this->render('admin/vehicle/pieces.html.twig', [
            'pieces' => $vehicleRepository->findBy([
                'vehicleType'  => VehicleTypeEnum::PIECE->value,
            ], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new/piece', name: 'admin_piece_new', methods: ['GET','POST'])]
    public function newPiece(
        Request $request,
        EntityManagerInterface $em,
        SlugService $slugger,
        AdminActivityLogger $logger,
    ): Response {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle,[
            'vehicleType' => VehicleTypeEnum::PIECE->value,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicle->setSlug($slugger->makeUniqueVehicleSlug($vehicle->getFullName()));
            $em->persist($vehicle);
            $em->flush();

            $logger->log('vehicle.create', 'Vehicle', $vehicle->getId());
            $this->addFlash('success', 'Pièce ajoutée.');
            return $this->redirectToRoute('admin_piece_edit', ['id' => $vehicle->getId()]);
        }

        return $this->render('admin/vehicle/form_piece.html.twig', ['form' => $form, 'vehicle' => $vehicle, 'is_new' => true]);
    }

    #[Route('/{id}/edit/piece', name: 'admin_piece_edit', methods: ['GET','POST'])]
    public function editPiece(
        string $id,
        Request $request,
        EntityManagerInterface $em,
        SlugService $slugger,
        VehicleImageService $imageService,
        AdminActivityLogger $logger,
    ): Response {
        $vehicle  = $em->getRepository(Vehicle::class)->find($id);
        if(empty($vehicle)){
            return $this->redirectToRoute('admin_piece_index');
        }
        $form = $this->createForm(VehicleType::class, $vehicle,[
            'vehicleType' => VehicleTypeEnum::PIECE->value,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicle->setSlug($slugger->makeUniqueVehicleSlug($vehicle->getFullName(), $vehicle->getId()));

            // Handle image uploads
            /** @var UploadedFile[] $files */
            $files = $request->files->get('images', []);
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    try {
                        $imageService->attachUpload($vehicle, $file);
                    } catch (\Throwable $e) {
                        $this->addFlash('error', $e->getMessage());
                    }
                }
            }

            $em->flush();
            $logger->log('vehicle.update', 'Pièce', $vehicle->getId());
            $this->addFlash('success', 'Pièce enregistrée.');
            return $this->redirectToRoute('admin_piece_edit', ['id' => $vehicle->getId()]);
        }

        return $this->render('admin/vehicle/form.html.twig', [
            'form'    => $form,
            'vehicle' => $vehicle,
            'is_new'  => false,
        ]);
    }

    #[Route('/{id}/toggle-publish-piece', name: 'admin_piece_toggle_publish', methods: ['POST'])]
    public function togglePublishPiece(Vehicle $vehicle, Request $request, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('vehicle_publish_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_piece_index');
        }
        $vehicle->setPublished(!$vehicle->isPublished());
        $em->flush();
        $logger->log('vehicle.toggle_publish', 'Pièce', $vehicle->getId(), ['published' => $vehicle->isPublished()]);
        $this->addFlash('success', $vehicle->isPublished() ? 'Pièce publiée.' : 'Pièce dépubliée.');
        return $this->redirectToRoute('admin_piece_index');
    }

    #[Route('/{id}/delete/piece', name: 'admin_piece_delete', methods: ['POST'])]
    public function deletePièce(Vehicle $vehicle, Request $request, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('vehicle_delete_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_piece_index');
        }
        $id = $vehicle->getId();
        $em->remove($vehicle);
        $em->flush();
        $logger->log('vehicle.delete', 'Pièce', $id);
        $this->addFlash('success', 'Pièce supprimée.');
        return $this->redirectToRoute('admin_piece_index');
    }

    #[Route('/images/{id}/primary/piece', name: 'admin_piece_image_primary', methods: ['POST'])]
    public function makePrimaryPièce(VehicleImage $image, Request $request, VehicleImageService $service): Response
    {
        if (!$this->isCsrfTokenValid('image_primary_'.$image->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        } else {
            $service->setPrimary($image);
            $this->addFlash('success', 'Photo principale mise à jour.');
        }
        return $this->redirectToRoute('admin_piece_edit', ['id' => $image->getVehicle()?->getId()]);
    }

    #[Route('/images/{id}/delete/piece', name: 'admin_piece_image_delete', methods: ['POST'])]
    public function deleteImagePiece(VehicleImage $image, Request $request, VehicleImageService $service): Response
    {
        $vehicleId = $image->getVehicle()?->getId();
        if (!$this->isCsrfTokenValid('image_delete_'.$image->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        } else {
            $service->remove($image);
            $this->addFlash('success', 'Photo supprimée.');
        }
        return $this->redirectToRoute('admin_piece_edit', ['id' => $vehicleId]);
    }


    #[Route('/new', name: 'admin_vehicle_new', methods: ['GET','POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SlugService $slugger,
        AdminActivityLogger $logger,
    ): Response {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle,[
            'vehicleType' => VehicleTypeEnum::CAR->value,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicle->setSlug($slugger->makeUniqueVehicleSlug($vehicle->getFullName()));
            $em->persist($vehicle);
            $em->flush();

            $logger->log('vehicle.create', 'Vehicle', $vehicle->getId());
            $this->addFlash('success', 'Véhicule ajouté.');
            return $this->redirectToRoute('admin_vehicle_edit', ['id' => $vehicle->getId()]);
        }

        return $this->render('admin/vehicle/form.html.twig', ['form' => $form, 'vehicle' => $vehicle, 'is_new' => true]);
    }

    #[Route('/{id}/edit', name: 'admin_vehicle_edit', methods: ['GET','POST'])]
    public function edit(
        string $id,
        Request $request,
        EntityManagerInterface $em,
        SlugService $slugger,
        VehicleImageService $imageService,
        AdminActivityLogger $logger,
    ): Response {
        $vehicle  = $em->getRepository(Vehicle::class)->find($id);
        if(empty($vehicle)){
            return $this->redirectToRoute('admin_vehicle_index');
        }
        $form = $this->createForm(VehicleType::class, $vehicle,[
            'vehicleType' => VehicleTypeEnum::CAR->value,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicle->setSlug($slugger->makeUniqueVehicleSlug($vehicle->getFullName(), $vehicle->getId()));

            // Handle image uploads
            /** @var UploadedFile[] $files */
            $files = $request->files->get('images', []);
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    try {
                        $imageService->attachUpload($vehicle, $file);
                    } catch (\Throwable $e) {
                        $this->addFlash('error', $e->getMessage());
                    }
                }
            }

             // Handle image uploads
            /** @var UploadedFile[] $files */
            $files = $request->files->get('imagesPlus', []);
           // dd($files);
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    try {
                        $imageService->attachUploadPlus($vehicle, $file);
                    } catch (\Throwable $e) {
                        dd($e);
                        $this->addFlash('error', $e->getMessage());
                    }
                }
            }

              // Handle image uploads
            /** @var UploadedFile[] $files */
            $files = $request->files->get('imagesMail', []);
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    try {
                        $imageService->attachUploadMail($vehicle, $file);
                    } catch (\Throwable $e) {
                        $this->addFlash('error', $e->getMessage());
                    }
                }
            }

            // Handle image uploads
            /** @var UploadedFile[] $files */
            $ctFile = $form->get('ctFile')->getData();
            if ($ctFile instanceof UploadedFile) {
                try {
                    $imageService->attachUploadCT($vehicle, $ctFile);
                } catch (\Throwable $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }
            $em->flush();
            $logger->log('vehicle.update', 'Vehicle', $vehicle->getId());
            $this->addFlash('success', 'Véhicule enregistré.');
            return $this->redirectToRoute('admin_vehicle_edit', ['id' => $vehicle->getId()]);
        }

        return $this->render('admin/vehicle/form.html.twig', [
            'form'    => $form,
            'vehicle' => $vehicle,
            'is_new'  => false,
        ]);
    }

    #[Route('/{id}/toggle-publish', name: 'admin_vehicle_toggle_publish', methods: ['POST'])]
    public function togglePublish(Vehicle $vehicle, Request $request, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('vehicle_publish_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_vehicle_index');
        }
        $vehicle->setPublished(!$vehicle->isPublished());
        $em->flush();
        $logger->log('vehicle.toggle_publish', 'Vehicle', $vehicle->getId(), ['published' => $vehicle->isPublished()]);
        $this->addFlash('success', $vehicle->isPublished() ? 'Véhicule publié.' : 'Véhicule dépublié.');
        return $this->redirectToRoute('admin_vehicle_index');
    }

    #[Route('/{id}/delete', name: 'admin_vehicle_delete', methods: ['POST'])]
    public function delete(Vehicle $vehicle, Request $request, EntityManagerInterface $em, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('vehicle_delete_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_vehicle_index');
        }
        $id = $vehicle->getId();
        $em->remove($vehicle);
        $em->flush();
        $logger->log('vehicle.delete', 'Vehicle', $id);
        $this->addFlash('success', 'Véhicule supprimé.');
        return $this->redirectToRoute('admin_vehicle_index');
    }

    #[Route('/images/{id}/primary/{which}', name: 'admin_vehicle_image_primary', methods: ['POST'])]
    public function makePrimary(int $id, Request $request, VehicleImageService $service,
    ?string $which, VehicleImageRepository $vehicleImageRepository,
    VehicleImagePlusRepository $vehicleImagePlusRepository, VehicleImageMailRepository $vehicleImageMailRepository): Response
    {
        $image = null;
        if (!$this->isCsrfTokenValid('image_primary_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        } 
        switch($which){
            case 'default':
                $image = $vehicleImageRepository->findOneBy(['id' => $id]);
                if(!empty($image)){
                    $service->setPrimary($image);
                    $this->addFlash('success', 'Photo principale mise à jour.');
                }
                break;
            case 'plus':
                $image = $vehicleImagePlusRepository->findOneBy(['id' => $id]);
                if(!empty($image)){
                    $service->setPrimaryPlus($image);
                    $this->addFlash('success', 'Photo plus mise à jour.');
                }
                break;
            case 'mail':
                $image = $vehicleImageMailRepository->findOneBy(['id' => $id]);
                if(!empty($image)){
                    $service->setPrimaryMail($image);
                    $this->addFlash('success', 'Photo mail mise à jour.');
                }
                break;   
        }

        return $this->redirectToRoute('admin_vehicle_edit', ['id' => $image->getVehicle()?->getId()]);
    }

    #[Route('/images/{id}/delete/{which}', name: 'admin_vehicle_image_delete', methods: ['POST'])]
    public function deleteImage(int $id, Request $request, VehicleImageService $service,
    ?string $which,VehicleImageRepository $vehicleImageRepository,
    VehicleImagePlusRepository $vehicleImagePlusRepository, VehicleImageMailRepository $vehicleImageMailRepository): Response
    {
        $image = $vehicleId = null;
          
        if (!$this->isCsrfTokenValid('image_delete_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        } 
        switch($which){
            case 'default':
                $image = $vehicleImageRepository->findOneBy(['id' => $id]);
                if(!empty($image)){
                    $vehicleId = $image->getVehicle()?->getId();
                    $service->remove($image);
                    $this->addFlash('success', 'Photo supprimée.');
                }
                break;
            case 'plus':
                $image = $vehicleImagePlusRepository->findOneBy(['id' => $id]);
                if(!empty($image)){
                    $vehicleId = $image->getVehicle()?->getId();
                    $service->removeImagePlus($image);
                    $this->addFlash('success', 'Photo plus supprimée.');
                }
                break;
            case 'mail':
                $image = $vehicleImageMailRepository->findOneBy(['id' => $id]);
                if(!empty($image)){
                    $vehicleId = $image->getVehicle()?->getId();
                    $service->removeImageMail($image);
                    $this->addFlash('success', 'Photo mail supprimée.');
                }
                break;   
        }     
        return $this->redirectToRoute('admin_vehicle_edit', ['id' => $vehicleId]);
    }
}
