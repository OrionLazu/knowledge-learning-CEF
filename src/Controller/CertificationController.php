<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CertificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Summary of the certifications earned by the logged-in user.
 */
class CertificationController extends AbstractController
{
    #[Route('/certifications', name: 'app_certifications', methods: ['GET'])]
    public function index(CertificationRepository $certificationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('certification/index.html.twig', [
            'certifications' => $certificationRepository->findForUser($user),
        ]);
    }
}
