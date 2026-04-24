<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\DTO;

use DateTimeImmutable;

/**
 * Represents a Party record from the weclapp API.
 *
 * The party endpoint is the common base for customers, suppliers, and contacts.
 * All three share the same party schema. This DTO covers all 137 fields of the
 * weclapp OpenAPI party schema (abstractParty + party inline properties).
 *
 * Use this when resolving a partyId reference from an invoice or order.
 * For type-specific access, prefer CustomerDTO, ContactDTO, or SupplierDTO.
 *
 * Customer-specific fields (customerNumber, customerBlocked, etc.) are present
 * but will be null/false for non-customer parties.
 * Supplier-specific fields (supplierNumber, supplierActive, etc.) are present
 * but will be null/false for non-supplier parties.
 *
 * @see \miralsoft\weclapp\api\Resource\PartyResource
 * @see \miralsoft\weclapp\api\DTO\CustomerDTO
 * @see \miralsoft\weclapp\api\DTO\SupplierDTO
 * @see \miralsoft\weclapp\api\DTO\ContactDTO
 */
final class PartyDTO extends AbstractDTO
{
    /**
     * @param string       $id                                          Internal weclapp UUID (readOnly).
     * @param string       $version                                     Optimistic locking version string (readOnly).
     * @param int          $createdDate                                 Creation timestamp in epoch milliseconds (readOnly).
     * @param int          $lastModifiedDate                            Last modification timestamp in epoch milliseconds (readOnly).
     * @param string|null  $partyType                                   Entity type: "ORGANIZATION" or "PERSON".
     * @param string|null  $salutation                                  Salutation (enum: salutation, e.g. MR, MRS).
     * @param string|null  $company                                     Company name (for ORGANIZATION type).
     * @param string|null  $company2                                    Second company name line.
     * @param string|null  $firstName                                   First name (for PERSON type).
     * @param string|null  $lastName                                    Last name (for PERSON type).
     * @param string|null  $middleName                                  Middle name.
     * @param int|null     $birthDate                                   Date of birth in epoch milliseconds.
     * @param string|null  $titleId                                     ID of the academic/professional title.
     * @param string|null  $email                                       Primary e-mail address.
     * @param string|null  $emailHome                                   Private e-mail address.
     * @param string|null  $phone                                       Primary phone number.
     * @param string|null  $phoneHome                                   Private phone number.
     * @param string|null  $fax                                         Fax number.
     * @param string|null  $mobilePhone1                                Primary mobile phone number.
     * @param string|null  $mobilePhone2                                Secondary mobile phone number.
     * @param string|null  $fixPhone2                                   Secondary fixed-line phone number.
     * @param string|null  $website                                     Website URL.
     * @param string|null  $personCompany                               Name of the company this person belongs to.
     * @param string|null  $personDepartmentId                          ID of the person's department.
     * @param string|null  $personRoleId                                ID of the person's role/position.
     * @param string|null  $imageId                                     ID of the party's profile image.
     * @param string|null  $description                                 Internal description or notes.
     * @param string|null  $parentPartyId                               ID of the parent party (e.g. company for a contact).
     * @param string|null  $primaryContactId                            ID of the primary contact person.
     * @param string|null  $primaryAddressId                            ID of the primary address.
     * @param string|null  $deliveryAddressId                           ID of the default delivery address.
     * @param string|null  $invoiceAddressId                            ID of the default invoice address.
     * @param string|null  $dunningAddressId                            ID of the dunning address.
     * @param string|null  $currencyId                                  ID of the assigned currency.
     * @param string|null  $commercialLanguageId                        ID of the commercial language for documents.
     * @param string|null  $responsibleUserId                           ID of the responsible weclapp user.
     * @param bool         $fixedResponsibleUser                        Whether the responsible user is fixed (not auto-reassigned).
     * @param string|null  $referenceNumber                             External reference number.
     * @param string|null  $regionId                                    ID of the assigned region.
     * @param string|null  $sectorId                                    ID of the assigned industry sector.
     * @param string|null  $companySizeId                               ID of the company size classification.
     * @param string|null  $legalFormId                                 ID of the legal form.
     * @param string|null  $ratingId                                    ID of the creditworthiness rating.
     * @param string|null  $leadRatingId                                ID of the lead rating.
     * @param string|null  $leadSourceId                                ID of the lead source.
     * @param string|null  $leadStatus                                  Lead status (enum: leadStatus).
     * @param int|null     $convertedOnDate                             Date the lead was converted in epoch milliseconds.
     * @param string|null  $eoriNumber                                  EORI number for customs.
     * @param string|null  $taxId                                       Tax ID number.
     * @param string|null  $vatIdentificationNumber                     VAT identification number.
     * @param string|null  $xRechnungLeitwegId                         XRechnung routing ID (German e-invoice).
     * @param bool         $factoring                                   Whether factoring is active for this party.
     * @param bool         $commissionBlock                             Whether commission is blocked.
     * @param bool         $competitor                                  Whether this party is a competitor.
     * @param bool         $formerSalesPartner                          Whether this was formerly a sales partner.
     * @param bool         $habitualExporter                            Whether this party is a habitual exporter.
     * @param bool         $salesPartner                                Whether this party is a sales partner.
     * @param string|null  $salesPartnerDefaultCommissionFix            Default fixed commission for sales partner as decimal string.
     * @param string|null  $salesPartnerDefaultCommissionPercentage     Default percentage commission for sales partner as decimal string.
     * @param string|null  $salesPartnerDefaultCommissionType           Default commission type (enum: commissionType).
     * @param bool         $optInEmail                                  E-mail marketing opt-in flag.
     * @param bool         $optInLetter                                 Letter marketing opt-in flag.
     * @param bool         $optInPhone                                  Phone marketing opt-in flag.
     * @param bool         $optInSms                                    SMS marketing opt-in flag.
     * @param bool         $invoiceBlock                                Whether invoicing is blocked.
     * @param string|null  $invoiceRecipientId                          ID of the invoice recipient party.
     * @param string|null  $deliveryEmailAddressesId                    ID of the delivery e-mail addresses record.
     * @param string|null  $dunningEmailAddressesId                     ID of the dunning e-mail addresses record.
     * @param string|null  $purchaseEmailAddressesId                    ID of the purchase e-mail addresses record.
     * @param string|null  $quotationEmailAddressesId                   ID of the quotation e-mail addresses record.
     * @param string|null  $salesInvoiceEmailAddressesId                ID of the sales invoice e-mail addresses record.
     * @param string|null  $salesOrderEmailAddressesId                  ID of the sales order e-mail addresses record.
     * @param bool         $purchaseViaPlafond                          Whether purchases via plafond/credit limit.
     * @param bool         $enableDropshippingInNewSupplySources        Whether drop-shipping is enabled for new supply sources.
     * @param string|null  $publicPageUuid                              UUID for the public party page.
     * @param int|null     $publicPageExpirationDate                    Expiration date of the public page in epoch milliseconds.
     * @param bool         $customer                                    Whether this party is a customer.
     * @param string|null  $customerNumber                              Customer number (e.g. "K-10042").
     * @param string|null  $customerNumberOld                           Legacy/old customer number.
     * @param bool         $customerBlocked                             Whether the customer is blocked.
     * @param bool         $customerDeliveryBlock                       Whether delivery is blocked for this customer.
     * @param bool         $customerInsolvent                           Whether insolvency proceedings are active.
     * @param bool         $customerInsured                             Whether the customer is credit-insured.
     * @param bool         $customerUseCustomsTariffNumber              Whether customs tariff numbers apply.
     * @param bool         $customerAllowDropshippingOrderCreation      Whether drop-shipping orders can be created.
     * @param string|null  $customerBusinessType                        Customer business type (enum: customerBusinessType).
     * @param string|null  $customerCategoryId                          ID of the customer category.
     * @param string|null  $customerCreditLimit                         Credit limit as decimal string.
     * @param string|null  $customerAmountInsured                       Credit-insured amount as decimal string.
     * @param string|null  $customerAnnualRevenue                       Annual revenue as decimal string.
     * @param string|null  $customerBlockNotice                         Notice/reason for customer block.
     * @param string|null  $customerCurrentSalesStageId                 ID of the current sales stage.
     * @param string|null  $customerDebtorAccountId                     ID of the debtor account.
     * @param string|null  $customerDebtorAccountingCodeId              ID of the debtor accounting code.
     * @param string|null  $customerDefaultHeaderDiscount               Default header discount as decimal string.
     * @param string|null  $customerDefaultHeaderSurcharge              Default header surcharge as decimal string.
     * @param string|null  $customerDefaultShippingCarrierId            ID of the default shipping carrier.
     * @param string|null  $customerDefaultWarehouseId                  ID of the default warehouse.
     * @param string|null  $customerInternalNote                        Internal note about the customer.
     * @param string|null  $customerLossDescription                     Description of customer loss reason.
     * @param string|null  $customerLossReasonId                        ID of the customer loss reason.
     * @param string|null  $customerNonStandardTaxId                    ID of non-standard tax rule for this customer.
     * @param string|null  $customerPaymentMethodId                     ID of the default payment method.
     * @param string|null  $customerSalesChannel                        Sales channel (enum: distributionChannel).
     * @param string|null  $customerSalesOrderPaymentType               Default payment type for sales orders (enum: salesOrderPaymentType).
     * @param int|null     $customerSalesProbability                    Sales probability in percent (0–100).
     * @param string|null  $customerSatisfaction                        Customer satisfaction level (enum: customerSatisfaction).
     * @param string|null  $customerShipmentMethodId                    ID of the default shipment method.
     * @param string|null  $customerSupplierNumber                      This customer's number in external supplier systems.
     * @param string|null  $customerTermOfPaymentId                     ID of the default payment terms.
     * @param bool         $supplier                                    Whether this party is a supplier.
     * @param bool         $supplierActive                              Whether the supplier account is active.
     * @param bool         $supplierOrderBlock                          Whether ordering from this supplier is blocked.
     * @param bool         $supplierMergeItemsForOcrInvoiceUpload       Whether to merge items in OCR invoice uploads.
     * @param string|null  $supplierNumber                              Supplier number (e.g. "L-10001").
     * @param string|null  $supplierNumberOld                           Legacy/old supplier number.
     * @param string|null  $supplierCreditorAccountId                   ID of the creditor account.
     * @param string|null  $supplierCreditorAccountingCodeId            ID of the creditor accounting code.
     * @param string|null  $supplierCustomerNumberAtSupplier            Our customer number at this supplier.
     * @param string|null  $supplierDefaultShippingCarrierId            ID of the default shipping carrier (purchase).
     * @param string|null  $supplierInternalNote                        Internal note about the supplier.
     * @param string|null  $supplierMinimumPurchaseOrderAmount          Minimum purchase order amount as decimal string.
     * @param string|null  $supplierNonStandardTaxId                    ID of non-standard tax rule for this supplier.
     * @param string|null  $supplierPaymentMethodId                     ID of the default purchase payment method.
     * @param string|null  $supplierShipmentMethodId                    ID of the default purchase shipment method.
     * @param string|null  $supplierTermOfPaymentId                     ID of the default purchase payment terms.
     * @param list<\miralsoft\weclapp\api\DTO\AddressDTO>                               $addresses                         List of addresses.
     * @param list<\miralsoft\weclapp\api\DTO\BankAccountDTO>                           $bankAccounts                      List of bank accounts.
     * @param list<\miralsoft\weclapp\api\DTO\OnlineAccountDTO>                         $onlineAccounts                    List of online accounts.
     * @param list<\miralsoft\weclapp\api\DTO\CommissionSalesPartnerDTO>                $commissionSalesPartners           List of commission sales partners.
     * @param list<\miralsoft\weclapp\api\DTO\PartyHabitualExporterLetterOfIntentDTO>   $partyHabitualExporterLettersOfIntent Habitual exporter letters of intent.
     * @param list<\miralsoft\weclapp\api\DTO\CustomAttributeDTO>                       $customAttributes                  List of custom attributes.
     * @param list<array>                                                                $contacts                          List of linked contact party objects (raw).
     * @param list<array>                                                                $partyEmailAddresses               List of e-mail address records (raw).
     * @param list<array>                                                                $customerSalesStageHistory         List of sales stage history entries (raw).
     * @param list<array>                                                                $tags                              List of tag objects (raw).
     * @param list<array>                                                                $topics                            List of topic objects (raw).
     */
    public function __construct(
        public readonly string  $id,
        public readonly string  $version,
        public readonly int     $createdDate,
        public readonly int     $lastModifiedDate,
        public readonly ?string $partyType,
        public readonly ?string $salutation,
        public readonly ?string $company,
        public readonly ?string $company2,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $middleName,
        public readonly ?int    $birthDate,
        public readonly ?string $titleId,
        public readonly ?string $email,
        public readonly ?string $emailHome,
        public readonly ?string $phone,
        public readonly ?string $phoneHome,
        public readonly ?string $fax,
        public readonly ?string $mobilePhone1,
        public readonly ?string $mobilePhone2,
        public readonly ?string $fixPhone2,
        public readonly ?string $website,
        public readonly ?string $personCompany,
        public readonly ?string $personDepartmentId,
        public readonly ?string $personRoleId,
        public readonly ?string $imageId,
        public readonly ?string $description,
        public readonly ?string $parentPartyId,
        public readonly ?string $primaryContactId,
        public readonly ?string $primaryAddressId,
        public readonly ?string $deliveryAddressId,
        public readonly ?string $invoiceAddressId,
        public readonly ?string $dunningAddressId,
        public readonly ?string $currencyId,
        public readonly ?string $commercialLanguageId,
        public readonly ?string $responsibleUserId,
        public readonly bool    $fixedResponsibleUser,
        public readonly ?string $referenceNumber,
        public readonly ?string $regionId,
        public readonly ?string $sectorId,
        public readonly ?string $companySizeId,
        public readonly ?string $legalFormId,
        public readonly ?string $ratingId,
        public readonly ?string $leadRatingId,
        public readonly ?string $leadSourceId,
        public readonly ?string $leadStatus,
        public readonly ?int    $convertedOnDate,
        public readonly ?string $eoriNumber,
        public readonly ?string $taxId,
        public readonly ?string $vatIdentificationNumber,
        public readonly ?string $xRechnungLeitwegId,
        public readonly bool    $factoring,
        public readonly bool    $commissionBlock,
        public readonly bool    $competitor,
        public readonly bool    $formerSalesPartner,
        public readonly bool    $habitualExporter,
        public readonly bool    $salesPartner,
        public readonly ?string $salesPartnerDefaultCommissionFix,
        public readonly ?string $salesPartnerDefaultCommissionPercentage,
        public readonly ?string $salesPartnerDefaultCommissionType,
        public readonly bool    $optInEmail,
        public readonly bool    $optInLetter,
        public readonly bool    $optInPhone,
        public readonly bool    $optInSms,
        public readonly bool    $invoiceBlock,
        public readonly ?string $invoiceRecipientId,
        public readonly ?string $deliveryEmailAddressesId,
        public readonly ?string $dunningEmailAddressesId,
        public readonly ?string $purchaseEmailAddressesId,
        public readonly ?string $quotationEmailAddressesId,
        public readonly ?string $salesInvoiceEmailAddressesId,
        public readonly ?string $salesOrderEmailAddressesId,
        public readonly bool    $purchaseViaPlafond,
        public readonly bool    $enableDropshippingInNewSupplySources,
        public readonly ?string $publicPageUuid,
        public readonly ?int    $publicPageExpirationDate,
        public readonly bool    $customer,
        public readonly ?string $customerNumber,
        public readonly ?string $customerNumberOld,
        public readonly bool    $customerBlocked,
        public readonly bool    $customerDeliveryBlock,
        public readonly bool    $customerInsolvent,
        public readonly bool    $customerInsured,
        public readonly bool    $customerUseCustomsTariffNumber,
        public readonly bool    $customerAllowDropshippingOrderCreation,
        public readonly ?string $customerBusinessType,
        public readonly ?string $customerCategoryId,
        public readonly ?string $customerCreditLimit,
        public readonly ?string $customerAmountInsured,
        public readonly ?string $customerAnnualRevenue,
        public readonly ?string $customerBlockNotice,
        public readonly ?string $customerCurrentSalesStageId,
        public readonly ?string $customerDebtorAccountId,
        public readonly ?string $customerDebtorAccountingCodeId,
        public readonly ?string $customerDefaultHeaderDiscount,
        public readonly ?string $customerDefaultHeaderSurcharge,
        public readonly ?string $customerDefaultShippingCarrierId,
        public readonly ?string $customerDefaultWarehouseId,
        public readonly ?string $customerInternalNote,
        public readonly ?string $customerLossDescription,
        public readonly ?string $customerLossReasonId,
        public readonly ?string $customerNonStandardTaxId,
        public readonly ?string $customerPaymentMethodId,
        public readonly ?string $customerSalesChannel,
        public readonly ?string $customerSalesOrderPaymentType,
        public readonly ?int    $customerSalesProbability,
        public readonly ?string $customerSatisfaction,
        public readonly ?string $customerShipmentMethodId,
        public readonly ?string $customerSupplierNumber,
        public readonly ?string $customerTermOfPaymentId,
        public readonly bool    $supplier,
        public readonly bool    $supplierActive,
        public readonly bool    $supplierOrderBlock,
        public readonly bool    $supplierMergeItemsForOcrInvoiceUpload,
        public readonly ?string $supplierNumber,
        public readonly ?string $supplierNumberOld,
        public readonly ?string $supplierCreditorAccountId,
        public readonly ?string $supplierCreditorAccountingCodeId,
        public readonly ?string $supplierCustomerNumberAtSupplier,
        public readonly ?string $supplierDefaultShippingCarrierId,
        public readonly ?string $supplierInternalNote,
        public readonly ?string $supplierMinimumPurchaseOrderAmount,
        public readonly ?string $supplierNonStandardTaxId,
        public readonly ?string $supplierPaymentMethodId,
        public readonly ?string $supplierShipmentMethodId,
        public readonly ?string $supplierTermOfPaymentId,
        public readonly array   $addresses,
        public readonly array   $bankAccounts,
        public readonly array   $onlineAccounts,
        public readonly array   $commissionSalesPartners,
        public readonly array   $partyHabitualExporterLettersOfIntent,
        public readonly array   $customAttributes,
        public readonly array   $contacts,
        public readonly array   $partyEmailAddresses,
        public readonly array   $customerSalesStageHistory,
        public readonly array   $tags,
        public readonly array   $topics,
    ) {}

    /**
     * Create a PartyDTO from a raw weclapp API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:                                      self::str($data, 'id'),
            version:                                 self::str($data, 'version'),
            createdDate:                             self::int($data, 'createdDate'),
            lastModifiedDate:                        self::int($data, 'lastModifiedDate'),
            partyType:                               self::strOrNull($data, 'partyType'),
            salutation:                              self::strOrNull($data, 'salutation'),
            company:                                 self::strOrNull($data, 'company'),
            company2:                                self::strOrNull($data, 'company2'),
            firstName:                               self::strOrNull($data, 'firstName'),
            lastName:                                self::strOrNull($data, 'lastName'),
            middleName:                              self::strOrNull($data, 'middleName'),
            birthDate:                               self::intOrNull($data, 'birthDate'),
            titleId:                                 self::strOrNull($data, 'titleId'),
            email:                                   self::strOrNull($data, 'email'),
            emailHome:                               self::strOrNull($data, 'emailHome'),
            phone:                                   self::strOrNull($data, 'phone'),
            phoneHome:                               self::strOrNull($data, 'phoneHome'),
            fax:                                     self::strOrNull($data, 'fax'),
            mobilePhone1:                            self::strOrNull($data, 'mobilePhone1'),
            mobilePhone2:                            self::strOrNull($data, 'mobilePhone2'),
            fixPhone2:                               self::strOrNull($data, 'fixPhone2'),
            website:                                 self::strOrNull($data, 'website'),
            personCompany:                           self::strOrNull($data, 'personCompany'),
            personDepartmentId:                      self::strOrNull($data, 'personDepartmentId'),
            personRoleId:                            self::strOrNull($data, 'personRoleId'),
            imageId:                                 self::strOrNull($data, 'imageId'),
            description:                             self::strOrNull($data, 'description'),
            parentPartyId:                           self::strOrNull($data, 'parentPartyId'),
            primaryContactId:                        self::strOrNull($data, 'primaryContactId'),
            primaryAddressId:                        self::strOrNull($data, 'primaryAddressId'),
            deliveryAddressId:                       self::strOrNull($data, 'deliveryAddressId'),
            invoiceAddressId:                        self::strOrNull($data, 'invoiceAddressId'),
            dunningAddressId:                        self::strOrNull($data, 'dunningAddressId'),
            currencyId:                              self::strOrNull($data, 'currencyId'),
            commercialLanguageId:                    self::strOrNull($data, 'commercialLanguageId'),
            responsibleUserId:                       self::strOrNull($data, 'responsibleUserId'),
            fixedResponsibleUser:                    self::bool($data, 'fixedResponsibleUser'),
            referenceNumber:                         self::strOrNull($data, 'referenceNumber'),
            regionId:                                self::strOrNull($data, 'regionId'),
            sectorId:                                self::strOrNull($data, 'sectorId'),
            companySizeId:                           self::strOrNull($data, 'companySizeId'),
            legalFormId:                             self::strOrNull($data, 'legalFormId'),
            ratingId:                                self::strOrNull($data, 'ratingId'),
            leadRatingId:                            self::strOrNull($data, 'leadRatingId'),
            leadSourceId:                            self::strOrNull($data, 'leadSourceId'),
            leadStatus:                              self::strOrNull($data, 'leadStatus'),
            convertedOnDate:                         self::intOrNull($data, 'convertedOnDate'),
            eoriNumber:                              self::strOrNull($data, 'eoriNumber'),
            taxId:                                   self::strOrNull($data, 'taxId'),
            vatIdentificationNumber:                 self::strOrNull($data, 'vatIdentificationNumber'),
            xRechnungLeitwegId:                      self::strOrNull($data, 'xRechnungLeitwegId'),
            factoring:                               self::bool($data, 'factoring'),
            commissionBlock:                         self::bool($data, 'commissionBlock'),
            competitor:                              self::bool($data, 'competitor'),
            formerSalesPartner:                      self::bool($data, 'formerSalesPartner'),
            habitualExporter:                        self::bool($data, 'habitualExporter'),
            salesPartner:                            self::bool($data, 'salesPartner'),
            salesPartnerDefaultCommissionFix:        self::strOrNull($data, 'salesPartnerDefaultCommissionFix'),
            salesPartnerDefaultCommissionPercentage: self::strOrNull($data, 'salesPartnerDefaultCommissionPercentage'),
            salesPartnerDefaultCommissionType:       self::strOrNull($data, 'salesPartnerDefaultCommissionType'),
            optInEmail:                              self::bool($data, 'optInEmail'),
            optInLetter:                             self::bool($data, 'optInLetter'),
            optInPhone:                              self::bool($data, 'optInPhone'),
            optInSms:                                self::bool($data, 'optInSms'),
            invoiceBlock:                            self::bool($data, 'invoiceBlock'),
            invoiceRecipientId:                      self::strOrNull($data, 'invoiceRecipientId'),
            deliveryEmailAddressesId:                self::strOrNull($data, 'deliveryEmailAddressesId'),
            dunningEmailAddressesId:                 self::strOrNull($data, 'dunningEmailAddressesId'),
            purchaseEmailAddressesId:                self::strOrNull($data, 'purchaseEmailAddressesId'),
            quotationEmailAddressesId:               self::strOrNull($data, 'quotationEmailAddressesId'),
            salesInvoiceEmailAddressesId:            self::strOrNull($data, 'salesInvoiceEmailAddressesId'),
            salesOrderEmailAddressesId:              self::strOrNull($data, 'salesOrderEmailAddressesId'),
            purchaseViaPlafond:                      self::bool($data, 'purchaseViaPlafond'),
            enableDropshippingInNewSupplySources:    self::bool($data, 'enableDropshippingInNewSupplySources'),
            publicPageUuid:                          self::strOrNull($data, 'publicPageUuid'),
            publicPageExpirationDate:                self::intOrNull($data, 'publicPageExpirationDate'),
            customer:                                self::bool($data, 'customer'),
            customerNumber:                          self::strOrNull($data, 'customerNumber'),
            customerNumberOld:                       self::strOrNull($data, 'customerNumberOld'),
            customerBlocked:                         self::bool($data, 'customerBlocked'),
            customerDeliveryBlock:                   self::bool($data, 'customerDeliveryBlock'),
            customerInsolvent:                       self::bool($data, 'customerInsolvent'),
            customerInsured:                         self::bool($data, 'customerInsured'),
            customerUseCustomsTariffNumber:          self::bool($data, 'customerUseCustomsTariffNumber'),
            customerAllowDropshippingOrderCreation:  self::bool($data, 'customerAllowDropshippingOrderCreation'),
            customerBusinessType:                    self::strOrNull($data, 'customerBusinessType'),
            customerCategoryId:                      self::strOrNull($data, 'customerCategoryId'),
            customerCreditLimit:                     self::strOrNull($data, 'customerCreditLimit'),
            customerAmountInsured:                   self::strOrNull($data, 'customerAmountInsured'),
            customerAnnualRevenue:                   self::strOrNull($data, 'customerAnnualRevenue'),
            customerBlockNotice:                     self::strOrNull($data, 'customerBlockNotice'),
            customerCurrentSalesStageId:             self::strOrNull($data, 'customerCurrentSalesStageId'),
            customerDebtorAccountId:                 self::strOrNull($data, 'customerDebtorAccountId'),
            customerDebtorAccountingCodeId:          self::strOrNull($data, 'customerDebtorAccountingCodeId'),
            customerDefaultHeaderDiscount:           self::strOrNull($data, 'customerDefaultHeaderDiscount'),
            customerDefaultHeaderSurcharge:          self::strOrNull($data, 'customerDefaultHeaderSurcharge'),
            customerDefaultShippingCarrierId:        self::strOrNull($data, 'customerDefaultShippingCarrierId'),
            customerDefaultWarehouseId:              self::strOrNull($data, 'customerDefaultWarehouseId'),
            customerInternalNote:                    self::strOrNull($data, 'customerInternalNote'),
            customerLossDescription:                 self::strOrNull($data, 'customerLossDescription'),
            customerLossReasonId:                    self::strOrNull($data, 'customerLossReasonId'),
            customerNonStandardTaxId:                self::strOrNull($data, 'customerNonStandardTaxId'),
            customerPaymentMethodId:                 self::strOrNull($data, 'customerPaymentMethodId'),
            customerSalesChannel:                    self::strOrNull($data, 'customerSalesChannel'),
            customerSalesOrderPaymentType:           self::strOrNull($data, 'customerSalesOrderPaymentType'),
            customerSalesProbability:                self::intOrNull($data, 'customerSalesProbability'),
            customerSatisfaction:                    self::strOrNull($data, 'customerSatisfaction'),
            customerShipmentMethodId:                self::strOrNull($data, 'customerShipmentMethodId'),
            customerSupplierNumber:                  self::strOrNull($data, 'customerSupplierNumber'),
            customerTermOfPaymentId:                 self::strOrNull($data, 'customerTermOfPaymentId'),
            supplier:                                self::bool($data, 'supplier'),
            supplierActive:                          self::bool($data, 'supplierActive'),
            supplierOrderBlock:                      self::bool($data, 'supplierOrderBlock'),
            supplierMergeItemsForOcrInvoiceUpload:   self::bool($data, 'supplierMergeItemsForOcrInvoiceUpload'),
            supplierNumber:                          self::strOrNull($data, 'supplierNumber'),
            supplierNumberOld:                       self::strOrNull($data, 'supplierNumberOld'),
            supplierCreditorAccountId:               self::strOrNull($data, 'supplierCreditorAccountId'),
            supplierCreditorAccountingCodeId:        self::strOrNull($data, 'supplierCreditorAccountingCodeId'),
            supplierCustomerNumberAtSupplier:        self::strOrNull($data, 'supplierCustomerNumberAtSupplier'),
            supplierDefaultShippingCarrierId:        self::strOrNull($data, 'supplierDefaultShippingCarrierId'),
            supplierInternalNote:                    self::strOrNull($data, 'supplierInternalNote'),
            supplierMinimumPurchaseOrderAmount:      self::strOrNull($data, 'supplierMinimumPurchaseOrderAmount'),
            supplierNonStandardTaxId:                self::strOrNull($data, 'supplierNonStandardTaxId'),
            supplierPaymentMethodId:                 self::strOrNull($data, 'supplierPaymentMethodId'),
            supplierShipmentMethodId:                self::strOrNull($data, 'supplierShipmentMethodId'),
            supplierTermOfPaymentId:                 self::strOrNull($data, 'supplierTermOfPaymentId'),
            addresses:                               array_map(
                static fn(array $i) => AddressDTO::fromArray($i),
                self::arr($data, 'addresses')
            ),
            bankAccounts:                            array_map(
                static fn(array $i) => BankAccountDTO::fromArray($i),
                self::arr($data, 'bankAccounts')
            ),
            onlineAccounts:                          array_map(
                static fn(array $i) => OnlineAccountDTO::fromArray($i),
                self::arr($data, 'onlineAccounts')
            ),
            commissionSalesPartners:                 array_map(
                static fn(array $i) => CommissionSalesPartnerDTO::fromArray($i),
                self::arr($data, 'commissionSalesPartners')
            ),
            partyHabitualExporterLettersOfIntent:    array_map(
                static fn(array $i) => PartyHabitualExporterLetterOfIntentDTO::fromArray($i),
                self::arr($data, 'partyHabitualExporterLettersOfIntent')
            ),
            customAttributes:                        array_map(
                static fn(array $i) => CustomAttributeDTO::fromArray($i),
                self::arr($data, 'customAttributes')
            ),
            contacts:                                self::arr($data, 'contacts'),
            partyEmailAddresses:                     self::arr($data, 'partyEmailAddresses'),
            customerSalesStageHistory:               self::arr($data, 'customerSalesStageHistory'),
            tags:                                    self::arr($data, 'tags'),
            topics:                                  self::arr($data, 'topics'),
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
     * Returns the date of birth as a DateTimeImmutable object.
     */
    public function getBirthDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['birthDate' => $this->birthDate], 'birthDate');
    }

    /**
     * Returns the lead conversion date as a DateTimeImmutable object.
     */
    public function getConvertedOnDate(): ?DateTimeImmutable
    {
        return self::dateFromEpochMs(['convertedOnDate' => $this->convertedOnDate], 'convertedOnDate');
    }

    /**
     * Returns the customer credit limit as a float, or null if not set.
     */
    public function getCustomerCreditLimit(): ?float
    {
        return $this->customerCreditLimit !== null ? (float) $this->customerCreditLimit : null;
    }

    /**
     * Returns the display name of the party.
     *
     * - ORGANIZATION → company name, falling back to customerNumber, supplierNumber,
     *                  then empty string.
     * - PERSON       → "firstName lastName", falling back to company (employer name),
     *                  then customerNumber or supplierNumber, then empty string.
     *
     * Uses partyType (not the presence of the company field) to distinguish the two
     * cases, because a PERSON party may have a non-null company field representing
     * their employer — returning that as the display name would be semantically wrong.
     */
    public function getDisplayName(): string
    {
        $numberFallback = $this->customerNumber ?? $this->supplierNumber ?? '';

        if ($this->partyType === 'ORGANIZATION') {
            return $this->company ?? $numberFallback;
        }

        $name = trim(implode(' ', array_filter([
            $this->firstName,
            $this->lastName,
        ])));

        return $name !== '' ? $name : ($this->company ?? $numberFallback);
    }

    /**
     * Returns true if this party is a customer (customer flag = true).
     */
    public function isCustomer(): bool
    {
        return $this->customer;
    }

    /**
     * Returns true if this party is a supplier (supplier flag = true).
     */
    public function isSupplier(): bool
    {
        return $this->supplier;
    }
}
