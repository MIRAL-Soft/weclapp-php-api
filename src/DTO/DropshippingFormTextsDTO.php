<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

/**
 * Represents the dropshipping delivery note form texts on a Purchase Order.
 *
 * Maps to the `dropshippingDeliveryNoteFormTextBlockData` schema.
 * Embedded as a single object in PurchaseOrderDTO::$dropshippingDeliveryNoteFormTexts.
 *
 * Contains optional text blocks that override the default record text fields
 * printed on the dropshipping delivery note PDF.
 */
final class DropshippingFormTextsDTO extends AbstractDTO
{
    /**
     * @param string|null $recordComment  HTML comment block for the dropshipping delivery note.
     * @param string|null $recordFreeText HTML free-text block for the dropshipping delivery note.
     * @param string|null $recordOpening  HTML opening block for the dropshipping delivery note.
     */
    public function __construct(
        public readonly ?string $recordComment,
        public readonly ?string $recordFreeText,
        public readonly ?string $recordOpening,
    ) {}

    /**
     * Create a DropshippingFormTextsDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            recordComment:  self::strOrNull($data, 'recordComment'),
            recordFreeText: self::strOrNull($data, 'recordFreeText'),
            recordOpening:  self::strOrNull($data, 'recordOpening'),
        );
    }

    /**
     * Returns true if no text blocks have been set.
     */
    public function isEmpty(): bool
    {
        return $this->recordComment === null
            && $this->recordFreeText === null
            && $this->recordOpening === null;
    }
}
