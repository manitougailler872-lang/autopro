<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\SettingsType;
use App\Service\AdminActivityLogger;
use App\Service\SettingService;
use App\Service\VehicleImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SettingController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET','POST'])]
    public function edit(Request $request, SettingService $settings,
                         AdminActivityLogger $logger,
                         VehicleImageService $imageService): Response
    {
        $current = $settings->all();
        $form = $this->createForm(SettingsType::class, $current);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $values = $form->getData();
            // Cast to string|null for storage
            $normalized = [];
            foreach ($values as $k => $v) {
                if($k == SettingService::COMPANY_LOGO) {
                    continue;
                }
                $normalized[$k] = $v === null ? null : (string) $v;
            }
            $companyLogo = $form->get('company_logo')->getData();
            if(!empty($companyLogo) && $companyLogo instanceof UploadedFile){
               $logo =  $imageService->uploadCompanyLogo($companyLogo);
               $normalized[SettingService::COMPANY_LOGO] = $logo;
            }
            $settings->setMany($normalized);
            $logger->log('settings.update');
            $this->addFlash('success', 'Paramètres enregistrés.');
            return $this->redirectToRoute('admin_settings');
        }
        return $this->render('admin/settings/edit.html.twig', ['form' => $form]);
    }
}
