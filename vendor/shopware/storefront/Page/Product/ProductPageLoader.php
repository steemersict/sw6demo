<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Product;

use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\DataResolver\CmsSlotsDataResolver;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageRepository;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoader;
use Shopware\Storefront\Page\Product\Configurator\ProductPageConfiguratorLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductPageLoader
{
    /**
     * @var GenericPageLoader
     */
    private $genericLoader;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SalesChannelCmsPageRepository
     */
    private $cmsPageRepository;

    /**
     * @var CmsSlotsDataResolver
     */
    private $slotDataResolver;

    /**
     * @var ProductPageConfiguratorLoader
     */
    private $configuratorLoader;

    /**
     * @var ProductDefinition
     */
    private $productDefinition;
    /**
     * @var ProductLoader
     */
    private $productLoader;

    public function __construct(
        GenericPageLoader $genericLoader,
        SalesChannelRepositoryInterface $productRepository,
        EventDispatcherInterface $eventDispatcher,
        SalesChannelCmsPageRepository $cmsPageRepository,
        CmsSlotsDataResolver $slotDataResolver,
        ProductPageConfiguratorLoader $configuratorLoader,
        ProductDefinition $productDefinition,
        ProductLoader $productLoader
    ) {
        $this->genericLoader = $genericLoader;
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->slotDataResolver = $slotDataResolver;
        $this->configuratorLoader = $configuratorLoader;
        $this->productDefinition = $productDefinition;
        $this->productLoader = $productLoader;
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws MissingRequestParameterException
     * @throws ProductNotFoundException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): ProductPage
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);
        $page = ProductPage::createFrom($page);

        $productId = $request->attributes->get('productId');
        if (!$productId) {
            throw new MissingRequestParameterException('productId', '/productId');
        }

        $productId = $this->findBestVariant($productId, $salesChannelContext);

        $product = $this->productLoader->load($productId, $salesChannelContext);
        $page->setProduct($product);

        $page->setConfiguratorSettings(
            $this->configuratorLoader->load($product, $salesChannelContext)
        );

        if ($cmsPage = $this->getCmsPage($salesChannelContext)) {
            $this->loadSlotData($cmsPage, $salesChannelContext, $product);
            $page->setCmsPage($cmsPage);
        }

        $this->eventDispatcher->dispatch(
            new ProductPageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }

    private function loadSlotData(
        CmsPageEntity $page,
        SalesChannelContext $salesChannelContext,
        SalesChannelProductEntity $product
    ): void {
        if (!$page->getBlocks()) {
            return;
        }

        // replace actual request in NEXT-1539
        $request = new Request();

        $resolverContext = new EntityResolverContext($salesChannelContext, $request, $this->productDefinition, $product);
        $slots = $this->slotDataResolver->resolve($page->getBlocks()->getSlots(), $resolverContext);

        $page->getBlocks()->setSlots($slots);
    }

    private function getCmsPage(SalesChannelContext $context): ?CmsPageEntity
    {
        $pages = $this->cmsPageRepository->getPagesByType('product_detail', $context);

        if ($pages->count() === 0) {
            return null;
        }

        /** @var CmsPageEntity $page */
        $page = $pages->first();

        return $page;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function findBestVariant(string $productId, SalesChannelContext $salesChannelContext)
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('product.parentId', $productId))
            ->addSorting(new FieldSorting('product.price'))
            ->setLimit(1);

        $variantId = $this->productRepository->searchIds($criteria, $salesChannelContext);

        if (\count($variantId->getIds()) > 0) {
            return $variantId->getIds()[0];
        }

        return $productId;
    }
}
