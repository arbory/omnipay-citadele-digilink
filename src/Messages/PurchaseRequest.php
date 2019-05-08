<?php

namespace Omnipay\CitadeleDigilink\Messages;

use Omnipay\CitadeleDigilink\Gateway;
use Omnipay\CitadeleDigilink\Utils\Utils;

class PurchaseRequest extends AbstractRequest
{
    /**
     * @var string
     */
    protected $timestamp;

    private function getXMLData()
    {
        $data = [
            'Timestamp'   => $this->getTimestamp(),
            'From'        => $this->getMerchantId(),
            'RequestUID'  => $this->getTransactionReference(),
            'ReturnURL'   => $this->getReturnUrl(),
            'BenAccNo'    => $this->getMerchantBankAccount(),
            'BenName'     => $this->getMerchantName(),
            'BenLegalId'  => $this->getMerchantLegalId(),
            'BenCountry'  => $this->getMerchantCountry(),
            'PmtInfo'     => $this->getDescription(),
            'Amt'         => $this->getAmount(),
            'Ccy'         => $this->getCurrency(),
            'Language'    => strtoupper($this->getLanguage())
        ];

        return $data;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        $this->timestamp = $this->timestamp ?? substr(date(Gateway::TIMESTAMP_FORMAT), 0, 17);

        return $this->timestamp;
    }

    /**
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    private function getXML()
    {
        $data = $this->getXMLData();
        $xml = Utils::getTemplate('PMTREQ', $data);
        $signedXML = Utils::signXML($xml, $this->getPrivateCertificatePath(), $this->getPublicCertificatePath());

        return $signedXML;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return ['xmldata' => $this->getXML()];
    }

    /**
     * @param mixed $data
     * @return \Omnipay\Common\Message\ResponseInterface|PurchaseResponse
     */
    public function sendData($data)
    {
        // Create fake response flow, so that user can be redirected
        /** @var AbstractResponse $purchaseResponseObj */
        return new PurchaseResponse($this, $data);
    }
}
