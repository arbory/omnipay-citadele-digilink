<?php

namespace Omnipay\CitadeleDigilink\Messages;

use Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;

abstract class AbstractRequest extends CommonAbstractRequest
{
    /**
     * @var string
     */
    protected $testServerEndpoint = 'https://astra.citadele.lv/amai/start.htm';

    /**
     * @var string
     */
    protected $liveServerEndpoint = 'https://online.citadele.lv/amai/start.htm';

    /**
     * @return string
     */
    public function getGatewayUrl(): string
    {
        return $this->getTestMode() ? $this->testServerEndpoint : $this->liveServerEndpoint;
    }

    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->getParameter('returnUrl');
    }

    /**
     * @param mixed $returnUrl
     */
    public function setReturnUrl($value)
    {
        return $this->setParameter('returnUrl', $value);
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
    public function setBankCertificatePath($value)
    {
        return $this->setParameter('bankCertificatePath', $value);
    }

    /**
     * @return string
     */
    public function getBankCertificatePath()
    {
        return $this->getParameter('bankCertificatePath');
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
     * @return CommonAbstractRequest
     */
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    /**
     * @param string $value
     */
    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    /**
     * @param string $value
     */
    public function setMerchantLegalId($value)
    {
        return $this->setParameter('merchantLegalId', $value);
    }

    /**
     * @return string
     */
    public function getMerchantLegalId()
    {
        return $this->getParameter('merchantLegalId');
    }

    /**
     * @param string $value
     */
    public function setMerchantName($value)
    {
        return $this->setParameter('merchantName', $value);
    }

    /**
     * @return string
     */
    public function getMerchantName()
    {
        return $this->getParameter('merchantName');
    }

    /**
     * @param string $value
     */
    public function setMerchantBankAccount($value)
    {
        return $this->setParameter('merchantBankAccount', $value);
    }

    /**
     * @return mixed
     */
    public function getMerchantBankAccount()
    {
        return $this->getParameter('merchantBankAccount');
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
}
