<?php declare(strict_types=1);

namespace Shopware\Storefront\Theme\Extension;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtensionInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Language\LanguageDefinition;
use Shopware\Storefront\Theme\Aggregate\ThemeTranslationDefinition;

class LanguageExtension implements EntityExtensionInterface
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField('themeTranslations', ThemeTranslationDefinition::class, 'language_id')
        );
    }

    public function getDefinitionClass(): string
    {
        return LanguageDefinition::class;
    }
}
