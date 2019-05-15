<?php

namespace Omnipay\CitadeleDigilink\Utils;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecEnc;
use DOMDocument;
use Exception;

/**
 * Class Gateway
 *
 * @package Omnipay\CitadeleDigilink
 */
class Utils
{
    /**
     * @param string $value
     * @return $this
     */
    public static function signXML(string $xml, string $privateKeyPath, string $publicKeyPath): string
    {
        $doc = new DOMDocument();
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($xml);

        $objDSig = new XMLSecurityDSig();

        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        $objDSig->addReference(
            $doc,
            XMLSecurityDSig::SHA1,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
            array('force_uri' => true,)
        );

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array( 'type' => 'private'));
        $objKey->loadKey($privateKeyPath, true);

        $appendTo = $doc->getElementsByTagName('SignatureData')->item(0);
        $objDSig->sign($objKey, $appendTo);

        $objDSig->add509Cert(file_get_contents($publicKeyPath));

        return $doc->saveXML();
    }

    /**
     * @return string
     */
    public static function verifyXMLSignature(string $xml, string $signingCertificatePath) : bool
    {
        $doc = new DOMDocument;
        $doc->loadXML($xml);

        /* Verifying response signature */
        $objXMLSecDSig = new XMLSecurityDSig();

        $objDSig = $objXMLSecDSig->locateSignature($doc);
        if (!$objDSig) {
            throw new Exception('Cannot locate signature node');
        }

        $objXMLSecDSig->canonicalizeSignedInfo();
        $objXMLSecDSig->idKeys = ['wsu:Id'];
        $objXMLSecDSig->idNS = array(
            'wsu'=>'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd'
        );

        $objKey = $objXMLSecDSig->locateKey();
        if (!$objKey) {
            throw new Exception('We have no idea about the key');
        }

        $objKeyInfo = XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);
        $objKey->loadKey($signingCertificatePath, true, true);
        $result = $objXMLSecDSig->verify($objKey);

        return ($result === 1);
    }

    public static function XMLToArray($xml): array
    {
        $parsedXml = simplexml_load_string($xml);
        $json = json_encode($parsedXml);
        return json_decode($json, true);
    }

    public static function getTemplate(string $templateName, array $variables): string
    {
        $templatePath = dirname(__DIR__) .  '/Templates/' . $templateName . '.xml';

        extract($variables);

        ob_start();

        include $templatePath;

        return ob_get_clean();
    }
}
