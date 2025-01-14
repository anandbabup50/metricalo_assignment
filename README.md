Payment Gateway Integration API & CLI
This project implements an API endpoint and CLI command that interact with two external payment gateways: Shift4 and ACI. Based on the request parameter, the server will send a request to the appropriate external system and return a unified response. This solution is built with Symfony 6.4 and PHP 8.
Project Overview
    API Endpoint: The API accepts input parameters such as amount, currency, card details (number, expiry year, expiry month, CVV).
    It sends a request to one of two external systems (Shift4 or ACI) based on a parameter passed in the URL.
    The response is unified regardless of which external system is used, and it will include:
        1. Transaction ID
        2. Date of creation
        3. Amount
        4. Currency
        5. Card BIN
API Example:
Endpoint URL: v1/payments/{aci|shift4} Replace {aci|shift4} with either aci or shift4 to determine which external system is called.

CLI Command:
The same functionality is implemented via a Symfony console command.
It takes the same parameters as the API endpoint and sends a request to one of the external systems based on the parameter passed in the command.
CLI Example: Command: php bin/console app:payments {aci|shift4}. Replace {aci|shift4} with either aci or shift4 to select the external system.

External Systems Integration : Shift4 and ACI
Technical Requirements : PHP - 8.3 and Symfony - 6.4

Setup & Installation

Running app on local 
    1. Clone the repository :- git clone https://github.com/anandbabup50/metricalo_assignment.git
    2. Install dependencies :- composer install
    3. Run the Symfony Server (for API testing) :- symfony server:start
    4. To execure CLI command :- php bin/console app:payments {aci|shift4} 100 EUR 4111111111111111 2025 12 123. Replace {aci|shift4} with either aci or shift4 to call the respective external system.
    The API will be available at http://localhost:8000.
Docker Setup
    1. Run docker build -t metricalo_assignment .
    2. Run docker run -d -p 8083:8000 --name metricalo_assignment-container metricalo_assignment
    The API will be accessible at http://localhost:8080.

Endpoints and CLI Commands
API Endpoint
    URL: /v1/payments/{aci|shift4}
    Method: GET
    Parameters:
        amount: Transaction amount (e.g. 100)
        currency: Currency code (e.g. EUR)
        card-number: Card number (e.g. 4111111111111111)
        card-exp-year: Expiration year (e.g. 2025)
        card-exp-month: Expiration month (e.g. 12)
        card-cvv: CVV (e.g. 123)
    Sample Response 
        {
            "transaction_id": "txn_12345",
            "date": "2025-01-14T09:30:00Z",
            "amount": 100,
            "currency": "EUR",
            "card_bin": "411111"
        }
CLI Command
Command: bin/console app:payments {aci|shift4}
Parameters:
    amount: Transaction amount
    currency: Currency code
    card-number: Card number
    card-exp-year: Expiration year
    card-exp-month: Expiration month
    card-cvv: CVV
Example 
    Command : php bin/console app:payments aci 100 EUR 4111111111111111 2025 12 123
    Output : {"transaction_id":"8ac7a4a19463eccf019465125ad54760","date":"2025-01-14 13:48:09","amount":"100.00","currency":"EUR","card_bin":"420000"}
