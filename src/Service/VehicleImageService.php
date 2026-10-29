<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Entity\VehicleImage;
use App\Entity\VehicleImagePlus;
use App\Entity\VehiculeImagesMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class VehicleImageService
{
    public const MAX_IMAGES_PER_VEHICLE =40;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FileUploadService $uploader,
        private readonly ImageResizerService $resizer,
        private readonly string $uploadDirVehicles,
        private readonly string $uploadDirVehiclesPlus,
        private readonly string $uploadDirVehiclesMail,
        private readonly string $uploadDirContrat,
        private readonly string $uploadDirCompanyLogo,
        private readonly string $uploadDirCT
    ) {}


    public function  uploadCompanyLogo(UploadedFile $file): string
    {
        $result = $this->uploader->upload(
            $file,
            $this->uploadDirCompanyLogo,
            FileUploadService::IMAGE_MIME_TYPES,
            FileUploadService::IMAGE_MAX_SIZE,
        );
        return $result['filename'];
    }

    public function attachContrat(Reservation $reservation, UploadedFile $file){
            $result = $this->uploader->upload(
                $file,
                $this->uploadDirContrat,
                FileUploadService::PROOF_MIME_TYPES,
                FileUploadService::IMAGE_MAX_SIZE,
            );
    }

    public function attachUpload(Vehicle $vehicle, UploadedFile $file): VehicleImage
    {
        if ($vehicle->getImages()->count() >= self::MAX_IMAGES_PER_VEHICLE) {
            throw new FileException(sprintf('Maximum %d photos par véhicule.', self::MAX_IMAGES_PER_VEHICLE));
        }

        $result = $this->uploader->upload(
            $file,
            $this->uploadDirVehicles,
            FileUploadService::IMAGE_MIME_TYPES,
            FileUploadService::IMAGE_MAX_SIZE,
        );

        // Resize/crop so every catalog image has the exact same dimensions
        $this->resizer->resizeInPlace($this->uploadDirVehicles.'/'.$result['filename']);

        $image = new VehicleImage();
        $image->setFilename($result['filename'])
              ->setOriginalFilename($result['originalName'])
              ->setPosition($vehicle->getImages()->count())
              ->setPrimaryImage($vehicle->getImages()->isEmpty());

        $vehicle->addImage($image);
        $this->em->persist($image);
        $this->em->flush();

        return $image;
    }

    public function attachUploadPlus(Vehicle $vehicle, UploadedFile $file): VehicleImagePlus
    {
        if ($vehicle->getImagesPlus()->count() >= self::MAX_IMAGES_PER_VEHICLE) {
            throw new FileException(sprintf('Maximum %d photos par véhicule.', self::MAX_IMAGES_PER_VEHICLE));
        }

        $result = $this->uploader->upload(
            $file,
            $this->uploadDirVehiclesPlus,
            FileUploadService::IMAGE_MIME_TYPES,
            FileUploadService::IMAGE_MAX_SIZE,
        );

        // Resize/crop so every catalog image has the exact same dimensions
        $this->resizer->resizeInPlace($this->uploadDirVehiclesPlus.'/'.$result['filename']);

        $image = new VehicleImagePlus();
        $image->setFilename($result['filename'])
              ->setOriginalFilename($result['originalName'])
              ->setPosition($vehicle->getImages()->count())
              ->setPrimaryImage($vehicle->getImages()->isEmpty());

        $vehicle->addImagePlus($image);
        $this->em->persist($image);
        $this->em->flush();

        return $image;
    }

    public function attachUploadMail(Vehicle $vehicle, UploadedFile $file): VehiculeImagesMail
    {
        if ($vehicle->getImagesMail()->count() >= self::MAX_IMAGES_PER_VEHICLE) {
            throw new FileException(sprintf('Maximum %d photos par véhicule.', self::MAX_IMAGES_PER_VEHICLE));
        }

        $result = $this->uploader->upload(
            $file,
            $this->uploadDirVehiclesMail,
            FileUploadService::IMAGE_MIME_TYPES,
            FileUploadService::IMAGE_MAX_SIZE,
        );

        // Resize/crop so every catalog image has the exact same dimensions
        $this->resizer->resizeInPlace($this->uploadDirVehiclesMail.'/'.$result['filename']);

        $image = new VehiculeImagesMail();
        $image->setFilename($result['filename'])
              ->setOriginalFilename($result['originalName'])
              ->setPosition($vehicle->getImages()->count())
              ->setPrimaryImage($vehicle->getImages()->isEmpty());

        $vehicle->addImageMail($image);
        $this->em->persist($image);
        $this->em->flush();

        return $image;
    }

    public function attachUploadCT(Vehicle $vehicle, UploadedFile $file): bool
    {

        try{
            $result = $this->uploader->upload(
                $file,
                $this->uploadDirCT,
                FileUploadService::IMAGE_MIME_TYPES,
                FileUploadService::IMAGE_MAX_SIZE,
            );
            $vehicle->setCtFile($result['filename']);
            $this->em->persist($vehicle);
            $this->em->flush();
            return true;
        }catch(\Exception $ex){
            return false;
        }
    }

    public function attachUploadContact(UploadedFile $file): array
    {
        try{
            $result = $this->uploader->upload(
                $file,
                $this->uploadDirCT,
                FileUploadService::IMAGE_MIME_TYPES,
                FileUploadService::IMAGE_MAX_SIZE,
            );
            return $result;
        }catch(\Exception $ex){
            return [];
        }
    }


    public function remove(VehicleImage $image): void
    {
        $vehicle = $image->getVehicle();
        $wasPrimary = $image->isPrimaryImage();
        $path = $this->uploadDirVehicles.'/'.$image->getFilename();
        $this->uploader->remove($path);

        if ($vehicle !== null) {
            $vehicle->removeImage($image);
        }
        $this->em->remove($image);
        $this->em->flush();

        if ($wasPrimary && $vehicle !== null && !$vehicle->getImages()->isEmpty()) {
            $first = $vehicle->getImages()->first();
            if ($first instanceof VehicleImage) {
                $first->setPrimaryImage(true);
                $this->em->flush();
            }
        }
    }

    public function removeImagePlus(VehicleImagePlus $image): void
    {
        $vehicle = $image->getVehicle();
        $wasPrimary = $image->isPrimaryImage();
        $path = $this->uploadDirVehiclesPlus.'/'.$image->getFilename();
        $this->uploader->remove($path);

        if ($vehicle !== null) {
            $vehicle->removeImagePlus($image);
        }
        $this->em->remove($image);
        $this->em->flush();

        if ($wasPrimary && $vehicle !== null && !$vehicle->getImages()->isEmpty()) {
            $first = $vehicle->getImages()->first();
            if ($first instanceof VehicleImagePlus) {
                $first->setPrimaryImage(true);
                $this->em->flush();
            }
        }
    }

    
    public function removeImageMail(VehiculeImagesMail $image): void
    {
        $vehicle = $image->getVehicle();
        $wasPrimary = $image->isPrimaryImage();
        $path = $this->uploadDirVehiclesMail.'/'.$image->getFilename();
        $this->uploader->remove($path);

        if ($vehicle !== null) {
            $vehicle->removeImageMail($image);
        }
        $this->em->remove($image);
        $this->em->flush();

        if ($wasPrimary && $vehicle !== null && !$vehicle->getImages()->isEmpty()) {
            $first = $vehicle->getImages()->first();
            if ($first instanceof VehiculeImagesMail) {
                $first->setPrimaryImage(true);
                $this->em->flush();
            }
        }
    }

    public function setPrimary(VehicleImage $image): void
    {
        $vehicle = $image->getVehicle();
        if ($vehicle === null) return;

        foreach ($vehicle->getImages() as $img) {
            $img->setPrimaryImage($img === $image);
        }
        $this->em->flush();
    }


     public function setPrimaryPlus(VehicleImagePlus $image): void
    {
        $vehicle = $image->getVehicle();
        if ($vehicle === null) return;

        foreach ($vehicle->getImages() as $img) {
            $img->setPrimaryImage($img === $image);
        }
        $this->em->flush();
    }

     public function setPrimaryMail(VehiculeImagesMail $image): void
    {
        $vehicle = $image->getVehicle();
        if ($vehicle === null) return;

        foreach ($vehicle->getImages() as $img) {
            $img->setPrimaryImage($img === $image);
        }
        $this->em->flush();
    }
}
