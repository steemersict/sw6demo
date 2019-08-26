<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Logging\ScheduledTask;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class LogCleanupTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        SystemConfigService $systemConfigService,
        Connection $connection
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
    }

    public static function getHandledMessages(): iterable
    {
        return [LogCleanupTask::class];
    }

    public function run(): void
    {
        $entryLifetimeSeconds = $this->systemConfigService->get('core.logging.entryLifetimeSeconds');
        $maxEntries = $this->systemConfigService->get('core.logging.entryLimit');

        if ($entryLifetimeSeconds !== -1) {
            $deleteBefore = date(Defaults::STORAGE_DATE_TIME_FORMAT, time() - $entryLifetimeSeconds);
            $this->connection->executeQuery(
                'DELETE FROM `log_entry` WHERE `created_at` < :before', ['before' => $deleteBefore]
            );
        }

        if ($maxEntries !== -1) {
            $sql = 'DELETE ld FROM `log_entry` ld LEFT JOIN (
                        SELECT id
                        FROM `log_entry`
                        ORDER BY `created_at` 
                        DESC LIMIT :maxEntries
                    ) ls ON ld.ID = ls.ID
                    WHERE ls.ID IS NULL;';

            $statement = $this->connection->prepare($sql);
            $statement->bindValue('maxEntries', $maxEntries, \PDO::PARAM_INT);
            $statement->execute();
        }
    }
}
