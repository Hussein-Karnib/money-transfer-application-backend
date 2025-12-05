# Identity Verification & Bank Account Workflow

## Overview
Users must complete identity verification (ID/Passport upload) **before** adding bank accounts. This ensures compliance and security.

---

## User Flow

### Step 1: Check Verification Status
```bash
GET /api/kyc/status
Headers: Authorization: Bearer {token}

Response:
{
  "success": true,
  "verified": false,
  "status": "not_submitted",
  "message": "Identity verification required to add bank accounts"
}
```

### Step 2: Submit Identity Document
```bash
POST /api/kyc
Headers: 
  Authorization: Bearer {token}
  Content-Type: multipart/form-data

Body:
  id_type: "passport"           // passport, national_id, drivers_license
  id_number: "AB1234567"
  expiry_date: "2027-12-31"     // Optional, for documents with expiry
  document: <file>              // Required: JPG, PNG, PDF (max 5MB)

Response:
{
  "success": true,
  "message": "Document submitted successfully. Admin review typically takes 24-48 hours.",
  "data": {
    "id": 1,
    "id_type": "passport",
    "id_number": "****4567",  // Masked for security
    "status": "pending",
    "document_url": null,
    "created_at": "2024-12-05T10:30:00Z"
  }
}
```

### Step 3: Wait for Admin Approval
User will be notified when verification is approved or rejected.

**Status possibilities:**
- `not_submitted` - User hasn't uploaded any document
- `pending` - Document uploaded, waiting for admin review
- `verified` - Document approved by admin
- `rejected` - Document rejected, user needs to resubmit
- `expired` - Document verified but has expired

### Step 4: Add Bank Account (After Verification)
Once status = `verified`, user can add bank accounts:

```bash
POST /api/bank-accounts
Headers: Authorization: Bearer {token}

Body:
{
  "currency_code": "USD",
  "bank_name": "My Primary Account"  // Optional
}

Response (Success - 201):
{
  "success": true,
  "message": "Bank account added successfully",
  "data": {
    "id": 1,
    "bank_name": "My Primary Account",
    "currency_code": "USD",
    "status": "pending",
    "card": {
      "brand": "visa",
      "last4": "1234",
      "masked": "4111 **** **** 1234"
    }
  }
}

Response (Failed - 403 - Not Verified):
{
  "success": false,
  "message": "Identity verification required. Please upload your ID/Passport.",
  "code": "KYC_NOT_VERIFIED"
}

Response (Failed - 403 - Pending):
{
  "success": false,
  "message": "Your identity verification is pending. Please wait for admin approval.",
  "code": "KYC_PENDING",
  "submitted_at": "2024-12-05T10:30:00Z"
}

Response (Failed - 403 - Expired):
{
  "success": false,
  "message": "Your identity document has expired. Please submit a new verification.",
  "code": "KYC_EXPIRED",
  "expired_at": "2024-12-04T23:59:59Z"
}
```

---

## Admin Flow

### 1. List Pending Verifications
```bash
GET /api/kyc/pending
Headers: Authorization: Bearer {admin_token}

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "id_type": "passport",
      "id_number": "AB1234567",
      "status": "pending",
      "expiry_date": "2027-12-31",
      "document_url": "/api/kyc/document/1",
      "created_at": "2024-12-05T10:30:00Z"
    }
  ],
  "meta": {
    "total": 5,
    "per_page": 20,
    "current_page": 1,
    "last_page": 1
  }
}
```

### 2. View Document
```bash
GET /api/kyc/document/{verification_id}
Headers: Authorization: Bearer {admin_token}

Response: Document file (PDF, JPG, PNG)
```

### 3. Approve Verification
```bash
POST /api/kyc/{id}/approve
Headers: Authorization: Bearer {admin_token}

Body:
{
  "review_comment": "Document is clear and valid"
}

Response:
{
  "success": true,
  "message": "Identity verification approved",
  "data": {
    "id": 1,
    "user": { ... },
    "status": "verified",
    "verified_at": "2024-12-05T11:00:00Z",
    "review_comment": "Document is clear and valid"
  }
}
```

### 4. Reject Verification
```bash
POST /api/kyc/{id}/reject
Headers: Authorization: Bearer {admin_token}

Body:
{
  "reason": "blurry",  // blurry, illegible, expired, incomplete, fraud_suspected, other
  "review_comment": "Document image is too blurry. Please resubmit with a clearer photo."
}

Response:
{
  "success": true,
  "message": "Identity verification rejected",
  "data": {
    "id": 1,
    "user": { ... },
    "status": "rejected",
    "review_comment": "Document image is too blurry..."
  }
}
```

---

## Error Codes

| Code | HTTP Status | Meaning | Action |
|------|-------------|---------|--------|
| `KYC_NOT_VERIFIED` | 403 | No verified document on file | User must submit ID/Passport |
| `KYC_PENDING` | 403 | Document pending admin review | Wait for admin approval |
| `KYC_EXPIRED` | 403 | Document expired | Resubmit new document |
| `PROFILE_VERIFICATION_REQUIRED` | 403 | Email/Phone not verified | Complete profile first |

---

## Data Validation

### Document Upload Constraints
- **Allowed formats**: JPG, JPEG, PNG, PDF
- **Maximum size**: 5MB
- **Minimum quality**: Must be clear and legible
- **Content**: Must show full document within frame

### ID Type Values
- `passport` - International Passport
- `national_id` - National ID Card / National ID Number
- `drivers_license` - Driver's License

### ID Number Validation
- Alphanumeric + hyphens/forward slashes only
- 1-50 characters
- Examples: `AB1234567`, `123-456-7890`, `AB/123/456`

---

## Database Schema

### user_verifications table
```sql
- id
- user_id (FK to users)
- id_type (enum: passport, national_id, drivers_license)
- id_number (string)
- document_path (string - private storage)
- expiry_date (date - nullable)
- status (enum: pending, verified, rejected)
- review_comment (text - admin notes)
- verified_at (timestamp - when approved)
- created_at
- updated_at
```

---

## Helper Methods

### On User Model
```php
$user->verifiedIdentity();           // Get verified identity
$user->hasVerifiedIdentity();        // Boolean check
$user->hasPendingVerification();     // Pending status
$user->hasExpiredVerification();     // Expired status
$user->verificationStatus();         // String: verified, pending, expired, etc.

$canAdd = $user->canAddBankAccount();
if ($canAdd['allowed']) {
    // Can add bank account
} else {
    echo $canAdd['message']; // User-friendly error message
    echo $canAdd['reason'];  // Code for error handling
}
```

### On UserVerification Model
```php
$verification->isValid();            // Verified and not expired
$verification->isExpired();          // Document expired
$verification->needsRenewal();       // Expires within 30 days
$verification->daysUntilExpiry();    // Integer or null
$verification->isPending();          // Status = pending
$verification->isRejected();         // Status = rejected
$verification->getStatusText();      // "Verified", "Pending Review", etc.
```

---

## Notifications

When verification is approved/rejected, user receives notifications:

### Approval Notification
- **Type**: Verification Required (misleading name, but used for positive confirmations)
- **Message**: "Your identity verification has been approved. You can now add bank accounts."
- **Channels**: Email, In-app

### Rejection Notification
- **Type**: Security Alert
- **Message**: "Your identity verification was rejected. Reason: [admin comment]"
- **Channels**: Email, In-app

---

## Implementation Checklist

- [x] UserVerification model with helper methods
- [x] KycVerifiedMiddleware checks for "verified" status
- [x] UserBankAccountController enforces verification
- [x] UserVerificationController handles document uploads
- [x] Admin routes for approval/rejection
- [x] Document storage (private disk)
- [x] Expiry date tracking
- [x] Notification system integration
- [x] Audit logging
- [x] Input validation
- [ ] Frontend implementation (user document upload UI)
- [ ] Frontend implementation (admin review dashboard)
- [ ] SMS/Email notification templates
- [ ] Document quality scanning (optional)

---

## Testing

### Create Test User with Verification
```php
// Create user
$user = User::factory()->create();

// Upload verification
$verification = UserVerification::create([
    'user_id' => $user->id,
    'id_type' => 'passport',
    'id_number' => 'AB1234567',
    'document_path' => 'kyc-documents/test.jpg',
    'status' => 'verified',
    'verified_at' => now(),
]);

// Now user can add bank accounts
$user->hasVerifiedIdentity(); // true
```

### Test Bank Account Creation Flow
```bash
# 1. Check status (should fail)
curl -X GET http://localhost:8000/api/kyc/status \
  -H "Authorization: Bearer TOKEN"

# 2. Upload document
curl -X POST http://localhost:8000/api/kyc \
  -H "Authorization: Bearer TOKEN" \
  -F "id_type=passport" \
  -F "id_number=AB1234567" \
  -F "document=@passport.jpg"

# 3. Admin approves (from admin account)
curl -X POST http://localhost:8000/api/kyc/1/approve \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"review_comment":"Approved"}'

# 4. Try adding bank account (should now work)
curl -X POST http://localhost:8000/api/bank-accounts \
  -H "Authorization: Bearer TOKEN" \
  -d '{"currency_code":"USD"}'
```

---

## Security Considerations

✅ **Implemented:**
- Private document storage (not publicly accessible)
- ID numbers masked in user-facing responses
- Admin-only verification endpoints
- Audit logging of all approval/rejection actions
- Expiry date validation
- Document format validation
- File size limits

⚠️ **Optional Enhancements:**
- Virus scanning for uploaded documents
- Optical Character Recognition (OCR) for document validation
- Biometric verification
- Re-verification requirements (e.g., annually)
- Suspicious pattern detection

---

## Troubleshooting

### User gets "KYC_NOT_VERIFIED" when document was submitted
- Check document status: `GET /api/kyc`
- If status is "rejected": User must resubmit
- If status is "pending": Admin hasn't reviewed yet

### User can't add bank account after approval
- Check expiry date: `GET /api/kyc`
- If expired: User must resubmit new document
- Clear cache: `php artisan cache:clear`

### Admin can't see pending verifications
- Verify admin has Admin role: Check `users.role_id` and `roles.name`
- Check middleware: Route should use `middleware('role:Admin')`

### Document storage issues
- Ensure "private" disk is configured in `config/filesystems.php`
- Check storage directory is readable/writable
- Ensure Laravel can access storage folder
