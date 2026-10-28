<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Management of the courses.
 *
 * @extends AbstractCrudController<Course>
 */
class CourseCrudController extends AbstractCrudController
{
    use AuditFieldsTrait;

    public static function getEntityFqcn(): string
    {
        return Course::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Cursus')
            ->setEntityLabelInPlural('Cursus')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield AssociationField::new('theme', 'Thème');
        yield MoneyField::new('price', 'Prix')->setCurrency('EUR')->setStoredAsCents();
        yield TextField::new('description', 'Description')->hideOnIndex();
        yield AssociationField::new('lessons', 'Leçons')->hideOnForm();
        yield from $this->auditFields();
    }
}
