<?php

namespace App\Controller\Admin;

use App\Entity\Purchase;
use App\Enum\PurchaseStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

/**
 * Consultation of the orders. They are read-only: an order is created and paid by the client
 * through Stripe, an administrator must not be able to change an amount or a status by hand.
 *
 * @extends AbstractCrudController<Purchase>
 */
class PurchaseCrudController extends AbstractCrudController
{
    use AuditFieldsTrait;

    public static function getEntityFqcn(): string
    {
        return Purchase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(ChoiceFilter::new('status', 'Statut')->setChoices($this->statusChoices()));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'N°');
        yield AssociationField::new('customer', 'Client');
        yield ChoiceField::new('status', 'Statut')->setChoices($this->statusChoices());
        yield MoneyField::new('total', 'Total')->setCurrency('EUR')->setStoredAsCents();
        yield DateTimeField::new('paidAt', 'Payée le');
        yield CollectionField::new('items', 'Contenu')->onlyOnDetail();
        yield TextField::new('stripeSessionId', 'Session Stripe')->onlyOnDetail();
        yield from $this->auditFields();
    }

    /**
     * @return array<string, PurchaseStatus>
     */
    private function statusChoices(): array
    {
        $choices = [];
        foreach (PurchaseStatus::cases() as $status) {
            $choices[$status->label()] = $status;
        }

        return $choices;
    }
}
