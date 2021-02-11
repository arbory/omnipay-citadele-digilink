<?php

namespace Omnipay\CitadeleDigilink;

use Omnipay\Tests\GatewayTestCase;
use Omnipay\CitadeleDigilink\Utils\Utils;

class GatewayTest extends GatewayTestCase
{
    /**
     * @var \Omnipay\CitadeleDigilink\Gateway
     */
    protected $gateway;

    /**
     * @var array
     */
    protected $options;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize([
            'merchantId'                => '1',
            'merchantLegalId'           => 9892,
            'merchantBankAccount'       => 'PAXXX0011',
            'merchantName'              => 'Some merchant',
            'merchantCountry'           => 'LT',
            'returnUrl'                 => 'http://localhost:8080/omnipay/citadele/',
            'privateCertificatePath'    => 'tests/Fixtures/key.pem',
            'publicCertificatePath'     => 'tests/Fixtures/key.pub',
            'bankCertificatePath' => 'tests/Fixtures/bank_key.pub',
        ]);

        $this->options = array(
            'transactionReference' => 'abc123',
            'description'          => 'purchase description',
            'amount'               => '10.00',
            'currency'             => 'EUR',
        );
    }

    public function testPurchaseSuccess()
    {
        $response = $this->gateway->purchase($this->options)->send();

        $this->assertInstanceOf('\Omnipay\CitadeleDigilink\Messages\PurchaseResponse', $response);
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertTrue($response->isTransparentRedirect());
        $this->assertEquals('POST', $response->getRedirectMethod());
        $this->assertEquals('https://online.citadele.lv/amai/start.htm', $response->getRedirectUrl());

        $this->assertEquals(['xmldata'], array_keys($response->getData()));
        $this->assertEquals($response->getData(), $response->getRedirectData());

        $expectedRequestXmlValues = [
            'Header' => [
                'Timestamp' => $response->getRequest()->getTimestamp(),
                'From' => 1,
                'Extension' => [
                    'Amai' => [
                        'Request' => 'PMTREQ',
                        'RequestUID' => 'abc123',
                        'Version' => '5.0',
                        'Language' => 'LV',
                        'ReturnURL' => 'http://localhost:8080/omnipay/citadele/',
                        // all actual signature data is stored in namespaced nodes which
                        // our XMLToArray parser is skipping
                        'SignatureData' => [],
                        'PaymentRequest' => [
                            'ExtId' => 'abc123',
                            'DocNo' => 'abc123',
                            'TaxPmtFlg' => 'N',
                            'Ccy' => 'EUR',
                            'PmtInfo' => 'purchase description',
                            'BenSet' => [
                                'Priority' => 'N',
                                'Comm' => 'OUR',
                                'Amt' => '10.00',
                                'BenAccNo' => 'PAXXX0011',
                                'BenName' => 'Some merchant',
                                'BenLegalId' => '9892',
                                'BenCountry' => 'LT'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertTrue(Utils::verifyXMLSignature(
            $response->getData()['xmldata'],
            $this->gateway->getPublicCertificatePath()
        ));
        $this->assertEquals($expectedRequestXmlValues, Utils::XMLToArray($response->getData()['xmldata']));
    }

    public function testPurchaMissingConfiguration()
    {
        // initiate new unconfigured gateway
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('The merchantId parameter is required');

        $response = $this->gateway->purchase($this->options)->send();
    }

    public function testPurchaseCompletePendingRegularRequest()
    {
        // build PMTRESP xml
        $xml = Utils::getTemplate('PMTRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'RequestUID' => $this->options['transactionReference'],
            'Version' => Gateway::VERSION,
            'Code' => '100',
        ]);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertTrue($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isServerToServerRequest());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isCancelled());
        $this->assertSame('abc123', $response->getTransactionReference());
        $this->assertSame('Payment is processing', $response->getMessage());
    }

    public function testPurchaseCompleteCanceledRegularRequest()
    {
        // build PMTRESP xml
        $xml = Utils::getTemplate('PMTRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'RequestUID' => $this->options['transactionReference'],
            'Version' => Gateway::VERSION,
            'Code' => '200',
        ]);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isServerToServerRequest());
        $this->assertFalse($response->isRedirect());
        $this->assertTrue($response->isCancelled());
        $this->assertSame('abc123', $response->getTransactionReference());
        $this->assertSame('Payment has been canceled', $response->getMessage());
    }

    public function testPurchaseCompleteErrorRegularRequest()
    {
        // build PMTRESP xml
        $xml = Utils::getTemplate('PMTRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'RequestUID' => $this->options['transactionReference'],
            'Code' => '300',
            'Version' => Gateway::VERSION,
            'Message' => 'no electricity',
        ]);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isServerToServerRequest());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isCancelled());
        $this->assertSame('abc123', $response->getTransactionReference());
        $this->assertSame('Bank internal error: no electricity', $response->getMessage());
    }

    public function testPurchaseCompleteErrorWithoutDescriptionRegularRequest()
    {
        // build PMTRESP xml
        $xml = Utils::getTemplate('PMTRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'RequestUID' => $this->options['transactionReference'],
            'Code' => '300',
            'Version' => Gateway::VERSION,
            'Message' => '',
        ]);

        // remove Messsage element as in some cases there are no Message node
        $xml = str_replace('<Message></Message>', '', $xml);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isServerToServerRequest());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isCancelled());
        $this->assertSame('abc123', $response->getTransactionReference());
        $this->assertSame('Bank internal error', $response->getMessage());
    }


    public function testPurchaseCompleteSucessStatusRequest()
    {
        // build PMTSTATRESP xml
        $xml = Utils::getTemplate('PMTSTATRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'ExtId' => $this->options['transactionReference'],
            'DocNo' => $this->options['transactionReference'],
            'Version' => Gateway::VERSION,
            'StatCode' => 'E'
        ]);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isPending());
        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isServerToServerRequest());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isCancelled());
        $this->assertSame('abc123', $response->getTransactionReference());
        $this->assertSame('Payment was successful', $response->getMessage());
    }

    public function testPurchaseCompleteCanceledStatusRequest()
    {
        // build PMTSTATRESP xml
        $xml = Utils::getTemplate('PMTSTATRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'ExtId' => $this->options['transactionReference'],
            'DocNo' => $this->options['transactionReference'],
            'Version' => Gateway::VERSION,
            'StatCode' => 'R'
        ]);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isServerToServerRequest());
        $this->assertFalse($response->isRedirect());
        $this->assertTrue($response->isCancelled());
        $this->assertSame('abc123', $response->getTransactionReference());
        $this->assertSame('Payment has been canceled', $response->getMessage());
    }

    public function testPurchaseCompleteFailedWithInvalidResponseType()
    {
        // build PMTSTATRESP xml
        $xml = Utils::getTemplate('PMTSTATRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'ExtId' => $this->options['transactionReference'],
            'DocNo' => $this->options['transactionReference'],
            'Version' => Gateway::VERSION,
            'StatCode' => 'R'
        ]);

        // put some unsupported response type
        $xml = str_replace('PMTSTATRESP', 'AUTHRESP', $xml);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid response type');

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();
    }

    public function testPurchaseCompleteFailedWithInvalidTimestamp()
    {
        // build PMTSTATRESP xml
        $xml = Utils::getTemplate('PMTSTATRESP', [
            // put timestamp exact 1 second past allowed timeout (15 minutes)
            'Timestamp' => substr(date('YmdHisu', time() - ((15 * 60) + 1)), 0, 17),
            'ExtId' => $this->options['transactionReference'],
            'DocNo' => $this->options['transactionReference'],
            'Version' => Gateway::VERSION,
            'StatCode' => 'R'
        ]);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // replace bank certificate with our public certificate
        $this->gateway->setBankCertificatePath($this->gateway->getPublicCertificatePath());

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('Timestamp exceed allowed timeout (15 minutes)');

        // perform actual request
        $response = $this->gateway->completePurchase($this->options)->send();
    }

    public function testPurchaseCompleteFailedWithInvalidSignature()
    {
        // build PMTSTATRESP xml
        $xml = Utils::getTemplate('PMTSTATRESP', [
            'Timestamp' => substr(date('YmdHisu'), 0, 17),
            'ExtId' => $this->options['transactionReference'],
            'DocNo' => $this->options['transactionReference'],
            'Version' => Gateway::VERSION,
            'StatCode' => 'R'
        ]);

        $signedXML = Utils::signXML(
            $xml,
            $this->gateway->getPrivateCertificatePath(),
            $this->gateway->getPublicCertificatePath()
        );

        // simulate post request
        $postData = array(
            'xmldata' => $signedXML
        );
        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);


        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('Data is corrupt or has been changed by a third party');

        // simulate post request
        $response = $this->gateway->completePurchase($this->options)->send();
    }

    public function testPurchaseCompleteFailedWithInvalidRequest()
    {
        $postData = array(
            'some_param' => 'x',
        );

        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('Missing xmldata value');

        $response = $this->gateway->completePurchase($this->options)->send();
    }

    public function testPurchaseCompleteFailedWithInvalidRequestContent()
    {
        $postData = array(
            'xmldata' => '<xml.../.',
        );

        $this->getHttpRequest()->setMethod('POST');
        $this->getHttpRequest()->request->replace($postData);

        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid xml');

        $response = $this->gateway->completePurchase($this->options)->send();
    }
}
