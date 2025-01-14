<?php

namespace App\Service;

class CardValidator {

    /**
     * Validate the card number using the Luhn algorithm.
     * @param string $cardNumber The card number to validate.
     * @return bool Returns true if the card number is valid, false otherwise.
     */
    public function validateCardNumber(string $cardNumber): bool {
        // Check if the card number is empty
        if (empty($cardNumber)) {
            return false;
        }

        $cardNumber = preg_replace('/\D/', '', $cardNumber); // Remove non-digit characters
        // Check if the card number has at least 13 digits (e.g., Visa) and a maximum of 19 digits (e.g., MasterCard)
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }

        $sum = 0;
        $shouldDouble = false;

        // Iterate over the card number from right to left
        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = (int) $cardNumber[$i];

            if ($shouldDouble) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $shouldDouble = !$shouldDouble;
        }

        return $sum % 10 === 0;
    }

    /**
     * Validate the expiration date.
     * @param string $expMonth The expiration month (MM).
     * @param string $expYear The expiration year (YYYY).
     * @return bool Returns true if the expiration date is valid, false otherwise.
     */
    public function validateExpirationDate(string $expMonth, string $expYear): bool {
        // Check if any expiration field is empty
        if (empty($expMonth) || empty($expYear)) {
            return false;
        }

        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        $expMonth = (int) $expMonth;
        $expYear = (int) $expYear;

        // Expiration year should not be in the past
        if ($expYear < $currentYear || ($expYear === $currentYear && $expMonth < $currentMonth)) {
            return false; // Expired card
        }

        // Ensure the expiration month is within the valid range
        return $expMonth >= 1 && $expMonth <= 12;
    }

    /**
     * Validate the CVV.
     * @param string $cvv The CVV to validate.
     * @return bool Returns true if the CVV is valid, false otherwise.
     */
    public function validateCvv(string $cvv): bool {
        // Check if CVV is empty
        if (empty($cvv)) {
            return false;
        }

        // CVV is typically 3 digits for most cards (or 4 for AMEX)
        return preg_match('/^\d{3,4}$/', $cvv);
    }

    /**
     * Validate if any of the card details fields are empty.
     *
     * @param string $cardNumber The card number.
     * @param string $expMonth The expiration month.
     * @param string $expYear The expiration year.
     * @param string $cvv The CVV.
     * @return bool Returns true if all fields are provided, false if any are empty.
     */
    public function validateFieldsNotEmpty(string $cardNumber, string $expMonth, string $expYear, string $cvv): bool {
        return !empty($cardNumber) && !empty($expMonth) && !empty($expYear) && !empty($cvv);
    }
}
