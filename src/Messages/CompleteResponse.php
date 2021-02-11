<?php

namespace Omnipay\CitadeleDigilink\Messages;

use Symfony\Component\HttpFoundation\ParameterBag;
use Omnipay\CitadeleDigilink\Gateway;

class CompleteResponse extends AbstractResponse
{
    public const STATUS_REJECTED  = 'R';
    public const STATUS_EXECUTED  = 'E';
    public const STATUS_PROCESSED = '100';
    public const STATUS_CANCELED  = '200';
    public const STATUS_ERRORED   = '300';

    /**
     * @return string
     */
    public function getTransactionReference(): string
    {
        if ($this->isConfirmationMessage()) {
            return $this->data['Header']['Extension']['Amai']['RequestUID'];
        }

        // assume status request otherwise
        return $this->data['PmtStat']['ExtId'];
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        // Important! PMTRESP with the code 100 does not confirm successful payment processing.
        // This means that customer has confirmed the payment and it is delivered to the Bank's system for
        // further processing. The final status of the payment (executed, rejected) is provided
        // with PMTSTATRESP message.

        return ($this->isStatusMessage() && $this->data['PmtStat']['StatCode'] === self::STATUS_EXECUTED);
    }

    /**
     * @return bool
     */
    protected function isConfirmationMessage(): bool
    {
        return $this->isMessageType(Gateway::PAYMENT_CONFIRMATION_MESSAGE);
    }

    /**
     * @return bool
     */
    protected function isStatusMessage(): bool
    {
        return $this->isMessageType(Gateway::PAYMENT_STATUS_MESSAGE);
    }

    /**
     * @return bool
     */
    protected function isMessageType(string $type): bool
    {
        return ($this->data['Header']['Extension']['Amai']['Request'] === $type);
    }

    /**
     * Checks if transaction has been canceled
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        if ($this->isConfirmationMessage() &&
            $this->data['Header']['Extension']['Amai']['Code'] === self::STATUS_CANCELED) {
            return true;
        } elseif ($this->isStatusMessage() && $this->data['PmtStat']['StatCode'] === self::STATUS_REJECTED) {
            return true;
        }

        return false;
    }

    public function getMessage()
    {
        $message = '';

        if ($this->isSuccessful()) {
            $message = 'Payment was successful';
        } elseif ($this->isPending()) {
            $message = 'Payment is processing';
        } elseif ($this->isCancelled()) {
            return 'Payment has been canceled';
        } elseif ($this->isConfirmationMessage() &&
            $this->data['Header']['Extension']['Amai']['Code'] === self::STATUS_ERRORED) {
            $message = 'Bank internal error';

            if (isset($this->data['Header']['Extension']['Amai']['Message'])) {
                $message .= ': ' . $this->data['Header']['Extension']['Amai']['Message'];
            }
        }

        return $message;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return ($this->isConfirmationMessage() &&
            $this->data['Header']['Extension']['Amai']['Code'] === self::STATUS_PROCESSED);
    }

    /**
     * @return bool
     */
    public function isServerToServerRequest(): bool
    {
        return $this->isStatusMessage();
    }
}
