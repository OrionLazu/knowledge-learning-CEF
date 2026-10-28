<?php

namespace App\Controller\Admin;

use App\Entity\LessonAccess;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

/**
 * Consultation of the lesson access rights granted by the purchases.
 * An administrator can revoke a right (delete) but not create one by hand.
 *
 * @extends AbstractCrudController<LessonAccess>
 */
class LessonAccessCrudController extends AbstractCrudController
{
    use AuditFieldsTrait;

    public static function getEntityFqcn(): string
    {
        return LessonAccess::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Accès à une leçon')
            ->setEntityLabelInPlural('Accès aux leçons')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield AssociationField::new('user', 'Client');
        yield AssociationField::new('lesson', 'Leçon');
        yield AssociationField::new('purchase', 'Commande');
        yield DateTimeField::new('createdAt', 'Accordé le')->onlyOnIndex();
        yield from $this->auditFields();
    }
}
