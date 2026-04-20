<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents an image attached to an Article.
 *
 * Maps to the articleImage schema in the weclapp API.
 * Instances are embedded inside ArticleDTO::$articleImages.
 *
 * API key name on the parent article: articleImages
 *
 * @see \miralsoft\weclapp\api\DTO\ArticleDTO
 */
final class ArticleImageDTO extends AbstractDTO
{
    /**
     * @param string  $id               Internal weclapp UUID (readOnly).
     * @param string  $version          Optimistic locking version string (readOnly).
     * @param int     $createdDate      Creation timestamp in epoch milliseconds (readOnly).
     * @param int     $lastModifiedDate Last modification timestamp in epoch milliseconds (readOnly).
     * @param string  $fileName         Original file name of the image.
     * @param bool    $mainImage        True if this is the primary/main image of the article.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $version,
        public readonly int    $createdDate,
        public readonly int    $lastModifiedDate,
        public readonly string $fileName,
        public readonly bool   $mainImage,
    ) {}

    /**
     * Create an ArticleImageDTO from a raw weclapp API response array.
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
            fileName:         self::str($data, 'fileName'),
            mainImage:        self::bool($data, 'mainImage'),
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
