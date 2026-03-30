<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Resource;

use miralsoft\weclapp\api\DTO\DocumentDTO;
use miralsoft\weclapp\api\Enum\DocumentType;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Query\QueryBuilder;
use miralsoft\weclapp\api\Util\ResponseParser;

/**
 * Resource class for weclapp Document operations.
 *
 * Documents are file attachments that belong to other weclapp entities
 * (e.g. salesInvoice, salesOrder, customer). Unlike most resources they
 * are not embedded in their parent entity — they must be queried
 * separately using entityId + entityName.
 *
 * Document IDs use a compound dot-notation format:
 *   {entityName}.{entityId}.{docId}   e.g. "salesInvoice.926104.926166"
 *
 * Typical usage for cancellation invoice download:
 *
 * @example
 * // Get the cancellation invoice PDF for a specific sales invoice
 * $pdf = $client->documents()->downloadCancellationInvoice($salesInvoiceId);
 * if ($pdf !== null) {
 *     file_put_contents('CLX-1061.pdf', $pdf);
 * }
 *
 * @example
 * // List all documents attached to a sales invoice
 * $docs = $client->documents()->findByEntity($invoiceId, 'salesInvoice');
 * foreach ($docs as $doc) {
 *     echo $doc->documentType . ' — ' . $doc->name . PHP_EOL;
 * }
 *
 * @see \miralsoft\weclapp\api\DTO\DocumentDTO
 * @see \miralsoft\weclapp\api\Enum\DocumentType
 */
class DocumentResource extends AbstractResource
{
    protected string $endpoint = 'document';
    protected string $dtoClass = DocumentDTO::class;

    // -------------------------------------------------------------------------
    // Query
    // -------------------------------------------------------------------------

    /**
     * Find all documents attached to a specific weclapp entity.
     *
     * This is the primary way to retrieve documents. Documents cannot be found
     * by listing all documents — they must be queried by entity.
     *
     * @param string            $entityId   The weclapp UUID of the parent entity.
     * @param string            $entityName The entity type name, e.g. "salesInvoice", "salesOrder", "customer".
     * @param QueryBuilder|null $extra      Optional additional filters (e.g. sort order).
     * @return list<DocumentDTO>
     *
     * @throws WeclappApiException
     *
     * @example
     * $docs = $client->documents()->findByEntity($invoiceId, 'salesInvoice');
     */
    public function findByEntity(
        string $entityId,
        string $entityName,
        ?QueryBuilder $extra = null,
    ): array {
        $all      = [];
        $page     = 1;
        $pageSize = 100;

        do {
            $params = [
                'entityId'   => $entityId,
                'entityName' => $entityName,
                'page'       => $page,
                'pageSize'   => $pageSize,
            ];

            // Merge in any extra sort/filter params from QueryBuilder
            if ($extra !== null) {
                // Extract filter params only (no page/pageSize override)
                $extraString = ltrim($extra->buildForCount(), '?');
                if ($extraString !== '') {
                    parse_str($extraString, $extraParams);
                    $params = array_merge($params, $extraParams);
                }
            }

            $queryString = '?' . http_build_query($params);

            $data = $this->rateLimiter->execute(
                fn () => $this->http->get($this->endpoint, $queryString)
            );

            $rawItems = ResponseParser::extractList($data);
            $items    = array_map(
                static fn (array $item): DocumentDTO => DocumentDTO::fromArray($item),
                $rawItems,
            );

            $all     = array_merge($all, $items);
            $hasMore = count($items) >= $pageSize;
            $page++;
        } while ($hasMore);

        return $all;
    }

    /**
     * Find all documents of a specific type attached to an entity.
     *
     * @param string       $entityId    The weclapp UUID of the parent entity.
     * @param string       $entityName  The entity type name (e.g. "salesInvoice").
     * @param DocumentType $type        The document type to filter by.
     * @return list<DocumentDTO>
     *
     * @throws WeclappApiException
     *
     * @example
     * $invoiceDocs = $client->documents()->findByEntityAndType(
     *     $invoiceId, 'salesInvoice', DocumentType::SalesInvoiceCancellation
     * );
     */
    public function findByEntityAndType(
        string $entityId,
        string $entityName,
        DocumentType $type,
    ): array {
        $all = $this->findByEntity($entityId, $entityName);

        return array_values(array_filter(
            $all,
            static fn (DocumentDTO $doc): bool => $doc->documentType === $type->value,
        ));
    }

    /**
     * Find the cancellation invoice document (SALES_INVOICE_CANCELLATION) for a sales invoice.
     *
     * Returns the first matching document, or null if no cancellation document exists.
     * A cancellation document is only present when the invoice was cancelled via
     * weclapp (status = CANCELLED, cancellationNumber set).
     *
     * @param string $salesInvoiceId The weclapp UUID of the original sales invoice.
     * @return DocumentDTO|null
     *
     * @throws WeclappApiException
     *
     * @example
     * $doc = $client->documents()->findCancellationDocument($invoiceId);
     * if ($doc !== null) {
     *     echo $doc->name; // e.g. "Storno-CLX1061-R-RE26767-Kdnr-12070.pdf" (weclapp-generated filename)
     * }
     */
    public function findCancellationDocument(string $salesInvoiceId): ?DocumentDTO
    {
        $docs = $this->findByEntityAndType(
            $salesInvoiceId,
            'salesInvoice',
            DocumentType::SalesInvoiceCancellation,
        );

        return $docs[0] ?? null;
    }

    // -------------------------------------------------------------------------
    // Download
    // -------------------------------------------------------------------------

    /**
     * Download the binary content of a document by its ID.
     *
     * The document ID may contain dots (e.g. "salesInvoice.926104.926166") —
     * it is URL-encoded automatically before building the request path.
     *
     * @param string $id The compound document ID.
     * @return string Raw binary file content (e.g. PDF bytes).
     *
     * @throws WeclappApiException
     *
     * @example
     * $pdf = $client->documents()->download('salesInvoice.926104.926166');
     * file_put_contents('storno.pdf', $pdf);
     */
    public function download(string $id): string
    {
        return $this->rateLimiter->execute(
            fn () => $this->http->getBinary(
                $this->endpoint . '/id/' . rawurlencode($id) . '/download'
            )
        );
    }

    /**
     * Download the cancellation invoice PDF for a given sales invoice ID.
     *
     * Convenience method that combines findCancellationDocument() + download().
     * Returns null if no cancellation document exists for this invoice.
     *
     * @param string $salesInvoiceId The weclapp UUID of the original (cancelled) sales invoice.
     * @return string|null Raw binary PDF content, or null if no cancellation document found.
     *
     * @throws WeclappApiException
     *
     * @example
     * if ($invoice->status === 'CANCELLED') {
     *     $pdf = $client->documents()->downloadCancellationInvoice($invoice->id);
     *     if ($pdf !== null) {
     *         file_put_contents($invoice->cancellationNumber . '.pdf', $pdf);
     *     }
     * }
     */
    public function downloadCancellationInvoice(string $salesInvoiceId): ?string
    {
        $doc = $this->findCancellationDocument($salesInvoiceId);

        if ($doc === null) {
            return null;
        }

        return $this->download($doc->id);
    }

    // -------------------------------------------------------------------------
    // Upload
    // -------------------------------------------------------------------------

    /**
     * Upload a new document and attach it to a weclapp entity.
     *
     * Creates a new document record with the given binary content.
     * The document is linked to the specified entity via entityId + entityName.
     *
     * @param string       $entityId    The weclapp UUID of the parent entity.
     * @param string       $entityName  The entity type name (e.g. "salesInvoice", "customer").
     * @param string       $name        File name including extension (e.g. "invoice.pdf").
     * @param string       $binary      Raw binary file content to upload.
     * @param DocumentType|null $type   Optional document type classification.
     * @param string       $description Optional document description (max 4000 chars).
     * @param string       $contentType MIME type of the file. Default: application/octet-stream.
     * @return DocumentDTO             The newly created document.
     *
     * @throws WeclappApiException
     *
     * @example
     * $pdfBytes = file_get_contents('/path/to/invoice.pdf');
     * $doc = $client->documents()->upload(
     *     entityId:    $invoiceId,
     *     entityName:  'salesInvoice',
     *     name:        'invoice.pdf',
     *     binary:      $pdfBytes,
     *     type:        DocumentType::SalesInvoice,
     *     contentType: 'application/pdf',
     * );
     */
    public function upload(
        string $entityId,
        string $entityName,
        string $name,
        string $binary,
        ?DocumentType $type = null,
        string $description = '',
        string $contentType = 'application/octet-stream',
    ): DocumentDTO {
        $params = [
            'entityId'   => $entityId,
            'entityName' => $entityName,
            'name'       => $name,
        ];

        if ($description !== '') {
            $params['description'] = $description;
        }

        if ($type !== null) {
            $params['documentType'] = $type->value;
        }

        $queryString = '?' . http_build_query($params);

        $response = $this->rateLimiter->execute(
            fn () => $this->http->postUpload(
                $this->endpoint . '/upload',
                $queryString,
                $binary,
                $contentType,
            )
        );

        // POST /document/upload wraps result in {"result": {...}}
        $data = $response['result'] ?? $response;

        return DocumentDTO::fromArray($data);
    }

    /**
     * Upload a new version of an existing document.
     *
     * Adds a new version entry to the document's version history.
     * The document ID may contain dots — it is URL-encoded automatically.
     *
     * @param string $id          The compound document ID (e.g. "salesInvoice.926104.926166").
     * @param string $binary      Raw binary content of the new version.
     * @param string $comment     Optional comment for this version (shown in version history).
     * @param string $contentType MIME type of the file. Default: application/octet-stream.
     * @return DocumentDTO        The updated document with new version history entry.
     *
     * @throws WeclappApiException
     *
     * @example
     * $updatedDoc = $client->documents()->uploadVersion(
     *     id:          'salesInvoice.926104.926166',
     *     binary:      file_get_contents('new-version.pdf'),
     *     comment:     'Corrected amount',
     *     contentType: 'application/pdf',
     * );
     */
    public function uploadVersion(
        string $id,
        string $binary,
        string $comment = '',
        string $contentType = 'application/octet-stream',
    ): DocumentDTO {
        $queryString = $comment !== '' ? ('?' . http_build_query(['comment' => $comment])) : '';

        $response = $this->rateLimiter->execute(
            fn () => $this->http->postUpload(
                $this->endpoint . '/id/' . rawurlencode($id) . '/upload',
                $queryString,
                $binary,
                $contentType,
            )
        );

        // POST /document/id/{id}/upload wraps result in {"result": {...}}
        $data = $response['result'] ?? $response;

        return DocumentDTO::fromArray($data);
    }

    // -------------------------------------------------------------------------
    // Standard CRUD — with URL-encoded ID
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * Overrides AbstractResource::find() to URL-encode the document ID,
     * which may contain dots (e.g. "salesInvoice.926104.926166").
     *
     * @return DocumentDTO
     */
    public function find(string $id): DocumentDTO
    {
        $data = $this->rateLimiter->execute(
            fn () => $this->http->get($this->endpoint . '/id/' . rawurlencode($id))
        );

        return DocumentDTO::fromArray($data);
    }

    /**
     * {@inheritdoc}
     *
     * Overrides AbstractResource::update() to URL-encode the document ID.
     *
     * @param array<string, mixed> $data Fields to update: description, documentType, mediaType, name, versions.
     * @return DocumentDTO
     */
    public function update(string $id, array $data): DocumentDTO
    {
        $response = $this->rateLimiter->execute(
            fn () => $this->http->put(
                $this->endpoint . '/id/' . rawurlencode($id),
                $data,
            )
        );

        return DocumentDTO::fromArray($response);
    }

    /**
     * {@inheritdoc}
     *
     * Overrides AbstractResource::delete() to URL-encode the document ID.
     */
    public function delete(string $id): void
    {
        $this->rateLimiter->execute(
            fn () => $this->http->delete($this->endpoint . '/id/' . rawurlencode($id))
        );
    }
}
