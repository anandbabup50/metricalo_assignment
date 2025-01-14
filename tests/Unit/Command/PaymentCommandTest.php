<?php

namespace App\Tests\Unit\Command;

use App\Command\PaymentCommand;
use App\Service\PaymentProcessor;
use App\Service\PaymentValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PaymentCommandTest extends TestCase
{
    private PaymentCommand $command;
    private PaymentProcessor $paymentProcessor;
    private PaymentValidator $paymentValidator;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->paymentProcessor = $this->createMock(PaymentProcessor::class);
        $this->paymentValidator = $this->createMock(PaymentValidator::class);
        
        $this->command = new PaymentCommand($this->paymentProcessor, $this->paymentValidator);
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public static function provideCommandTestData(): array
    {
        return [
            'successful_payment' => [
                'input' => [
                    'payment_provider' => 'shift4',
                    'amount' => '100.00',
                    'currency' => 'EUR',
                    'card_number' => '4242424242424242',
                    'card_exp_year' => '2025',
                    'card_exp_month' => '12',
                    'card_cvv' => '123'
                ],
                'validationResult' => ['success' => true],
                'processingResult' => [
                    'transaction_id' => 'test_tx_123',
                    'created_at' => '2025-01-14T12:00:00Z',
                    'amount' => '100.00',
                    'currency' => 'EUR',
                    'card_bin' => '424242'
                ],
                'expectedExitCode' => 0
            ],
            'validation_failure' => [
                'input' => [
                    'payment_provider' => 'shift4',
                    'amount' => '-100.00',
                    'currency' => 'EUR',
                    'card_number' => '4242424242424242',
                    'card_exp_year' => '2025',
                    'card_exp_month' => '12',
                    'card_cvv' => '123'
                ],
                'validationResult' => ['error' => 'Invalid amount'],
                'processingResult' => null,
                'expectedExitCode' => 1
            ]
        ];
    }

    /**
     * @dataProvider provideCommandTestData
     */
    public function testCommandExecution(
        array $input,
        array $validationResult,
        ?array $processingResult,
        int $expectedExitCode
    ): void {
        // Setup validator mock
        $this->paymentValidator
            ->expects($this->once())
            ->method('validatePaymentFields')
            ->willReturn($validationResult);

        if ($processingResult !== null) {
            $this->paymentProcessor
                ->expects($this->once())
                ->method('processPayment')
                ->willReturn($processingResult);
        }

        // Execute command
        $exitCode = $this->commandTester->execute($input);

        // Assert results
        $this->assertEquals($expectedExitCode, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertJson($output);

        if ($processingResult !== null) {
            $this->assertJsonStringEqualsJsonString(
                json_encode($processingResult),
                trim($output)
            );
        } else {
            $this->assertJsonStringEqualsJsonString(
                json_encode($validationResult),
                trim($output)
            );
        }
    }
}