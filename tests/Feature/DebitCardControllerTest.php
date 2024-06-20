<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        $response = $this->get('/api/debit-cards');

        $response->assertStatus(200);
        // Add more assertions as needed, like checking for specific JSON structure or data
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // Create a debit card for another user
        $otherUser = User::factory()->create();
        DebitCard::factory()->create(['user_id' => $otherUser->id]);

        // Attempt to fetch the list of debit cards as the current authenticated user
        $response = $this->get('/api/debit-cards');

        // Assert that the response status is 200, but the response should not contain the other user's debit card
        $response->assertStatus(200);
        // Assert that the other user's debit card is not present in the response JSON
        $response->assertJsonMissing(['user_id' => $otherUser->id]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $data = [
            'card_number' => '1234567812345678',
            'expiry_date' => '12/23',
            // Add other required fields for creating a debit card
        ];

        $response = $this->postJson('/api/debit-cards', $data);

        $response->assertStatus(201);
        // Optionally, assert the response structure or specific data returned
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200);
        // Optionally, assert the response structure or specific data returned
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // Create a debit card for another user
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        // Attempt to fetch the debit card details as the current authenticated user
        $response = $this->get("/api/debit-cards/{$debitCard->id}");

        // Assert that the response status is 403 (Forbidden) or 404 (Not Found) depending on your application logic
        $response->assertStatus(403); // or 404 if you prefer
    }

    public function testCustomerCanUpdateADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'card_number' => 'updated-card-number',
            'expiry_date' => '12/25',
            // Add other fields to update
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $data);

        $response->assertStatus(200);
        // Optionally, assert the response structure or specific data returned
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->delete("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);
        // Optionally, assert other post-delete conditions
    }

    // Extra tests as per specific validation scenarios or business logic
}
