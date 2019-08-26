<?php declare(strict_types=1);

namespace Shopware\Core\Content\ImportExport\Aggregate\ImportExportLog;

use Shopware\Core\Content\ImportExport\Aggregate\ImportExportFile\ImportExportFileEntity;
use Shopware\Core\Content\ImportExport\ImportExportProfileEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\User\UserEntity;

class ImportExportLogEntity extends Entity
{
    use EntityIdTrait;

    public const ACTIVITY_IMPORT = 'import';
    public const ACTIVITY_EXPORT = 'export';

    public const STATE_PROGRESS = 'progress';
    public const STATE_SUCCEEDED = 'succeeded';
    public const STATE_FAILED = 'failed';
    public const STATE_ABORTED = 'aborted';

    /**
     * @var string
     */
    protected $activity;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var int
     */
    protected $records;

    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $profileName;

    /**
     * @var UserEntity|null
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $userId;

    /**
     * @var ImportExportProfileEntity|null
     */
    protected $profile;

    /**
     * @var string|null
     */
    protected $profileId;

    /**
     * @var ImportExportFileEntity|null
     */
    protected $file;

    /**
     * @var string|null
     */
    protected $fileId;

    /**
     * @var \DateTimeInterface
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $updatedAt;

    public function getActivity(): string
    {
        return $this->activity;
    }

    public function setActivity(string $activity): void
    {
        $this->activity = $activity;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getRecords(): int
    {
        return $this->records;
    }

    public function setRecords(int $records): void
    {
        $this->records = $records;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getProfileName(): ?string
    {
        return $this->profileName;
    }

    public function setProfileName(string $profileName): void
    {
        $this->profileName = $profileName;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(UserEntity $userEntity): void
    {
        $this->user = $userEntity;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getProfile(): ?ImportExportProfileEntity
    {
        return $this->profile;
    }

    public function setProfile(ImportExportProfileEntity $profile): void
    {
        $this->profile = $profile;
    }

    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function setProfileId(string $profileId): void
    {
        $this->profileId = $profileId;
    }

    public function getFile(): ?ImportExportFileEntity
    {
        return $this->file;
    }

    public function setFile(ImportExportFileEntity $file): void
    {
        $this->file = $file;
    }

    public function getFileId(): ?string
    {
        return $this->fileId;
    }

    public function setFileId(string $fileId): void
    {
        $this->fileId = $fileId;
    }
}
