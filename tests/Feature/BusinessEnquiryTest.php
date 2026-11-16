<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Models\CorporateEnquiry;
use App\Models\PartnerEnquiry;
use App\Models\User;
use App\Rules\IndianMobile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_corporate_enquiry_is_stored_and_manageable(): void
    {
        $this->post(route('corporate.store'), [
            'company_name' => 'Acme Logistics', 'contact_name' => 'Priya', 'phone' => '+91 99400 11223',
            'email' => 'Priya@Acme.example', 'requirement' => 'airport', 'monthly_trips' => '11-50', 'consent' => '1',
        ])->assertRedirect(route('corporate').'#enquiry')->assertSessionHas('status');

        $enquiry = CorporateEnquiry::firstOrFail();
        $this->assertMatchesRegularExpression('/^COR-\d{6}-[A-Z0-9]{5}$/', $enquiry->reference);
        $this->assertSame('+919940011223', $enquiry->phone);
        $this->assertSame('priya@acme.example', $enquiry->email);
        $this->assertSame(EnquiryStatus::New, $enquiry->status);

        $admin = User::where('is_admin', true)->firstOrFail();
        $this->actingAs($admin)->get(route('admin.corporate-enquiries.index', ['q' => 'Acme']))->assertOk()->assertSee($enquiry->reference);
        $this->actingAs($admin)->get(route('admin.corporate-enquiries.show', $enquiry))->assertOk()->assertSee('Airport transfers');
        $this->actingAs($admin)->put(route('admin.corporate-enquiries.update', $enquiry), ['status' => 'contacted', 'admin_notes' => 'Sent proposal'])->assertSessionHasNoErrors();
        $this->assertSame(EnquiryStatus::Contacted, $enquiry->fresh()->status);
    }

    public function test_corporate_enquiry_validation(): void
    {
        $this->post(route('corporate.store'), ['phone' => '12345', 'email' => 'nope'])
            ->assertSessionHasErrors(['company_name', 'contact_name', 'phone', 'email', 'requirement', 'consent']);
        $this->assertDatabaseCount('corporate_enquiries', 0);
    }

    public function test_partner_registration_is_stored_and_can_be_paused(): void
    {
        $this->post(route('partner.store'), [
            'name' => 'Suresh', 'phone' => '9876501234', 'city' => 'Madurai', 'partner_type' => 'owner_driver',
            'vehicle_type' => 'sedan', 'vehicle_count' => 1, 'experience_years' => 8, 'has_commercial_licence' => '1', 'consent' => '1',
        ])->assertRedirect(route('partner').'#register');

        $partner = PartnerEnquiry::firstOrFail();
        $this->assertMatchesRegularExpression('/^PTR-\d{6}-[A-Z0-9]{5}$/', $partner->reference);
        $this->assertTrue($partner->has_commercial_licence);

        $this->actingAs(User::where('is_admin', true)->firstOrFail())->get(route('admin.partner-enquiries.index'))->assertOk()->assertSee('Suresh');

        settings()->setMany(['partner_programme_open' => false]);
        $this->get(route('partner'))->assertOk()->assertSee('Registrations paused');
        $this->post(route('partner.store'), ['name' => 'X'])->assertForbidden();
    }

    public function test_indian_mobile_normaliser(): void
    {
        $this->assertSame('+919840012345', IndianMobile::normalize('98400 12345'));
        $this->assertSame('+919840012345', IndianMobile::normalize('+91-98400-12345'));
        $this->assertSame('+919840012345', IndianMobile::normalize('09840012345'));
        $this->assertNull(IndianMobile::normalize('1234567890'));
        $this->assertNull(IndianMobile::normalize('98400123456'));
        $this->assertNull(IndianMobile::normalize(['9840012345']));
    }

    public function test_app_links_only_appear_when_configured(): void
    {
        $this->get(route('app'))->assertOk()->assertSee('coming soon')->assertDontSee('Google Play')->assertDontSee('Scan to download');

        settings()->setMany(['app_play_store_url' => 'https://play.google.com/store/apps/details?id=com.example.onewaytaxi']);

        $this->get(route('app'))->assertSee('Google Play')->assertSee('https://play.google.com/store/apps/details?id=com.example.onewaytaxi', false)
            ->assertSee('data:image/svg+xml;base64,', false)->assertSee('Scan to download');
    }
}
