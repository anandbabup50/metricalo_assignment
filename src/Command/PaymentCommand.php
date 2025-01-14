<?php

namespace App\Command;

use App\Service\PaymentProcessor;
use App\Service\PaymentValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class PaymentCommand extends Command {

    private $paymentProcessor;
    private $paymentValidator;

    public function __construct(PaymentProcessor $paymentProcessor, PaymentValidator $paymentValidator) {
        parent::__construct();
        $this->paymentProcessor = $paymentProcessor;
        $this->paymentValidator = $paymentValidator;
    }

    protected function configure() {
        $this
                ->setName('app:payments {payment_provider}')
                ->setDescription('Process a payment via Shift4 or ACI.')
                ->addArgument('payment_provider', InputArgument::REQUIRED, 'The payment system to use (aci or shift4)')
                ->addArgument('amount', InputArgument::REQUIRED, 'Amount of the transaction')
                ->addArgument('currency', InputArgument::REQUIRED, 'Currency')
                ->addArgument('card_number', InputArgument::REQUIRED, 'Card number')
                ->addArgument('card_exp_year', InputArgument::REQUIRED, 'Card expiration year')
                ->addArgument('card_exp_month', InputArgument::REQUIRED, 'Card expiration month')
                ->addArgument('card_cvv', InputArgument::REQUIRED, 'Card CVV');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $paymentProvider = $input->getArgument('payment_provider');
        $amount = $input->getArgument('amount');
        $currency = $input->getArgument('currency');
        $cardNumber = $input->getArgument('card_number');
        $cardExpYear = $input->getArgument('card_exp_year');
        $cardExpMonth = $input->getArgument('card_exp_month');
        $cardCvv = $input->getArgument('card_cvv');

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
            $output->writeln(json_encode(['error' => $validationResult['error']]));
            return Command::FAILURE;
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

        $output->writeln(json_encode($response));
        return Command::SUCCESS;
    }
}
