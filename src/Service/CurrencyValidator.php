<?php

namespace App\Service;

class CurrencyValidator {

    /**
     * List of currencies supported by various providers
     */
    private const VALID_CURRENCY_CODES = [
        'USD', 'CAD', 'EUR', 'GBP', 'AUD', 'JPY', 'MXN', 'CHF', 'CNY', 'INR',
        'BRL', 'SEK', 'NOK', 'DKK', 'HKD', 'SGD', 'NZD', 'ZAR', 'KRW', 'RUB',
        'TRY', 'PLN', 'ILS', 'MYR', 'THB', 'PHP'
    ];

    /**
     * Validate the three-letter ISO currency code.
     * @param string $currency The currency code to validate.
     * @return bool Returns true if the currency code is valid, false otherwise.
     */
    public function validateCurrencyCode(string $currency): bool {
        // Check if the currency is a valid ISO code (3-letter)
        return in_array(strtoupper($currency), self::VALID_CURRENCY_CODES);
    }

    /**
     * Validate the user input amount.
     * @param string $amount The amount to validate.
     * @param string $currency The currency associated with the amount (for example, "USD").
     * @return bool Returns true if the amount is valid, false otherwise.
     */
    public function validateAmount(string $amount): bool {
        // Check if the amount is a valid number (can include decimal points)
        if (!is_numeric($amount) || (float) $amount <= 0) {
            return false; // The amount should be a positive number.
        }
        return true;
    }

    /**
     * Validate the user input currency and amount.
     * @param string $currency The currency code.
     * @param string $amount The amount to validate.
     * @return bool Returns true if both the currency code and amount are valid.
     */
    public function validateCurrencyAndAmount(string $currency, string $amount): bool {
        // validating the currency code
        if (empty($currency) || !$this->validateCurrencyCode($currency)) {
            return false; // Invalid currency code
        }

        // validating the amount
        return $this->validateAmount($amount);
    }
}
