# Person 3 - API Documentation

## Overview

This documentation explains all the APIs for **Person 3's responsibilities** in the money transfer application. Person 3 handles:
- **Beneficiaries** (recipients of money transfers)
- **Transfers** (the actual money movement)
- **Exchange Rates** (currency conversion)
- **Transfer Fees** (fee calculation and management)
- **Payments** (payment gateway integration)

---

## Table of Contents

1. [Beneficiaries API](#beneficiaries-api)
2. [Transfers API](#transfers-api)
3. [Exchange Rates API](#exchange-rates-api)
4. [Transfer Fees API](#transfer-fees-api)
5. [Payments API](#payments-api)
6. [Complete Transfer Flow](#complete-transfer-flow)

---

## Beneficiaries API

A **beneficiary** is a person who will receive money transfers. Users can create multiple beneficiaries.

### 1. Get All Beneficiaries

**Endpoint:** `GET /api/beneficiaries`

**Authentication:** Required (Bearer token)

**Description:** Returns all beneficiaries for the authenticated user.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "full_name": "John Doe",
      "country_id": 2,
      "transfer_method_id": 1,
      "payout_details": {
        "account_number": "123456789",
        "bank_name": "Bank Name"
      },
      "country": {
        "id": 2,
        "iso2": "US",
        "name": "United States"
      },
      "method": {
        "id": 1,
        "name": "Bank Transfer",
        "description": "Direct bank transfer"
      }
    }
  ]
}
```

### 2. Create Beneficiary

**Endpoint:** `POST /api/beneficiaries`

**Authentication:** Required

**Request Body:**
```json
{
  "full_name": "John Doe",
  "country_id": 2,
  "transfer_method_id": 1,
  "payout_details": {
    "account_number": "123456789",
    "bank_name": "Bank Name",
    "swift_code": "SWIFT123"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Beneficiary added successfully",
  "data": {
    "id": 1,
    "full_name": "John Doe",
    ...
  }
}
```

### 3. Get Specific Beneficiary

**Endpoint:** `GET /api/beneficiaries/{id}`

**Authentication:** Required

### 4. Update Beneficiary

**Endpoint:** `PUT /api/beneficiaries/{id}`

**Authentication:** Required

### 5. Delete Beneficiary

**Endpoint:** `DELETE /api/beneficiaries/{id}`

**Authentication:** Required

---

## Transfers API

A **transfer** represents the movement of money from a sender to a beneficiary.

### Transfer Status Flow

```
queued → paid → in_progress → available_for_pickup → completed
   ↓        ↓
failed   refunded
```

- **queued**: Transfer created, waiting for payment
- **paid**: Payment authorized
- **in_progress**: Payment captured, transfer being processed
- **available_for_pickup**: Ready for beneficiary to collect (if agent-based)
- **completed**: Transfer successfully completed
- **failed**: Transfer failed
- **refunded**: Transfer was refunded

### 1. Get Transfer Summary (Before Creating)

**Endpoint:** `GET /api/transfers/summary`

**Authentication:** Required

**Query Parameters:**
- `beneficiary_id` (required): The beneficiary ID
- `amount` (required): The amount to send
- `currency_from` (required): Currency code to send from (e.g., "USD")
- `currency_to` (required): Currency code to send to (e.g., "EUR")

**Description:** This endpoint calculates and shows you the breakdown of a transfer BEFORE you create it. It shows:
- Exchange rate
- Transfer fee
- Amount you'll send
- Amount recipient will receive
- Total amount (amount + fee)

**Example Request:**
```
GET /api/transfers/summary?beneficiary_id=1&amount=1000&currency_from=USD&currency_to=EUR
```

**Response:**
```json
{
  "success": true,
  "data": {
    "beneficiary": {
      "id": 1,
      "full_name": "John Doe",
      "country": { ... },
      "method": { ... }
    },
    "amount": {
      "send": 1000,
      "currency_from": "USD",
      "receive": 850,
      "currency_to": "EUR"
    },
    "exchange_rate": 0.85,
    "fee": 25,
    "total_amount": 1025,
    "breakdown": {
      "transfer_amount": 1000,
      "fee": 25,
      "total_to_pay": 1025,
      "recipient_receives": 850
    }
  }
}
```

### 2. Create Transfer

**Endpoint:** `POST /api/transfers`

**Authentication:** Required

**Request Body:**
```json
{
  "beneficiary_id": 1,
  "amount": 1000,
  "currency_from": "USD",
  "currency_to": "EUR"
}
```

**What Happens:**
1. System validates the beneficiary belongs to you
2. Fetches current exchange rate
3. Calculates transfer fee
4. Generates unique reference code (e.g., "TRFABC123XYZ")
5. Creates transfer with status "queued"
6. Creates initial transfer event

**Response:**
```json
{
  "success": true,
  "message": "Transfer initiated successfully",
  "data": {
    "id": 1,
    "sender_id": 5,
    "beneficiary_id": 1,
    "amount": 1000,
    "currency_from": "USD",
    "currency_to": "EUR",
    "exchange_rate": 0.85,
    "fee": 25,
    "total_amount": 1025,
    "status": "queued",
    "reference": "TRFABC123XYZ",
    "initiated_at": "2024-01-15T10:30:00Z",
    "beneficiary": { ... },
    "events": [
      {
        "id": 1,
        "status": "queued",
        "note": "Transfer initiated successfully",
        "created_at": "2024-01-15T10:30:00Z"
      }
    ]
  }
}
```

### 3. Get All Transfers

**Endpoint:** `GET /api/transfers`

**Authentication:** Required

**Query Parameters:**
- `status` (optional): Filter by status (queued, paid, completed, etc.)
- `from_date` (optional): Filter from date (YYYY-MM-DD)
- `to_date` (optional): Filter to date (YYYY-MM-DD)
- `per_page` (optional): Results per page (default: 15)

**Example:**
```
GET /api/transfers?status=completed&from_date=2024-01-01
```

### 4. Get Specific Transfer

**Endpoint:** `GET /api/transfers/{id}`

**Authentication:** Required

**Response:** Returns detailed transfer information including beneficiary, events, and payment.

### 5. Track Transfer

**Endpoint:** `GET /api/transfers/{id}/track`

**Authentication:** Required

**Description:** Returns the current status and all events (status changes) for a transfer. Useful for showing users a timeline.

**Response:**
```json
{
  "success": true,
  "data": {
    "transfer": { ... },
    "current_status": "in_progress",
    "events": [
      {
        "id": 3,
        "status": "in_progress",
        "note": "Payment captured, transfer processing",
        "created_at": "2024-01-15T10:35:00Z"
      },
      {
        "id": 2,
        "status": "paid",
        "note": "Payment authorized via stripe",
        "created_at": "2024-01-15T10:32:00Z"
      },
      {
        "id": 1,
        "status": "queued",
        "note": "Transfer initiated successfully",
        "created_at": "2024-01-15T10:30:00Z"
      }
    ]
  }
}
```

### 6. Cancel Transfer

**Endpoint:** `POST /api/transfers/{id}/cancel`

**Authentication:** Required

**Description:** Cancels a transfer. Only transfers with status "queued" or "paid" can be cancelled.

**Response:**
```json
{
  "success": true,
  "message": "Transfer cancelled successfully",
  "data": { ... }
}
```

---

## Exchange Rates API

Exchange rates are used to convert money from one currency to another.

### 1. Get All Exchange Rates

**Endpoint:** `GET /api/exchange-rates`

**Authentication:** Not required (public)

**Query Parameters:**
- `from` (optional): Filter by source currency
- `to` (optional): Filter by destination currency

**Example:**
```
GET /api/exchange-rates?from=USD&to=EUR
```

### 2. Get Exchange Rate

**Endpoint:** `GET /api/exchange-rates/{from}/{to}`

**Authentication:** Not required (public)

**Description:** Gets the current exchange rate from one currency to another.

**Example:**
```
GET /api/exchange-rates/USD/EUR
```

**Response:**
```json
{
  "success": true,
  "data": {
    "from": "USD",
    "to": "EUR",
    "rate": 0.85
  }
}
```

### 3. Convert Amount

**Endpoint:** `GET /api/exchange-rates/convert`

**Authentication:** Not required (public)

**Query Parameters:**
- `amount` (required): Amount to convert
- `from` (required): Source currency code
- `to` (required): Destination currency code

**Example:**
```
GET /api/exchange-rates/convert?amount=100&from=USD&to=EUR
```

**Response:**
```json
{
  "success": true,
  "data": {
    "amount": 100,
    "from": "USD",
    "to": "EUR",
    "rate": 0.85,
    "converted_amount": 85
  }
}
```

**How It Works:**
1. System checks database for recent rate (less than 24 hours old)
2. If found, uses that rate
3. Otherwise, fetches from external API
4. Saves rate to database
5. Calculates converted amount

---

## Transfer Fees API

Transfer fees are calculated based on:
- Sender's country
- Recipient's country
- Transfer amount

### 1. Calculate Fee

**Endpoint:** `POST /api/transfer-fees/calculate`

**Authentication:** Required

**Request Body:**
```json
{
  "amount": 1000,
  "country_from_id": 1,
  "country_to_id": 2
}
```

**Description:** Calculates the fee that would be charged for a transfer. Useful for showing users the fee before they create a transfer.

**Response:**
```json
{
  "success": true,
  "data": {
    "amount": 1000,
    "country_from": { "id": 1, "name": "United States" },
    "country_to": { "id": 2, "name": "United Kingdom" },
    "fee": 25,
    "fee_rule": {
      "id": 1,
      "fee_fixed": 5,
      "fee_percent": 2
    },
    "total_amount": 1025
  }
}
```

**Fee Calculation:**
- If a fee rule exists: `fee = fee_fixed + (amount * fee_percent / 100)`
- If no rule exists: `fee = max(amount * 0.02, 5.0)` (2% with minimum $5)

### 2. Get All Fee Rules

**Endpoint:** `GET /api/transfer-fees`

**Authentication:** Required

**Query Parameters:**
- `country_from_id` (optional): Filter by sender country
- `country_to_id` (optional): Filter by recipient country

### 3. Create Fee Rule (Admin)

**Endpoint:** `POST /api/transfer-fees`

**Authentication:** Required (Admin only)

**Request Body:**
```json
{
  "country_from_id": 1,
  "country_to_id": 2,
  "min_amount": 0,
  "max_amount": 10000,
  "fee_fixed": 5,
  "fee_percent": 2.5
}
```

### 4. Update Fee Rule (Admin)

**Endpoint:** `PUT /api/transfer-fees/{id}`

**Authentication:** Required (Admin only)

### 5. Delete Fee Rule (Admin)

**Endpoint:** `DELETE /api/transfer-fees/{id}`

**Authentication:** Required (Admin only)

---

## Payments API

Payments are processed through payment gateways (Stripe, PayPal, etc.). This system includes a **mock payment gateway** for testing.

### Payment Status Flow

```
authorized → captured → (refunded)
```

- **authorized**: Payment authorized but not yet captured
- **captured**: Payment captured, money moved
- **failed**: Payment failed
- **refunded**: Payment refunded

### 1. Create Payment (Authorize)

**Endpoint:** `POST /api/payments`

**Authentication:** Required

**Request Body:**
```json
{
  "transfer_id": 1,
  "gateway": "stripe",
  "gateway_ref": "ch_abc123" // Optional, generated if not provided
}
```

**Description:** Authorizes a payment for a transfer. This calls the mock payment gateway which simulates a real payment provider.

**What Happens:**
1. Validates transfer belongs to user
2. Validates transfer status is "queued"
3. Calls mock payment gateway to authorize payment
4. Creates payment record
5. Updates transfer status to "paid"
6. Creates transfer event

**Response:**
```json
{
  "success": true,
  "message": "Payment authorized successfully",
  "data": {
    "id": 1,
    "transfer_id": 1,
    "amount": 1025,
    "currency_code": "USD",
    "gateway": "stripe",
    "gateway_ref": "ch_ABC123XYZ",
    "status": "authorized",
    "authorized_at": "2024-01-15T10:32:00Z"
  }
}
```

### 2. Capture Payment

**Endpoint:** `POST /api/payments/{id}/capture`

**Authentication:** Required

**Description:** Captures an authorized payment. This moves the money from the customer's account to your account.

**What Happens:**
1. Validates payment is "authorized"
2. Calls mock gateway to capture payment
3. Updates payment status to "captured"
4. Updates transfer status to "in_progress"
5. Creates transfer event

**Response:**
```json
{
  "success": true,
  "message": "Payment captured successfully",
  "data": {
    "id": 1,
    "status": "captured",
    "captured_at": "2024-01-15T10:35:00Z",
    ...
  }
}
```

### 3. Refund Payment

**Endpoint:** `POST /api/payments/{id}/refund`

**Authentication:** Required

**Description:** Refunds a payment. Can be full or partial refund.

**Response:**
```json
{
  "success": true,
  "message": "Payment refunded successfully",
  "data": { ... }
}
```

### Mock Payment Gateway

The system includes a **mock payment gateway** that simulates real payment processing:

- **Always succeeds** (unless you pass `test_fail: true` in payment data)
- **Generates fake gateway references**
- **Simulates API delays**

In production, replace the mock methods in `PaymentService` with actual API calls to your payment provider.

---

## Complete Transfer Flow

Here's the complete flow of creating and processing a transfer:

### Step 1: Create Beneficiary
```http
POST /api/beneficiaries
{
  "full_name": "John Doe",
  "country_id": 2,
  "transfer_method_id": 1,
  "payout_details": { ... }
}
```

### Step 2: Get Transfer Summary (Optional)
```http
GET /api/transfers/summary?beneficiary_id=1&amount=1000&currency_from=USD&currency_to=EUR
```
This shows you the breakdown before creating the transfer.

### Step 3: Create Transfer
```http
POST /api/transfers
{
  "beneficiary_id": 1,
  "amount": 1000,
  "currency_from": "USD",
  "currency_to": "EUR"
}
```
Transfer is created with status "queued".

### Step 4: Authorize Payment
```http
POST /api/payments
{
  "transfer_id": 1,
  "gateway": "stripe"
}
```
Payment is authorized, transfer status changes to "paid".

### Step 5: Capture Payment
```http
POST /api/payments/1/capture
```
Payment is captured, transfer status changes to "in_progress".

### Step 6: Track Transfer
```http
GET /api/transfers/1/track
```
Check the status and events of your transfer.

### Step 7: Transfer Completes
The system (or an agent) updates the transfer status to "completed" when the money is delivered.

---

## Error Handling

All APIs return errors in this format:

```json
{
  "success": false,
  "message": "Error message here"
}
```

Common HTTP Status Codes:
- `200`: Success
- `201`: Created
- `400`: Bad Request (validation error)
- `401`: Unauthorized (not authenticated)
- `403`: Forbidden (not authorized)
- `404`: Not Found
- `500`: Server Error

---

## Authentication

Most endpoints require authentication using a Bearer token:

```http
Authorization: Bearer {your_token_here}
```

Get your token by logging in through the authentication endpoints (handled by Person 1).

---

## Notes for Beginners

1. **Always authenticate first** - Get a token from the login endpoint
2. **Create beneficiaries before transfers** - You need a beneficiary to send money to
3. **Check the summary** - Use the summary endpoint to see fees before creating a transfer
4. **Follow the status flow** - Transfers go through specific statuses in order
5. **Track your transfers** - Use the track endpoint to see what's happening
6. **Mock gateway is for testing** - In production, replace with real payment providers

---

## File Structure

Person 3's code is organized as follows:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── BeneficiaryController.php      # Beneficiary CRUD
│   │   ├── TransferController.php         # Transfer operations
│   │   ├── ExchangeRateController.php     # Exchange rate operations
│   │   ├── TransferFeeController.php      # Fee management
│   │   └── PaymentController.php          # Payment operations
│   └── Requests/
│       ├── StoreTransferRequest.php       # Transfer validation
│       ├── StoreBeneficiaryRequest.php    # Beneficiary validation
│       └── StorePaymentRequest.php        # Payment validation
├── Models/
│   ├── Beneficiary.php
│   ├── Transfer.php
│   ├── Exchange_Rate.php
│   ├── Transfer_Fee.php
│   ├── Payment.php
│   └── Transfer_Event.php
└── Services/
    ├── TransferService.php               # Transfer business logic
    ├── PaymentService.php                # Payment business logic
    └── ExchangeRateService.php           # Exchange rate logic
```

---

## Questions?

If you have questions about Person 3's APIs, check:
1. The code comments in each controller and service
2. The model relationships in the Model files
3. The validation rules in the Request files

Happy coding! 🚀

