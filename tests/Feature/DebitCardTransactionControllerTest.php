<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        $response = $this->get('/api/debit-card-transactions');

        $response->assertStatus(200);
        // Add more assertions as needed, like checking for specific JSON structure or data
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // Create a debit card and associated transaction for another user
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        // Attempt to fetch the list of transactions as the current authenticated user
        $response = $this->get('/api/debit-card-transactions');

        // Assert that the response status is 200, but the response should not contain the other user's transaction
        $response->assertStatus(200);
        // Assert that the other user's transaction is not present in the response JSON
        $response->assertJsonMissing(['debit_card_id' => $otherUserDebitCard->id]);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $data = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 50,
            'currency_code' => 'USD',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/api/debit-card-transactions', $data);

        $response->assertStatus(201);
        // Optionally, assert the response structure or specific data returned
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // Create a debit card for another user
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $data = [
            'debit_card_id' => $otherUserDebitCard->id,
            'amount' => 50,
            'currency_code' => 'USD',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/api/debit-card-transactions', $data);

        // Assert that the response status is 403 (Forbidden) or 404 (Not Found) depending on your application logic
        $response->assertStatus(403); // or 404 if you prefer
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = $this->debitCard->transactions()->create(['amount' => 100, 'currency_code' => 'USD']);

        $response = $this->get("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200);
        // Optionally, assert the response structure or specific data returned
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // Create a debit card and associated transaction for another user
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $transaction = $otherUserDebitCard->transactions()->create(['amount' => 100, 'currency_code' => 'USD']);

        // Attempt to fetch the transaction details as the current authenticated user
        $response = $this->get("/api/debit-card-transactions/{$transaction->id}");

        // Assert that the response status is 403 (Forbidden) or 404 (Not Found) depending on your application logic
        $response->assertStatus(403); // or 404 if you prefer
    }

    // Extra tests as per specific validation scenarios or business logic
}
