<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a single version entry in a weclapp Document's version history.
 *
 * Each upload to an existing document creates a new version entry.
 * Version history is returned as part of the DocumentDTO::$versions array.
 *
 * @see DocumentDTO
 */
final class DocumentVersionDTO extends AbstractDTO
{
    /**
     * @param string      $id              Internal weclapp version UUID.
     * @param string      $version         Optimistic locking version string.
     * @param int         $createdDate     Creation timestamp in epoch milliseconds.
     * @param int         $lastModifiedDate Last modification timestamp in epoch milliseconds.
     * @param string|null $comment         Optional comment left when uploading this version.
     * @param int|null    $documentSize    File size of this version in bytes.
     * @param string|null $documentVersion Human-readable version label (max 60 chars).
     * @param string|null $userId          ID of the user who uploaded this version.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $comment,
        public readonly ?int    $documentSize,
        public readonly ?string $documentVersion,
        public readonly ?string $userId,
    ) {}

    /**
     * Create a DocumentVersionDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:               self::str($data, 'id'),
            version:          self::str($data, 'version'),
            createdDate:      self::int($data, 'createdDate'),
            lastModifiedDate: self::int($data, 'lastModifiedDate'),
            comment:          self::strOrNull($data, 'comment'),
            documentSize:     self::intOrNull($data, 'documentSize'),
            documentVersion:  self::strOrNull($data, 'documentVersion'),
            userId:           self::strOrNull($data, 'userId'),
        );
    }

    /**
     * Returns the creation date as a DateTimeImmutable object.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['createdDate' => $this->createdDate], 'createdDate');
    }

    /**
     * Returns the last modification date as a DateTimeImmutable object.
     */
    public function getLastModifiedAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['lastModifiedDate' => $this->lastModifiedDate], 'lastModifiedDate');
    }
}
