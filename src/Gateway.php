<?php

namespace Omnipay\CitadeleDigilink;

use Omnipay\Common\AbstractGateway;
use Omnipay\CitadeleDigilink\Messages\PurchaseRequest;
use Omnipay\CitadeleDigilink\Messages\CompleteRequest;

/**
 * Class Gateway
 *
 * @package Omnipay\CitadeleDigilink
 */
class Gateway extends AbstractGateway
{
    public const PAYMENT_REQUEST_MESSAGE      = 'PMTREQ';
    public const PAYMENT_CONFIRMATION_MESSAGE = 'PMTRESP';
    public const PAYMENT_STATUS_MESSAGE       = 'PMTSTATRESP';
    public const TIMESTAMP_FORMAT             = 'YmdHisu';
    public const VERSION                      = '5.0';

    /**
     * @return string
     */
    public function getName()
    {
        return 'Citadele Digilink';
    }

    /**
     * @return array
     */
    public function getDefaultParameters()
    {
        return array(
            'merchantCountry'           => 'LV',
            'language'                  => 'LV',
            'testMode'                  => false
        );
    }

    /**
     * @param array $options
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function purchase(array $options = [])
    {
        return $this->createRequest(PurchaseRequest::class, $options);
    }

    /**
     * Complete transaction
     * @param array $options
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function completePurchase(array $options = [])
    {
        return $this->createRequest(CompleteRequest::class, $options);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setReturnUrl($value)
    {
        return $this->setParameter('returnUrl', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPrivateCertificatePath($value)
    {
        return $this->setParameter('privateCertificatePath', $value);
    }

    /**
     * @return string
     */
    public function getPrivateCertificatePath()
    {
        return $this->getParameter('privateCertificatePath');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPublicCertificatePath($value)
    {
        return $this->setParameter('publicCertificatePath', $value);
    }

    /**
     * @return string
     */
    public function getPublicCertificatePath()
    {
        return $this->getParameter('publicCertificatePath');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setBankCertificatePath($value)
    {
        return $this->setParameter('bankCertificatePath', $value);
    }

    /**
     * @param string $value
     */
    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    /**
     * @param string $value
     */
    public function setMerchantLegalId($value)
    {
        return $this->setParameter('merchantLegalId', $value);
    }

    /**
     * @param string $value
     */
    public function setMerchantName($value)
    {
        return $this->setParameter('merchantName', $value);
    }

    /**
     * @param string $value
     */
    public function setMerchantBankAccount($value)
    {
        return $this->setParameter('merchantBankAccount', $value);
    }

    /**
     * @param string $value
     */
    public function setMerchantCountry($value)
    {
        return $this->setParameter('merchantCountry', $value);
    }

    /**
     * @return string
     */
    public function getMerchantCountry()
    {
        return $this->getParameter('merchantCountry');
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    /**
     * @param $value
     */
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }
}
