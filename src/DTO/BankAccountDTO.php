<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a bank account linked to a party in the weclapp API.
 *
 * Maps to the bankAccount schema. Instances are embedded inside
 * the $bankAccounts array of PartyDTO, CustomerDTO, ContactDTO, and SupplierDTO.
 * All 29 fields of the weclapp OpenAPI bankAccount schema are covered.
 *
 * @see \miralsoft\weclapp\api\DTO\PartyDTO
 */
final class BankAccountDTO extends AbstractDTO
{
    /**
     * @param string       $id                                          Internal weclapp UUID (readOnly).
     * @param string       $version                                     Optimistic locking version string (readOnly).
     * @param int          $createdDate                                 Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate                            Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $accountHolder                               Account holder name.
     * @param string|null  $accountId                                   ID of the linked accounting account.
     * @param string|null  $accountNumber                               Legacy bank account number.
     * @param bool         $active                                      Whether this bank account is active.
     * @param bool         $autoSync                                    Whether automatic bank synchronisation is enabled.
     * @param string|null  $automaticProcessing                         Automatic processing strategy (enum: moneyTransactionProcessingStrategy).
     * @param string|null  $balance                                     Current balance as decimal string (readOnly).
     * @param string|null  $bankCode                                    Bank routing / sort code.
     * @param string|null  $connectionFailure                           Description of the last connection failure (readOnly).
     * @param string|null  $creditInstitute                             Name of the credit institution / bank.
     * @param string|null  $creditInstituteCity                         City of the credit institution.
     * @param string|null  $creditInstituteStreet                       Street address of the credit institution.
     * @param string|null  $creditInstituteZip                          Postal code of the credit institution.
     * @param string|null  $creditLine                                  Approved credit line as decimal string.
     * @param string|null  $currencyId                                  ID of the account currency.
     * @param string|null  $differentSepaCreditorIdentifier             Alternative SEPA creditor identifier.
     * @param bool         $enabledForElectronicPaymentTransactions     Whether electronic payment transactions are enabled.
     * @param string|null  $iban                                        International Bank Account Number (IBAN).
     * @param string|null  $incidentalCostsOfMonetaryTrafficAccountId   ID of the incidental costs account.
     * @param string|null  $incidentalCostsOfMonetaryTrafficTaxId       ID of the incidental costs tax.
     * @param int|null     $lastDownload                                Timestamp of the last bank data download in epoch milliseconds (readOnly).
     * @param bool         $primary                                     Whether this is the primary bank account.
     * @param string|null  $qrIban                                      QR-IBAN for Swiss QR invoices.
     * @param string|null  $qrIdentifier                                QR reference identifier.
     * @param string|null  $swiftBic                                    SWIFT / BIC code.
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $accountHolder,
        public readonly ?string $accountId,
        public readonly ?string $accountNumber,
        public readonly bool    $active,
        public readonly bool    $autoSync,
        public readonly ?string $automaticProcessing,
        public readonly ?string $balance,
        public readonly ?string $bankCode,
        public readonly ?string $connectionFailure,
        public readonly ?string $creditInstitute,
        public readonly ?string $creditInstituteCity,
        public readonly ?string $creditInstituteStreet,
        public readonly ?string $creditInstituteZip,
        public readonly ?string $creditLine,
        public readonly ?string $currencyId,
        public readonly ?string $differentSepaCreditorIdentifier,
        public readonly bool    $enabledForElectronicPaymentTransactions,
        public readonly ?string $iban,
        public readonly ?string $incidentalCostsOfMonetaryTrafficAccountId,
        public readonly ?string $incidentalCostsOfMonetaryTrafficTaxId,
        public readonly ?int    $lastDownload,
        public readonly bool    $primary,
        public readonly ?string $qrIban,
        public readonly ?string $qrIdentifier,
        public readonly ?string $swiftBic,
    ) {}

    /**
     * Create a BankAccountDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                                        self::str($data, 'id'),
            version:                                   self::str($data, 'version'),
            createdDate:                               self::int($data, 'createdDate'),
            lastModifiedDate:                          self::int($data, 'lastModifiedDate'),
            accountHolder:                             self::strOrNull($data, 'accountHolder'),
            accountId:                                 self::strOrNull($data, 'accountId'),
            accountNumber:                             self::strOrNull($data, 'accountNumber'),
            active:                                    self::bool($data, 'active'),
            autoSync:                                  self::bool($data, 'autoSync'),
            automaticProcessing:                       self::strOrNull($data, 'automaticProcessing'),
            balance:                                   self::strOrNull($data, 'balance'),
            bankCode:                                  self::strOrNull($data, 'bankCode'),
            connectionFailure:                         self::strOrNull($data, 'connectionFailure'),
            creditInstitute:                           self::strOrNull($data, 'creditInstitute'),
            creditInstituteCity:                       self::strOrNull($data, 'creditInstituteCity'),
            creditInstituteStreet:                     self::strOrNull($data, 'creditInstituteStreet'),
            creditInstituteZip:                        self::strOrNull($data, 'creditInstituteZip'),
            creditLine:                                self::strOrNull($data, 'creditLine'),
            currencyId:                                self::strOrNull($data, 'currencyId'),
            differentSepaCreditorIdentifier:           self::strOrNull($data, 'differentSepaCreditorIdentifier'),
            enabledForElectronicPaymentTransactions:   self::bool($data, 'enabledForElectronicPaymentTransactions'),
            iban:                                      self::strOrNull($data, 'iban'),
            incidentalCostsOfMonetaryTrafficAccountId: self::strOrNull($data, 'incidentalCostsOfMonetaryTrafficAccountId'),
            incidentalCostsOfMonetaryTrafficTaxId:     self::strOrNull($data, 'incidentalCostsOfMonetaryTrafficTaxId'),
            lastDownload:                              self::intOrNull($data, 'lastDownload'),
            primary:                                   self::bool($data, 'primary'),
            qrIban:                                    self::strOrNull($data, 'qrIban'),
            qrIdentifier:                              self::strOrNull($data, 'qrIdentifier'),
            swiftBic:                                  self::strOrNull($data, 'swiftBic'),
        );
    }

    /**
     * Returns the current balance as a float, or null if not set.
     */
    public function getBalance(): ?float
    {
        return $this->balance !== null ? (float) $this->balance : null;
    }

    /**
     * Returns the credit line as a float, or null if not set.
     */
    public function getCreditLine(): ?float
    {
        return $this->creditLine !== null ? (float) $this->creditLine : null;
    }

    /**
     * Returns the timestamp of the last bank data download as DateTimeImmutable.
     */
    public function getLastDownloadAt(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['lastDownload' => $this->lastDownload], 'lastDownload');
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
