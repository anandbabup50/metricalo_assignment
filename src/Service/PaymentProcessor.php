<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PaymentProcessor {

    private $httpClient;
    private $shift4ApiKey;
    private $shift4ApiUrl;
    private $aciApiUrl;
    private $aciAuthKey;
    private $aciEntityId;

    public function __construct(HttpClientInterface $httpClient) {
        $this->httpClient = $httpClient;
        $this->shift4ApiKey = $_ENV['SHIFT4_API_KEY'] ?? null;
        $this->shift4ApiUrl = $_ENV['SHIFT4_API_URL'] ?? null;
        $this->aciApiUrl = $_ENV['ACI_API_URL'] ?? null;
        $this->aciAuthKey = $_ENV['ACI_AUTH_KEY'] ?? null;
        $this->aciEntityId = $_ENV['ACI_ENTITY_ID'] ?? null;
    }

    // checking the payment provider and making necessory api calls
    public function processPayment(string $paymentProvider, array $paymentData) {
        if ($paymentProvider === 'shift4') {
            return $this->processShift4Payment($paymentData);
        } elseif ($paymentProvider === 'aci') {
            return $this->processAciPayment($paymentData);
        }
        throw new \InvalidArgumentException("Invalid payment provider: $paymentProvider");
    }

    /* code start of shift4 provider */

    private function processShift4Payment(array $paymentData) {
        $this->validateShift4Config();
        try {
            // Making API Request
            $response = $this->makeShift4Request($paymentData);

            // successfully made the call, then returning the data
            if ($response->getStatusCode() === 200) {
                return $this->processShift4Response($response);
            }

            // API Errors/status other than 200
            return $this->handleApiError($response);
        } catch (\Exception $e) {
            // Unexpected Errors
            return $this->handleUnexpectedError($e);
        }
    }

    // create a new charge
    private function makeShift4Request(array $paymentData) {
        return $this->httpClient->request('POST', $this->shift4ApiUrl, [
                    'json' => $this->prepareShift4PaymentData($paymentData),
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($this->shift4ApiKey . ':') // only username, password left blank
                    ],
        ]);
    }

    // function to prepare request body
    private function prepareShift4PaymentData(array $paymentData) {
        return [
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'card' => [
                'number' => $paymentData['card_number'],
                'expMonth' => $paymentData['card_exp_month'],
                'expYear' => $paymentData['card_exp_year'],
                'cvc' => $paymentData['card_cvv']
            ]
        ];
    }

    // preparing return array
    private function processShift4Response($response) {
        $data = $response->toArray();
        return [
            'transaction_id' => $data['id'],
            'date' => date('Y-m-d H:i:s', $data['created']),
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'card_bin' => $data['card']['first6']
        ];
    }

    /* end of code shift4 provider */


    /* code start of ACI provider */

    private function processAciPayment(array $paymentData) {
        $this->validateAciConfig();
        try {
            // Making pre authorization Request
            $response = $this->makeAciPreAuthorizeRequest($paymentData);

            // successfully made the pre authorization call, then making the payment capture call
            if ($response->getStatusCode() === 200) {
                return $this->makeAciPaymentCaptureRequest($response);
            }

            // API Errors/status other than 200
            return $this->handleApiError($response);
        } catch (\Exception $e) {
            // Unexpected Errors
            return $this->handleUnexpectedError($e);
        }
    }

    // making Pre-authorize payment call
    private function makeAciPreAuthorizeRequest(array $paymentData) {
        return $this->httpClient->request('POST', $this->aciApiUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->aciAuthKey,
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => $this->preparePreAuthorizeRequestData($paymentData)
        ]);
    }

    // Capture the payment
    private function makeAciPaymentCaptureRequest($response) {
        $preAuthorizationResponse = $response->toArray();
        try {
            // Making payment capture Request
            $paymentCaptureResponse = $this->httpClient->request('POST', $this->aciApiUrl . '/' . $preAuthorizationResponse['id'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->aciAuthKey,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => [
                    'entityId' => $this->aciEntityId,
                    'amount' => $preAuthorizationResponse['amount'],
                    'paymentType' => 'CP',
                    'currency' => 'EUR'
                ]
            ]);

            // successfully made the pre authorization call, then making the payment capture call
            if ($paymentCaptureResponse->getStatusCode() === 200) {
                return $this->processAciPaymentCaptureResponse($paymentCaptureResponse, $preAuthorizationResponse['card']['bin']);
            }

            // API Errors/status other than 200
            return $this->handleApiError($paymentCaptureResponse);
        } catch (\Exception $e) {
            // Unexpected Errors
            return $this->handleUnexpectedError($e);
        }
    }

    // prepare authorize request data
    private function preparePreAuthorizeRequestData(array $paymentData) {
        return [
            'entityId' => $this->aciEntityId,
            'amount' => $paymentData['amount'],
            'currency' => 'EUR',
            'paymentBrand' => 'VISA',
            'paymentType' => 'PA',
            'card.number' => '4200000000000000', // hard coding data $paymentData['card_number']
            'card.holder' => 'Jane Jones',
            'card.expiryYear' => '2034', // hard coding data, actual => $paymentData['card_exp_year']
            'card.expiryMonth' => '05', // hard coding data, actual => $paymentData['card_exp_month']
            'card.cvv' => '123' //hard coding data, actual => $paymentData['card_cvv']
        ];
    }

    // creating return array
    private function processAciPaymentCaptureResponse($response, $cardBin) {
        $data = $response->toArray();
        return [
            'transaction_id' => $data['id'],
            'date' => (new \DateTime($data['timestamp']))->format('Y-m-d H:i:s'),
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'card_bin' => $cardBin
        ];
    }

    /* end of code ACI provider */

    // to check and make sure required values are present in configuration file
    private function validateShift4Config(): void {
        if (!$this->shift4ApiKey || !$this->shift4ApiUrl) {
            throw new BadRequestException('An unexpected error occurred (Error Code: EV_343). Please contact our support team for assistance');
        }
    }

    // to check and make sure required values are present in configuration file
    private function validateAciConfig(): void {
        if (!$this->aciApiUrl || !$this->aciAuthKey || !$this->aciEntityId) {
            throw new BadRequestException('An unexpected error occurred (Error Code: EV_343). Please contact our support team for assistance');
        }
    }

    // to handle API fetch request exceptions
    private function handleApiError($response) {
        return [
            'error' => 'API returned error with status ' . $response->getStatusCode(),
            'details' => $response->getContent(false)
        ];
    }

    // handle other general exceptions if any
    private function handleUnexpectedError(\Exception $e) {
        return [
            'error' => 'An unexpected error occurred: ' . $e->getMessage(),
            'details' => $e->getResponse()->getContent(false) ?? 'No additional details available'
        ];
    }
}
