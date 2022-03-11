<?php

namespace RZP\Gateway\P2p\Upi\Npci;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Mandate;
use RZP\Models\P2p\Transaction;
use RZP\Models\P2p\BankAccount;
use RZP\Gateway\P2p\Base\Request;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * Base version of NPCI Library which is V_1_5 (1.5)
 * Class Cl
 *
 * @package RZP\Gateway\P2p\Upi\Npci
 */
class Cl
{
    /** @var ArrayBag */
    protected $handle;

    /** @var ArrayBag */
    protected $data;

    /**
     * @var string
     */
    protected $getCredentialAction;

    public function __construct(ArrayBag $handle, array $input)
    {
        $this->handle = $handle;
        $this->data   = new ArrayBag();

        $this->setData($input);
    }

    public function setData(array $input)
    {
        $allowed = array_only($input, array_keys(ClInput::$allowed));

        // We can run validation on allowed

        $this->data->putMany($allowed);
    }

    /**
     * Whether the CL token should be registered or rotated
     *
     * @return bool
     */
    public function shouldRegisterApp(): bool
    {
        return ($this->shouldRegisterToken() or $this->shouldRotateToken());
    }

    /**
     * Whether the CL was ever registered for the device
     *
     * @return bool
     */
    public function shouldRegisterToken(): bool
    {
        // When any of token or expiry is empty
        return (empty($this->data->get(ClInput::CL_TOKEN)) or
                empty($this->data->get(ClInput::CL_EXPIRY)));
    }

    /**
     * Whether the CL token is expiring for the device
     *
     * @return bool
     */
    public function shouldRotateToken(): bool
    {
        $expiry = (int) $this->data->get(ClInput::CL_EXPIRY);

        // if going to expire in next 60 seconds
        return (Carbon::now()->addSeconds(60)->getTimestamp() >= $expiry);
    }

    /**
     * Device registration request
     *
     * @return Request
     */
    public function registerRequest(): Request
    {
        $request = $this->request(ClAction::GET_CHALLENGE);

        $request->setContent([
                                 ClOutput::VECTOR => [
                                     ClOutput::INITIAL,
                                     $this->data->get(ClInput::DEVICE_ID),
                                 ],
                                 ClOutput::COUNT  => 2,
                             ]);

        $request->setCallback([
                                  ClOutput::TYPE => ClOutput::INITIAL,
                              ]);

        return $request;
    }

    private function request(string $action): Request
    {
        $request = new Request();

        $request->setSdk(ClOutput::NPCI);
        $request->setAction($action);

        return $request;
    }

    /**
     * Device rotation request
     *
     * @return Request
     */
    public function rotateRequest(): Request
    {
        $request = $this->request(ClAction::GET_CHALLENGE);

        $request->setContent([
                                 ClOutput::VECTOR => [
                                     ClOutput::ROTATE,
                                     $this->data->get(ClInput::DEVICE_ID),
                                 ],
                                 ClOutput::COUNT  => 2,
                             ]);

        $request->setCallback([
                                  ClOutput::TYPE => ClOutput::ROTATE,
                              ]);

        return $request;
    }

    /**
     * App registration request
     *
     * @return Request
     */
    public function registerAppRequest(): Request
    {
        $request = $this->request(ClAction::REGISTER_APP);

        $request->setContent([
                                 ClOutput::VECTOR => [
                                     $this->data->get(ClInput::APP_ID),
                                     $this->data->get(ClInput::MOBILE_NUMBER),
                                     $this->data->get(ClInput::DEVICE_ID),
                                     $this->generateHmac(),
                                 ],
                                 ClOutput::COUNT  => 4,
                             ]);

        $request->setCallback([
                                  ClOutput::TOKEN  => $this->data->get(ClInput::CL_TOKEN),
                                  ClOutput::EXPIRY => Carbon::now()->addDays(45)->getTimestamp(),
                              ]);

        return $request;
    }

    private function generateHmac()
    {
        $token = $this->data->get(ClInput::CL_TOKEN);

        $string = $this->data->get(ClInput::APP_ID) . '|' .
                  $this->data->get(ClInput::MOBILE_NUMBER) . '|' .
                  $this->data->get(ClInput::DEVICE_ID);

        $hash = hash('sha256', $string);

        $encrypted = (new ClCrypto($token))->encryptAes256($hash);

        return $encrypted;
    }

    public function getCredentialRequest(string $action): Request
    {
        $this->getCredentialAction = $action;

        $request = $this->request(ClAction::GET_CREDENTIAL);

        $request->setContent([
                                 ClOutput::VECTOR => [
                                     $this->getCredKeyCode(),
                                     $this->getCredXmlPayload(),
                                     $this->getCredControls(),
                                     $this->getCredConfiguration($action),
                                     $this->getCredSalt($action),
                                     $this->getCredTrust(),
                                     $this->getCredPayInfo(),
                                     $this->getCredLanguagePref(),
                                 ],
                                 ClOutput::COUNT  => 8,
                             ]);

        return $request;
    }

    // Get Credentials Methods

    private function getCredKeyCode()
    {
        return 'NPCI';
    }

    private function getCredXmlPayload()
    {
        return '<xml></xml>';
    }

    private function getCredControls()
    {
        $creds = $this->data->get(ClInput::BANK_ACCOUNT)[BankAccount\Entity::CREDS] ?? [];

        $transformed = array_map(function($cred) {
            if (empty($cred[BankAccount\Credentials::SET]) === false)
            {
                return [
                    ClOutput::TYPE     => $cred[BankAccount\Credentials::TYPE],
                    ClOutput::SUB_TYPE => $cred[BankAccount\Credentials::SUB_TYPE],
                    ClOutput::DTYPE    => $cred[BankAccount\Credentials::FORMAT],
                    ClOutput::DLENGTH  => $cred[BankAccount\Credentials::LENGTH],
                ];
            }
        }, $creds);

        return [
            // CredAllowed is sequential array, thus using array_values
            ClOutput::CRED_ALLOWED => array_values(array_filter($transformed)),
        ];
    }

    private function getCredConfiguration(string $type)
    {
        switch ($type)
        {
            case ClAction::RECURRING_DEBIT:
                return [
                    // NOTE: Not needed for actual npci integration
                    'txnId'  => $this->data->get(ClInput::MANDATE)[Mandate\Entity::ID],
                    'action' => $this->getCredentialAction,
                ];
            default:
                return [
                    // NOTE: Not needed for actual npci integration
                    'txnId'  => $this->data->get(ClInput::TXN_ID),
                    'action' => $this->getCredentialAction,
                ];
        }
    }

    private function getCredSalt(string $type)
    {
        switch ($type)
        {
            case ClAction::RECURRING_DEBIT:
                // Order is important thus we declare first
                $salt = [
                    ClOutput::MANDATE_ID     => $this->data->get(ClInput::MANDATE)[Mandate\Entity::ID],
                    ClOutput::MANDATE_AMOUNT => null,
                    ClOutput::DEVICE_ID      => $this->data->get(ClInput::DEVICE_ID),
                    ClOutput::APP_ID         => $this->data->get(ClInput::APP_ID),
                    ClOutput::MOBILE_NUMBER  => $this->data->get(ClInput::MOBILE_NUMBER),
                    ClOutput::PAYER_ADDR     => null,
                    ClOutput::PAYEE_ADDR     => null,
                ];

                if ($this->getCredentialAction === ClAction::RECURRING_DEBIT)
                {
                    $txnId                  = $this->data->get(Mandate\Entity::UPI)[UpiMandate\Entity::NETWORK_TRANSACTION_ID];
                    $salt[ClOutput::TXN_ID] = $txnId;

                    $amount                     = $this->data->get(ClInput::MANDATE)[Mandate\Entity::AMOUNT];
                    $salt[ClOutput::TXN_AMOUNT] = amount_format_IN($amount);

                    $payerVpa                   = $this->data->get(ClInput::PAYER)[Vpa\Entity::ADDRESS];
                    $salt[ClOutput::PAYER_ADDR] = $payerVpa;

                    $payeeVpa                   = $this->data->get(ClInput::PAYEE)[Vpa\Entity::ADDRESS];
                    $salt[ClOutput::PAYEE_ADDR] = $payeeVpa;

                    $salt[ClOutput::CRED_TYPE] = ClInput::MANDATE;
                }

                return array_filter($salt);

            default:
                $salt = [
                    ClOutput::TXN_ID        => $this->data->get(ClInput::TXN_ID),
                    ClOutput::TXN_AMOUNT    => null,
                    ClOutput::DEVICE_ID     => $this->data->get(ClInput::DEVICE_ID),
                    ClOutput::APP_ID        => $this->data->get(ClInput::APP_ID),
                    ClOutput::MOBILE_NUMBER => $this->data->get(ClInput::MOBILE_NUMBER),
                    ClOutput::PAYER_ADDR    => null,
                    ClOutput::PAYEE_ADDR    => null,
                ];

                if ($this->getCredentialAction === ClAction::DEBIT)
                {
                    $txnId                  = $this->data->get(ClInput::UPI)[Transaction\UpiTransaction\Entity::NETWORK_TRANSACTION_ID];
                    $salt[ClOutput::TXN_ID] = $txnId;

                    $amount                     = $this->data->get(ClInput::TRANSACTION)[Transaction\Entity::AMOUNT];
                    $salt[ClOutput::TXN_AMOUNT] = amount_format_IN($amount);

                    $payerVpa                   = $this->data->get(ClInput::PAYER)[Vpa\Entity::ADDRESS];
                    $salt[ClOutput::PAYER_ADDR] = $payerVpa;

                    $payeeVpa                   = $this->data->get(ClInput::PAYEE)[Vpa\Entity::ADDRESS];
                    $salt[ClOutput::PAYEE_ADDR] = $payeeVpa;
                }

                return array_filter($salt);
        }

    }

    private function getCredTrust()
    {
        return $this->generateHmac();
    }

    private function getCredPayInfo()
    {
        $info = [];

        if ($this->getCredentialAction === ClAction::DEBIT)
        {
            $value  = $this->data->get(ClInput::PAYEE)[Vpa\Entity::BENEFICIARY_NAME];
            $input  = [
                ClOutput::NAME  => ClOutput::PAYEE_NAME,
                ClOutput::VALUE => $value,
            ];
            $info[] = $input;

            $value  = $this->data->get(ClInput::TRANSACTION)[Transaction\Entity::DESCRIPTION];
            $input  = [
                ClOutput::NAME  => ClOutput::NOTE,
                ClOutput::VALUE => $value,
            ];
            $info[] = $input;

            $value  = $this->data->get(ClInput::UPI)[Transaction\UpiTransaction\Entity::REF_ID];
            $input  = [
                ClOutput::NAME  => ClOutput::REF_ID,
                ClOutput::VALUE => $value,
            ];
            $info[] = $input;

            $value  = $this->data->get(ClInput::UPI)[Transaction\UpiTransaction\Entity::REF_URL];
            $input  = [
                ClOutput::NAME  => ClOutput::REF_URL,
                ClOutput::VALUE => $value,
            ];
            $info[] = $input;

            $value  = $this->data->get(ClInput::BANK_ACCOUNT)[BankAccount\Entity::MASKED_ACCOUNT_NUMBER];
            $input  = [
                ClOutput::NAME  => ClOutput::ACCOUNT,
                ClOutput::VALUE => $value,
            ];
            $info[] = $input;
        }

        return array_filter($info);
    }

    private function getCredLanguagePref()
    {
        return 'en_US';
    }
}
