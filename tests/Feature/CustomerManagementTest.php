<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    protected User $admin;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@pharmacy.com')->first();
        $this->cashier = User::where('email', 'cashier@pharmacy.com')->first();
    }

    public function test_admin_can_view_customers_directory()
    {
        $response = $this->actingAs($this->admin)->get(route('customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Customer Directory');
        $response->assertSee('Add New Customer');
    }

    public function test_admin_can_create_new_customer()
    {
        $payload = [
            'name' => 'Wimal Weerakoon',
            'phone' => '+94719998877',
            'nic_number' => '198765432100',
            'credit_limit' => 20000,
            'address' => '78 Peradeniya Road, Kandy'
        ];

        $response = $this->actingAs($this->admin)->post(route('customers.store'), $payload);
        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'phone' => '+94719998877',
            'name' => 'Wimal Weerakoon',
            'credit_limit' => 20000,
        ]);
    }

    public function test_admin_can_update_customer_details()
    {
        $customer = Customer::where('phone', '+94719998877')->first();
        if (!$customer) {
            $customer = Customer::create([
                'name' => 'Wimal Weerakoon',
                'phone' => '+94719998877',
                'nic_number' => '198765432100',
                'credit_limit' => 20000,
                'address' => '78 Peradeniya Road, Kandy'
            ]);
        }

        $updateData = [
            'name' => 'Wimal Weerakoon (Updated)',
            'phone' => '+94719998877', // Same phone should pass unique validation
            'nic_number' => '198765432100',
            'credit_limit' => 35000,
            'address' => '100 Temple Road, Kandy'
        ];

        $response = $this->actingAs($this->admin)->put(route('customers.update', $customer->id), $updateData);
        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertEquals('Wimal Weerakoon (Updated)', $customer->name);
        $this->assertEquals(35000, (float) $customer->credit_limit);
        $this->assertEquals('100 Temple Road, Kandy', $customer->address);

        // Clean up
        $customer->delete();
    }

    public function test_pos_ajax_can_create_customer_via_json()
    {
        $payload = [
            'name' => 'POS Fast Customer',
            'phone' => '+94761112233',
            'nic_number' => '200012345678',
            'credit_limit' => 15000,
            'address' => 'Colombo 03'
        ];

        $response = $this->actingAs($this->cashier)
            ->postJson(route('customers.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customer' => [
                'name' => 'POS Fast Customer',
                'phone' => '+94761112233',
            ]
        ]);

        Customer::where('phone', '+94761112233')->delete();
    }
}
