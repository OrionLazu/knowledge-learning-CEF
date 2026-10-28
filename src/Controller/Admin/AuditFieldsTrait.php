<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

/**
 * Read-only display of the four audit columns in the detail page of every back-office screen.
 */
trait AuditFieldsTrait
{
    /**
     * @return iterable<\EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface>
     */
    private function auditFields(): iterable
    {
        yield DateTimeField::new('createdAt', 'Créé le')->onlyOnDetail();
        yield AssociationField::new('createdBy', 'Créé par')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail();
        yield AssociationField::new('updatedBy', 'Modifié par')->onlyOnDetail();
    }
}
