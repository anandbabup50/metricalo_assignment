<?php

namespace App\Tests\Unit\Controller;

use App\Controller\PaymentController;
use App\Service\PaymentProcessor;
use App\Service\PaymentValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentControllerTest extends TestCase
{
    private PaymentController $controller;
    private PaymentProcessor $paymentProcessor;
    private PaymentValidator $paymentValidator;

    protected function setUp(): void
    {
        $this->paymentProcessor = $this->createMock(PaymentProcessor::class);
        $this->paymentValidator = $this->createMock(PaymentValidator::class);
        $this->controller = new PaymentController($this->paymentProcessor, $this->paymentValidator);
    }

    /**
     * @dataProvider provideValidPaymentData
     */
    public function testSuccessfulPaymentProcessing(string $provider, array $requestData, array $expectedResponse): void
    {
        // Setup validator mock
        $this->paymentValidator
            ->expects($this->once())
            ->method('validatePaymentProvider')
            ->with($provider)
            ->willReturn(['success' => true]);

        $this->paymentValidator
            ->expects($this->once())
            ->method('validatePaymentFields')
            ->with(
                $requestData['amount'],
                $requestData['currency'],
                $requestData['card_number'],
                $requestData['card_exp_year'],
                $requestData['card_exp_month'],
                $requestData['card_cvv']
            )
            ->willReturn(['success' => true]);

        // Setup processor mock
        $this->paymentProcessor
            ->expects($this->once())
            ->method('processPayment')
            ->with($provider, $requestData)
            ->willReturn($expectedResponse);

        // Create request with test data
        $request = new Request([], $requestData);

        // Process payment
        $response = $this->controller->processPayment($provider, $request);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse),
            $response->getContent()
        );
    }

    public static function provideValidPaymentData(): array
    {
        return [
            'shift4_valid_payment' => [
                'provider' => 'shift4',
                'requestData' => [
                    'amount' => '100.00',
                    'currency' => 'EUR',
                    'card_number' => '4242424242424242',
                    'card_exp_year' => '2025',
                    'card_exp_month' => '12',
                    'card_cvv' => '123'
                ],
                'expectedResponse' => [
                    'transaction_id' => 'test_tx_123',
                    'created_at' => '2025-01-14T12:00:00Z',
                    'amount' => '100.00',
                    'currency' => 'EUR',
                    'card_bin' => '424242'
                ]
            ],
            'aci_valid_payment' => [
                'provider' => 'aci',
                'requestData' => [
                    'amount' => '50.00',
                    'currency' => 'EUR',
                    'card_number' => '5555555555554444',
                    'card_exp_year' => '2025',
                    'card_exp_month' => '12',
                    'card_cvv' => '123'
                ],
                'expectedResponse' => [
                    'transaction_id' => 'aci_tx_456',
                    'created_at' => '2025-01-14T12:00:00Z',
                    'amount' => '50.00',
                    'currency' => 'EUR',
                    'card_bin' => '555555'
                ]
            ]
        ];
    }

    /**
     * @dataProvider provideInvalidPaymentData
     */
    public function testInvalidPaymentProcessing(
        string $provider, 
        array $requestData, 
        string $validationError
    ): void {
        // Setup validator mock to return error
        $this->paymentValidator
            ->expects($this->once())
            ->method('validatePaymentProvider')
            ->with($provider)
            ->willReturn(['error' => $validationError]);

        // Create request with test data
        $request = new Request([], $requestData);

        // Process payment
        $response = $this->controller->processPayment($provider, $request);

        // Assert response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($validationError, $response->getContent());
    }

    public static function provideInvalidPaymentData(): array
    {
        return [
            'invalid_amount' => [
                'provider' => 'shift4',
                'requestData' => [
                    'amount' => '-100.00',
                    'currency' => 'EUR',
                    'card_number' => '4242424242424242',
                    'card_exp_year' => '2025',
                    'card_exp_month' => '12',
                    'card_cvv' => '123'
                ],
                'validationError' => 'Invalid amount'
            ],
            'invalid_card_number' => [
                'provider' => 'shift4',
                'requestData' => [
                    'amount' => '100.00',
                    'currency' => 'EUR',
                    'card_number' => '424242',
                    'card_exp_year' => '2025',
                    'card_exp_month' => '12',
                    'card_cvv' => '123'
                ],
                'validationError' => 'Invalid card number'
            ]
        ];
    }
}