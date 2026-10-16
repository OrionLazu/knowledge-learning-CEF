<?php

namespace App\Controller\Admin;

use App\Entity\Certification;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

/**
 * Consultation of the certifications earned by the clients (created automatically, read-only).
 *
 * @extends AbstractCrudController<Certification>
 */
class CertificationCrudController extends AbstractCrudController
{
    use AuditFieldsTrait;

    public static function getEntityFqcn(): string
    {
        return Certification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Certification')
            ->setEntityLabelInPlural('Certifications')
            ->setDefaultSort(['obtainedAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield AssociationField::new('user', 'Client');
        yield AssociationField::new('theme', 'Thème');
        yield DateTimeField::new('obtainedAt', 'Obtenue le');
        yield from $this->auditFields();
    }
}
