<?php

// src/Service/PaymentValidator.php

namespace App\Service;

class PaymentValidator {

    private $cardValidator;
    private $currencyValidator;

    public function __construct(CardValidator $cardValidator, CurrencyValidator $currencyValidator) {
        $this->cardValidator = $cardValidator;
        $this->currencyValidator = $currencyValidator;
    }

    /**
     * Validate the payment fields: card details, amount, and currency.
     *
     * @param string $amount
     * @param string $currency
     * @param string $cardNumber
     * @param string $cardExpYear
     * @param string $cardExpMonth
     * @param string $cardCvv
     * @return array
     */
    public function validatePaymentFields(
            string $amount,
            string $currency,
            string $cardNumber,
            string $cardExpYear,
            string $cardExpMonth,
            string $cardCvv
    ): array {
        // Validate card fields are not empty
        if (!$this->cardValidator->validateFieldsNotEmpty($cardNumber, $cardExpMonth, $cardExpYear, $cardCvv)) {
            return ['error' => 'All fields (card number, expiration date, year, and CVV) must be provided.'];
        }

        // Validate card number, expiration date, and CVV
        $isCardNumberValid = $this->cardValidator->validateCardNumber($cardNumber);
        $isExpirationValid = $this->cardValidator->validateExpirationDate($cardExpMonth, $cardExpYear);
        $isCvvValid = $this->cardValidator->validateCvv($cardCvv);

        if (!($isCardNumberValid && $isExpirationValid && $isCvvValid)) {
            return ['error' => 'Invalid card details.'];
        }

        // Validate currency and amount
        if (!$this->currencyValidator->validateCurrencyAndAmount($currency, $amount)) {
            return ['error' => 'Invalid currency code or amount.'];
        }

        return ['success' => true];
    }

    // to validate payment provider
    public function validatePaymentProvider($providerName) {
        if (empty($providerName) || !in_array(strtolower($providerName), ['shift', 'aci'])) {
            return ['error' => 'Invalid payment provider name.'];
        }
        return ['success' => true];
    }
}
