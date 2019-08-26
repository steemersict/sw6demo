<?php declare(strict_types=1);

namespace Shopware\Storefront\Framework\Seo\SeoUrl;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReadProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Language\LanguageDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class SeoUrlDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'seo_url';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SeoUrlCollection::class;
    }

    public function getEntityClass(): string
    {
        return SeoUrlEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new Required()),
            (new IdField('foreign_key', 'foreignKey'))->addFlags(new Required()),

            (new StringField('route_name', 'routeName', 50))->addFlags(new Required()),
            (new StringField('path_info', 'pathInfo', 750))->addFlags(new Required()),
            (new StringField('seo_path_info', 'seoPathInfo', 750))->addFlags(new Required()),

            new BoolField('is_canonical', 'isCanonical'),
            new BoolField('is_modified', 'isModified'),
            new BoolField('is_deleted', 'isDeleted'),
            new BoolField('is_valid', 'isValid'),

            (new StringField('url', 'url'))->addFlags(new Runtime()),

            (new IntField('auto_increment', 'autoIncrement'))->addFlags(new WriteProtected(Context::SYSTEM_SCOPE)),

            new CustomFields(),

            new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false),

            (new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false))->addFlags(new ReadProtected(SalesChannelApiSource::class)),
        ]);
    }
}
