<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Document from the weclapp API (/api/v2/document).
 *
 * Documents are attachments that belong to other weclapp entities
 * (e.g. salesInvoice, salesOrder, customer). They are not returned
 * inside those entities but must be queried separately by entityId + entityName.
 *
 * The document ID uses a compound dot-notation format:
 *   {entityName}.{entityId}.{docId}
 *   e.g. "salesInvoice.926104.926166"
 *
 * Important document types for invoice processing:
 *   - SALES_INVOICE              → regular invoice PDF
 *   - SALES_INVOICE_CANCELLATION → cancellation invoice PDF (CLX-number range)
 *
 * @see \miralsoft\weclapp\api\Resource\DocumentResource
 * @see \miralsoft\weclapp\api\Enum\DocumentType
 */
final class DocumentDTO extends AbstractDTO
{
    /**
     * @param string                    $id               Compound document ID (e.g. "salesInvoice.926104.926166").
     * @param string                    $version          Optimistic locking version string.
     * @param int                       $createdDate      Creation timestamp in epoch milliseconds.
     * @param int                       $lastModifiedDate Last modification timestamp in epoch milliseconds.
     * @param string|null               $description      Optional document description (max 4000 chars).
     * @param int|null                  $documentSize     File size in bytes (read-only).
     * @param string                    $documentType     Document type — see DocumentType enum.
     * @param string|null               $mediaType        MIME type (e.g. "application/pdf", max 255 chars).
     * @param string|null               $name             File name (e.g. "Storno-CLX1061-...pdf", max 1000 chars).
     * @param string|null               $userId           ID of the user who created this document (read-only).
     * @param list<DocumentVersionDTO>  $versions         Version history — oldest first.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $description,
        public readonly ?int    $documentSize,
        public readonly string  $documentType,
        public readonly ?string $mediaType,
        public readonly ?string $name,
        public readonly ?string $userId,
        public readonly array   $versions,
    ) {}

    /**
     * Create a DocumentDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $versions = array_map(
            static fn (array $v): DocumentVersionDTO => DocumentVersionDTO::fromArray($v),
            self::arr($data, 'versions'),
        );

        return new static(
            id:               self::str($data, 'id'),
            version:          self::str($data, 'version'),
            createdDate:      self::int($data, 'createdDate'),
            lastModifiedDate: self::int($data, 'lastModifiedDate'),
            description:      self::strOrNull($data, 'description'),
            documentSize:     self::intOrNull($data, 'documentSize'),
            documentType:     self::str($data, 'documentType'),
            mediaType:        self::strOrNull($data, 'mediaType'),
            name:             self::strOrNull($data, 'name'),
            userId:           self::strOrNull($data, 'userId'),
            versions:         $versions,
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

    /**
     * Returns true if this document is a cancellation invoice (documentType = SALES_INVOICE_CANCELLATION).
     *
     * @example
     * if ($doc->isCancellationInvoice()) {
     *     $pdf = $client->documents()->download($doc->id);
     *     file_put_contents($doc->name, $pdf);
     * }
     */
    public function isCancellationInvoice(): bool
    {
        return $this->documentType === 'SALES_INVOICE_CANCELLATION';
    }

    /**
     * Returns true if this document is a regular sales invoice PDF.
     */
    public function isSalesInvoice(): bool
    {
        return $this->documentType === 'SALES_INVOICE';
    }

    /**
     * Returns the file name without directory path, or 'document' as fallback.
     */
    public function getFileName(): string
    {
        return $this->name ?? 'document';
    }

    /**
     * Returns the latest version entry, or null if no version history is available.
     */
    public function getLatestVersion(): ?DocumentVersionDTO
    {
        if (empty($this->versions)) {
            return null;
        }

        return $this->versions[array_key_last($this->versions)];
    }
}
