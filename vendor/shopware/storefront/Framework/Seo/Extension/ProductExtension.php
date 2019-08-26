<?php declare(strict_types=1);

namespace Shopware\Storefront\Framework\Seo\Extension;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtensionInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Storefront\Framework\Seo\Entity\Field\CanonicalUrlField;
use Shopware\Storefront\Framework\Seo\Entity\Field\SeoUrlAssociationField;
use Shopware\Storefront\Framework\Seo\SeoUrlRoute\ProductPageSeoUrlRoute;

class ProductExtension implements EntityExtensionInterface
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new SeoUrlAssociationField('seoUrls', ProductPageSeoUrlRoute::ROUTE_NAME, 'id')
        );
        $collection->add(
            (new CanonicalUrlField('canonicalUrl', ProductPageSeoUrlRoute::ROUTE_NAME))
                ->addFlags(new Runtime(), new WriteProtected())
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
