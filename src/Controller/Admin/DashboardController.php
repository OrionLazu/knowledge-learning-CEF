<?php

namespace App\Controller\Admin;

use App\Enum\PurchaseStatus;
use App\Repository\CertificationRepository;
use App\Repository\PurchaseRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Back office, reserved to administrators: users, catalogue contents, purchases and certifications.
 *
 * The /admin path is protected twice: by the access_control rule of security.yaml
 * and by the IsGranted attribute below.
 */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PurchaseRepository $purchaseRepository,
        private readonly CertificationRepository $certificationRepository,
    ) {
    }

    /**
     * Home page of the back office with a few figures.
     */
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $this->userRepository->count([]),
            'paidPurchaseCount' => $this->purchaseRepository->count(['status' => PurchaseStatus::Paid]),
            'certificationCount' => $this->certificationRepository->count([]),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Knowledge Learning - Administration')
            ->setFaviconPath('images/favicon.png')
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');

        yield MenuItem::section('Comptes');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');

        yield MenuItem::section('Contenus');
        yield MenuItem::linkTo(ThemeCrudController::class, 'Thèmes', 'fa fa-folder');
        yield MenuItem::linkTo(CourseCrudController::class, 'Cursus', 'fa fa-graduation-cap');
        yield MenuItem::linkTo(LessonCrudController::class, 'Leçons', 'fa fa-book-open');

        yield MenuItem::section('Achats');
        yield MenuItem::linkTo(PurchaseCrudController::class, 'Commandes', 'fa fa-cart-shopping');
        yield MenuItem::linkTo(LessonAccessCrudController::class, 'Accès aux leçons', 'fa fa-key');
        yield MenuItem::linkTo(CertificationCrudController::class, 'Certifications', 'fa fa-award');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'app_home');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-right-from-bracket');
    }
}
