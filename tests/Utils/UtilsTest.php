<?php

namespace Omnipay\CitadeleDigilink\Utils;

use Omnipay\CitadeleDigilink\Gateway;
use Omnipay\Tests\TestCase;

class UtilsTest extends TestCase
{
    public function testSignXML()
    {
        $privateCertPath = 'tests/Fixtures/key.pem';
        $publicKeyPath = 'tests/Fixtures/key.pub';
        $xml = $this->sampleXML();
        $signedXML = Utils::signXML($xml, $privateCertPath, $publicKeyPath);

        // compare just by md5 hash here for simplicity
        $this->assertSame('989f90c9dc85194c43692b3eeff9f5b4', md5($signedXML));
    }

    public function testVerifyXMLSignature()
    {
        $privateCertPath = 'tests/Fixtures/key.pem';
        $publicKeyPath = 'tests/Fixtures/key.pub';
        $xml = $this->sampleXML();
        $signedXML = Utils::signXML($xml, $privateCertPath, $publicKeyPath);

        $this->assertTrue(Utils::verifyXMLSignature($signedXML, $publicKeyPath));
    }

    public function testVerifyXMLSignatureWithMissingSignature()
    {
        $privateCertPath = 'tests/Fixtures/key.pem';
        $publicKeyPath = 'tests/Fixtures/key.pub';
        $xml = $this->sampleXML();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot locate signature node');
        $this->assertTrue(Utils::verifyXMLSignature($xml, $publicKeyPath));
    }

    public function testVerifyXMLSignatureWithMissingKey()
    {
        $privateCertPath = 'tests/Fixtures/key.pem';
        $publicKeyPath = 'tests/Fixtures/key.pub';
        $xml = $this->sampleXML();
        $signedXML = Utils::signXML($xml, $privateCertPath, $publicKeyPath);
        $invalidSignedXML = str_replace('Algorithm', 'x', $signedXML);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('We have no idea about the key');
        $this->assertTrue(Utils::verifyXMLSignature($invalidSignedXML, $publicKeyPath));
    }

    public function testXMLToArray()
    {
        $xml = $this->sampleXML();

        $expectedResult = [
            'Header' => [
                'Timestamp' => '20190508064941000',
                'From' => '1',
                'Extension' => [
                    'Amai' => [
                        'Request' => 'PMTSTATRESP',
                        'Version' => '5.0',
                        'SignatureData' => []
                    ]
                ]
            ],
           'PmtStat' => [
                'ExtId' => 'xx',
                'DocNo' => 'xx',
                'StatCode' => 'R',
                'BookDate' => '2019-05-08',
                'Extension' => [
                    'AccNo' => 'LV12PARX12345443233',
                    'Name' => 'John Doe'
                ]
            ]
        ];

        $this->assertSame($expectedResult, Utils::XMLToArray($xml));
    }

    public function sampleXML(): string
    {
        return Utils::getTemplate('PMTSTATRESP', [
            'Timestamp' => '20190508064941000',
            'ExtId' => 'xx',
            'DocNo' => 'xx',
            'Version' => Gateway::VERSION,
            'StatCode' => 'R'
        ]);
    }
}
