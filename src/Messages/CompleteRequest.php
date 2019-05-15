<?php

namespace Omnipay\CitadeleDigilink\Messages;

use DOMDocument;
use DateTime;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\CitadeleDigilink\Utils\Utils;
use Omnipay\CitadeleDigilink\Gateway;

class CompleteRequest extends AbstractRequest
{
    public const MAX_RESPONSE_TIMEOUT = 15; // value in minutes

    public function getData()
    {
        return $this->httpRequest->request->all();
    }

    /*
     * Faking sending flow
     */
    public function createResponse(array $data)
    {
        // Read data from request object
        return $purchaseResponseObj = new CompleteResponse($this, $data);
    }

    /**
     * @param mixed $data
     * @return \Omnipay\Common\Message\ResponseInterface|AbstractResponse|CompleteResponse
     */
    public function sendData($data)
    {
        //Validate response data before we process further
        $this->validate();

        // Create fake response flow
        /** @var CompleteResponse $purchaseResponseObj */
        $response = $this->createResponse(Utils::XMLToArray($data['xmldata']));

        return $response;
    }

    public function validate()
    {
        $response = $this->getData();

        // check for xmldata itself
        if (!isset($response['xmldata'])) {
            throw new InvalidRequestException('Missing xmldata value');
        }

        $xml = new DOMDocument;
        //$xml->formatOutput = true;

        // validate xml
        if (!$xml->loadXML($response['xmldata'], LIBXML_NOERROR)) {
            throw new InvalidRequestException('Invalid xml');
        }

        // validate response type
        if (!$this->isValidResponseType($xml)) {
            throw new InvalidRequestException('Invalid response type');
        }

        if (!$this->isValidTimestamp($xml)) {
            throw new InvalidRequestException('Timestamp exceed allowed timeout (' .
                self::MAX_RESPONSE_TIMEOUT . ' minutes)');
        }

        // validate integrity
        if (!Utils::verifyXMLSignature($response['xmldata'], $this->getBankCertificatePath())) {
            throw new InvalidRequestException('Data is corrupt or has been changed by a third party');
        }
    }

    /**
     * @return bool
     */
    protected function isValidTimestamp($xml): bool
    {
        $timestamp = $xml->getElementsByTagName('Timestamp')->item(0)->nodeValue;
        $date = DateTime::createFromFormat(Gateway::TIMESTAMP_FORMAT, $timestamp);

        return (time() - $date->getTimestamp()) <= self::MAX_RESPONSE_TIMEOUT * 60;
    }

    /**
     * @return bool
     */
    protected function isValidResponseType($xml): bool
    {
        $type = $xml->getElementsByTagName('Request')->item(0)->nodeValue;

        return in_array($type, $this->allowedResponseMessageTypes());
    }

    protected function allowedResponseMessageTypes(): array
    {
        return [Gateway::PAYMENT_CONFIRMATION_MESSAGE, Gateway::PAYMENT_STATUS_MESSAGE];
    }

    /**
     * @param $value
     */
    public function setMerchantId($value)
    {
        $this->setParameter('merchantId', $value);
    }
}
