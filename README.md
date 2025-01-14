# Payment Gateway Integration API & CLI

This project implements an API endpoint and CLI command that interact with two external payment gateways: **Shift4** and **ACI**. Based on the request parameter, the server will send a request to the appropriate external system and return a unified response. This solution is built with **Symfony 6.4** and **PHP 8**.

## Project Overview

### API Endpoint:
The API accepts input parameters such as amount, currency, and card details (number, expiry year, expiry month, CVV). It sends a request to one of two external systems (Shift4 or ACI) based on a parameter passed in the URL. The response is unified regardless of which external system is used, and it will include:

- **Transaction ID**
- **Date of creation**
- **Amount**
- **Currency**
- **Card BIN**

### API Example:
**Endpoint URL**: `/v1/payments/{aci|shift4}`  
Replace `{aci|shift4}` with either `aci` or `shift4` to determine which external system is called.

### CLI Command:
The same functionality is implemented via a Symfony console command. It takes the same parameters as the API endpoint and sends a request to one of the external systems based on the parameter passed in the command.

**CLI Example**:  
**Command**: `php bin/console app:payments {aci|shift4}`  
Replace `{aci|shift4}` with either `aci` or `shift4` to select the external system.

## External Systems Integration: Shift4 and ACI

### Technical Requirements:
- **PHP**: 8.3
- **Symfony**: 6.4

## Setup & Installation

### Running the App Locally

1. **Clone the repository**:
    ```bash
    git clone https://github.com/anandbabup50/metricalo_assignment.git
    ```

2. **Install dependencies**:
    ```bash
    composer install
    ```

3. **Run the Symfony Server (for API testing)**:
    ```bash
    symfony server:start
    ```

4. **To execute the CLI command**:
    ```bash
    php bin/console app:payments {aci|shift4} 100 EUR 4111111111111111 2025 12 123
    ```
    Replace `{aci|shift4}` with either `aci` or `shift4` to call the respective external system.  
    The API will be available at `http://localhost:8000`.

### Docker Setup

1. **Build the Docker image**:
    ```bash
    docker build -t metricalo_assignment .
    ```

2. **Run the Docker container**:
    ```bash
    docker run -d -p 8083:8000 --name metricalo_assignment-container metricalo_assignment
    ```
    The API will be accessible at `http://localhost:8083`.

## Endpoints and CLI Commands

### API Endpoint

- **URL**: `/v1/payments/{aci|shift4}`
- **Method**: `GET`
- **Parameters**:
    - `amount`: Transaction amount (e.g., `100`)
    - `currency`: Currency code (e.g., `EUR`)
    - `card-number`: Card number (e.g., `4111111111111111`)
    - `card-exp-year`: Expiration year (e.g., `2025`)
    - `card-exp-month`: Expiration month (e.g., `12`)
    - `card-cvv`: CVV (e.g., `123`)

**Sample Response**:
```json
{
    "transaction_id": "txn_12345",
    "date": "2025-01-14T09:30:00Z",
    "amount": 100,
    "currency": "EUR",
    "card_bin": "411111"
}
