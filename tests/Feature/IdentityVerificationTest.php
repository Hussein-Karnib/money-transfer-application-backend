<?php

/**
 * IDENTITY VERIFICATION & BANK ACCOUNT - INTEGRATION TEST
 * 
 * This script demonstrates and tests the complete workflow:
 * 1. User submits ID/Passport for verification
 * 2. Admin reviews and approves/rejects
 * 3. User can add bank accounts only after approval
 */

// ============================================
// SETUP: Create test users
// ============================================

// Create regular user
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'email_verified_at' => now(),
    'phone_verified_at' => now(),
]);

// Create admin user
$admin = User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'role_id' => Role::where('name', 'Admin')->first()->id,
]);

echo "✓ Test users created\n";

// ============================================
// TEST 1: Check verification status (not verified)
// ============================================

echo "\n=== TEST 1: Check Status (Not Verified) ===\n";

$status = $user->verificationStatus(); // 'not_submitted'
echo "Status: $status\n";
assert($status === 'not_submitted', 'Should be not_submitted');

$canAdd = $user->canAddBankAccount();
echo "Can add bank account: " . ($canAdd['allowed'] ? 'Yes' : 'No') . "\n";
echo "Reason: " . $canAdd['reason'] . "\n";
assert(!$canAdd['allowed'], 'Should not be able to add bank account');
assert($canAdd['reason'] === 'KYC_NOT_VERIFIED', 'Reason should be KYC_NOT_VERIFIED');

echo "✓ TEST 1 PASSED\n";

// ============================================
// TEST 2: Submit verification document
// ============================================

echo "\n=== TEST 2: Submit Verification Document ===\n";

$verification = UserVerification::create([
    'user_id' => $user->id,
    'id_type' => 'passport',
    'id_number' => 'AB1234567',
    'document_path' => 'kyc-documents/test-passport.jpg',
    'expiry_date' => now()->addYears(5),
    'status' => 'pending',
]);

echo "Verification ID: " . $verification->id . "\n";
echo "Status: " . $verification->status . "\n";
assert($verification->status === 'pending', 'Should be pending');
assert($user->hasPendingVerification(), 'User should have pending verification');

$status = $user->verificationStatus(); // 'pending'
echo "New status: $status\n";
assert($status === 'pending', 'Should be pending');

$canAdd = $user->canAddBankAccount();
assert(!$canAdd['allowed'], 'Should not be able to add bank account while pending');
assert($canAdd['reason'] === 'KYC_PENDING', 'Reason should be KYC_PENDING');

echo "✓ TEST 2 PASSED\n";

// ============================================
// TEST 3: Admin approves verification
// ============================================

echo "\n=== TEST 3: Admin Approves Verification ===\n";

$verification->update([
    'status' => 'verified',
    'verified_at' => now(),
    'review_comment' => 'Document is clear and valid',
]);

echo "Status: " . $verification->status . "\n";
assert($verification->status === 'verified', 'Should be verified');

$status = $user->refresh()->verificationStatus(); // 'verified'
echo "New user status: $status\n";
assert($status === 'verified', 'User status should be verified');

assert($user->hasVerifiedIdentity(), 'User should have verified identity');
assert(!$user->hasPendingVerification(), 'User should not have pending verification');

$canAdd = $user->canAddBankAccount();
assert($canAdd['allowed'], 'Should be able to add bank account');
assert($canAdd['reason'] === null, 'No reason for allowed state');

echo "✓ TEST 3 PASSED\n";

// ============================================
// TEST 4: User adds bank account
// ============================================

echo "\n=== TEST 4: User Adds Bank Account ===\n";

$bankAccount = UserBankAccount::create([
    'user_id' => $user->id,
    'bank_name' => 'My USD Account',
    'account_number' => '4111111111111111', // Valid Visa test card
    'currency_code' => 'USD',
    'status' => 'pending',
]);

echo "Bank Account ID: " . $bankAccount->id . "\n";
echo "Currency: " . $bankAccount->currency_code . "\n";
assert($bankAccount->user_id === $user->id, 'Should belong to user');

echo "✓ TEST 4 PASSED\n";

// ============================================
// TEST 5: Test document expiry
// ============================================

echo "\n=== TEST 5: Test Document Expiry ===\n";

// Create expired document
$expiredVerification = UserVerification::create([
    'user_id' => $user->id,
    'id_type' => 'national_id',
    'id_number' => 'ID123456',
    'document_path' => 'kyc-documents/expired-id.jpg',
    'expiry_date' => now()->subDays(30), // Expired 30 days ago
    'status' => 'verified',
    'verified_at' => now()->subMonths(6),
]);

assert($expiredVerification->isExpired(), 'Should be expired');
assert(!$expiredVerification->isValid(), 'Should not be valid');

echo "✓ TEST 5 PASSED\n";

// ============================================
// TEST 6: Test renewal warning
// ============================================

echo "\n=== TEST 6: Test Renewal Warning ===\n";

$renewalVerification = UserVerification::create([
    'user_id' => $user->id,
    'id_type' => 'drivers_license',
    'id_number' => 'DL123456',
    'document_path' => 'kyc-documents/drivers-license.jpg',
    'expiry_date' => now()->addDays(15), // Expires in 15 days
    'status' => 'verified',
    'verified_at' => now()->subMonths(3),
]);

assert($renewalVerification->isValid(), 'Should still be valid');
assert($renewalVerification->needsRenewal(), 'Should need renewal');
assert($renewalVerification->daysUntilExpiry() === 15, 'Days should be 15');

echo "Days until expiry: " . $renewalVerification->daysUntilExpiry() . "\n";

echo "✓ TEST 6 PASSED\n";

// ============================================
// TEST 7: Test helper methods
// ============================================

echo "\n=== TEST 7: Test Helper Methods ===\n";

// Test format methods
$formatted = $verification->getIdTypeText(); // 'Passport'
assert($formatted === 'Passport', 'Should format id type');

$statusText = $verification->getStatusText(); // 'Verified'
assert($statusText === 'Verified', 'Should format status');

// Test scopes
$verified = UserVerification::verified()->count();
echo "Verified verifications: $verified\n";

$pending = UserVerification::pending()->count();
echo "Pending verifications: $pending\n";

$valid = UserVerification::valid()->count();
echo "Valid verifications: $valid\n";

echo "✓ TEST 7 PASSED\n";

// ============================================
// TEST 8: Test rejection workflow
// ============================================

echo "\n=== TEST 8: Test Rejection Workflow ===\n";

$user2 = User::factory()->create([
    'email_verified_at' => now(),
    'phone_verified_at' => now(),
]);

$rejectedVer = UserVerification::create([
    'user_id' => $user2->id,
    'id_type' => 'passport',
    'id_number' => 'BLURRY123',
    'document_path' => 'kyc-documents/blurry.jpg',
    'status' => 'pending',
]);

// Admin rejects
$rejectedVer->update([
    'status' => 'rejected',
    'review_comment' => 'Image is too blurry, please resubmit',
]);

assert($rejectedVer->isRejected(), 'Should be rejected');
assert(!$user2->hasVerifiedIdentity(), 'User should not have verified identity');

$canAdd = $user2->canAddBankAccount();
assert(!$canAdd['allowed'], 'Should not be able to add bank account');
assert($canAdd['reason'] === 'KYC_NOT_VERIFIED', 'Should prompt to resubmit');

echo "✓ TEST 8 PASSED\n";

// ============================================
// SUMMARY
// ============================================

echo "\n";
echo "===========================================\n";
echo "✅ ALL TESTS PASSED\n";
echo "===========================================\n";
echo "Verified Workflow:\n";
echo "1. User submits document (pending)\n";
echo "2. Admin approves/rejects\n";
echo "3. User can add bank accounts (if approved)\n";
echo "4. Document expiry tracking works\n";
echo "5. Helper methods function correctly\n";
echo "6. Error codes are returned properly\n";
echo "===========================================\n\n";

?>
