<?php declare(strict_types=1);

namespace Shopware\Storefront\Theme\Subscriber;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Store\Event\FirstRunWizardFinishedEvent;
use Shopware\Storefront\Theme\ThemeEntity;
use Shopware\Storefront\Theme\ThemeLifecycleService;
use Shopware\Storefront\Theme\ThemeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FirstRunWizardSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThemeService
     */
    private $themeService;

    /**
     * @var ThemeLifecycleService
     */
    private $themeLifecycleService;

    /**
     * @var EntityRepositoryInterface
     */
    private $themeRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $themeSalesChannelRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    public function __construct(
        ThemeService $themeService,
        ThemeLifecycleService $themeLifecycleService,
        EntityRepositoryInterface $themeRepository,
        EntityRepositoryInterface $themeSalesChannelRepository,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->themeService = $themeService;
        $this->themeLifecycleService = $themeLifecycleService;
        $this->themeRepository = $themeRepository;
        $this->themeSalesChannelRepository = $themeSalesChannelRepository;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            FirstRunWizardFinishedEvent::class => 'frwFinished',
        ];
    }

    public function frwFinished(FirstRunWizardFinishedEvent $event): void
    {
        // only run on open -> completed|failed transition
        if (!$event->getPreviousState()->isOpen() || $event->getState()->isOpen()) {
            return;
        }

        $context = $event->getContext();

        $this->themeLifecycleService->refreshThemes($context);

        $criteria = new Criteria();
        $criteria->addAssociation('theme.salesChannels');
        $criteria->addFilter(new EqualsFilter('technicalName', 'Storefront'));
        /** @var ThemeEntity|null $theme */
        $theme = $this->themeRepository->search($criteria, $context)->first();
        if (!$theme) {
            throw new \RuntimeException('Default theme not found');
        }

        $total = $theme->getSalesChannels()->count();

        // only run if the themes are not already initialised
        if ($total) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $salesChannelIds = $this->salesChannelRepository->search($criteria, $context)->getIds();
        foreach ($salesChannelIds as $id) {
            $this->themeService->compileTheme($id, $theme->getId(), $context);
            $this->themeSalesChannelRepository->upsert([[
                'themeId' => $theme->getId(),
                'salesChannelId' => $id,
            ]], $context);
        }
    }
}
