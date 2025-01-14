<?php

namespace App\Controller;

use App\Service\PaymentProcessor;
use App\Service\PaymentValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController {

    private $paymentProcessor;
    private $paymentValidator;

    public function __construct(PaymentProcessor $paymentProcessor, PaymentValidator $paymentValidator) {
        $this->paymentProcessor = $paymentProcessor;
        $this->paymentValidator = $paymentValidator;
    }

    /**
     * Process a payment transaction
     * This endpoint processes a payment using the specified payment provider (ACI or Shift4).
     * It validates the payment details and returns a unified response format.
     * @param string $paymentProvider The payment provider to use (aci or shift4)
     * @param Request $request The HTTP request containing payment details
     * @return JsonResponse|Response The payment processing result or error response
     * @throws \InvalidArgumentException When payment provider is invalid
     */
    public function processPayment(string $paymentProvider, Request $request) {
        $amount = $request->request->get('amount', '');
        $currency = $request->request->get('currency', '');
        $cardNumber = $request->request->get('card_number', '');
        $cardExpYear = $request->request->get('card_exp_year', '');
        $cardExpMonth = $request->request->get('card_exp_month', '');
        $cardCvv = $request->request->get('card_cvv', '');

        $providerValidationResult = $this->paymentValidator->validatePaymentProvider($paymentProvider);
        if (isset($providerValidationResult['error'])) {
            return new Response($providerValidationResult['error']);
        }

        // Validate payment fields using the PaymentValidator
        $validationResult = $this->paymentValidator->validatePaymentFields(
                $amount,
                $currency,
                $cardNumber,
                $cardExpYear,
                $cardExpMonth,
                $cardCvv
        );

        // return validation message if any
        if (isset($validationResult['error'])) {
            return new Response($validationResult['error']);
        }

        // processing the payment
        $response = $this->paymentProcessor->processPayment($paymentProvider, [
            'amount' => $amount,
            'currency' => $currency,
            'card_number' => $cardNumber,
            'card_exp_year' => $cardExpYear,
            'card_exp_month' => $cardExpMonth,
            'card_cvv' => $cardCvv,
        ]);
        return new JsonResponse($response);
    }
}
