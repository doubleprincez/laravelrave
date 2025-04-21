<?php

namespace KingFlamez\Rave;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use KingFlamez\Rave\Helpers\Banks;
use KingFlamez\Rave\Helpers\Beneficiary;
use KingFlamez\Rave\Helpers\Bills;
use KingFlamez\Rave\Helpers\Payments;
use KingFlamez\Rave\Helpers\Transfers;

/**
 * Flutterwave's Rave payment laravel package
 * @author Oluwole Adebiyi - Flamez <flamekeed@gmail.com>
 * @version 3
 **/
class Rave
{

    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;

    /**
     * Construct
     */
    function __construct()
    {

        $this->publicKey = config('flutterwave.publicKey');
        $this->secretKey = config('flutterwave.secretKey');
        $this->secretHash = config('flutterwave.secretHash');
        $this->baseUrl = 'https://api.flutterwave.com/v3';
    }


    /**
     * Generates a unique reference
     * @param String|null $transactionPrefix
     * @return string
     */

    public function generateReference(String $transactionPrefix = NULL)
    {
        if ($transactionPrefix) {
            return $transactionPrefix . '_' . uniqid(time());
        }
        return 'flw_' . uniqid(time());
    }

    /**
     * Reaches out to Flutterwave to initialize a payment
     * @param $data
     * @return object
     * @throws ConnectionException
     */
    public function initializePayment(array $data)
    {

        $payment = Http::withToken($this->secretKey)->post(
            $this->baseUrl . '/payments',
            $data
        )->json();

        return $payment;
    }


    /**
     * Gets a transaction ID depending on the redirect structure
     * @return string
     */
    public function getTransactionIDFromCallback()
    {
        $transactionID = request()->transaction_id;

        if (!$transactionID) {
            $transactionID = json_decode(request()->resp)->data->id;
        }

        return $transactionID;
    }

    /**
     * Reaches out to Flutterwave to verify a transaction
     * @param $id
     * @return object
     * @throws ConnectionException
     */
    public function verifyTransaction($id)
    {
        $data =  Http::withToken($this->secretKey)->get($this->baseUrl . "/transactions/" . $id . '/verify')->json();
        return $data;
    }

    /**
     * Confirms webhook `verifi-hash` is the same as the environment variable
     * @param $data
     * @return boolean
     */
    public function verifyWebhook()
    {
        // Process Paystack Webhook. https://developer.flutterwave.com/reference#webhook
        if (request()->header('verif-hash')) {
            // get input and verify paystack signature
            $flutterwaveSignature = request()->header('verif-hash');

            // confirm the signature is right
            if ($flutterwaveSignature == $this->secretHash) {
                return true;
            }
        }
        return false;
    }

    /**
     * Payments
     * @return Payments
     */
    public function payments()
    {
        return new Payments($this->publicKey, $this->secretKey, $this->baseUrl);
    }

    /**
     * Banks
     * @return Banks
     */
    public function banks()
    {
        return new Banks($this->publicKey, $this->secretKey, $this->baseUrl);
    }

    /**
     * Transfers
     * @return Transfers
     */
    public function transfers()
    {
        $transfers = new Transfers($this->publicKey, $this->secretKey, $this->baseUrl);
        return $transfers;
    }

    /**
     * Beneficiary
     * @return Beneficiary
     */
    public function beneficiaries()
    {
        return new Beneficiary($this->publicKey, $this->secretKey, $this->baseUrl);
    }

    /**
     * Bill payments
     * @return Bills
     */
    public function bill()
    {
        return new Bills($this->publicKey, $this->secretKey, $this->baseUrl);
    }
}
