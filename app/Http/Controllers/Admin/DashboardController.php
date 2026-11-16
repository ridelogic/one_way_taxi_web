<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\PartnerEnquiry;
use App\Models\TaxiRoute;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /** Number of months shown in the monthly booking and revenue charts. */
    private const MONTHS = 6;

    /** Number of days in each KPI sparkline. */
    private const SPARK_DAYS = 14;

    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'kpis' => [
                $this->kpi('Total Bookings', Booking::query(), 'calendar', 'blue', route('admin.bookings.index')),
                $this->kpi('Active Routes', TaxiRoute::active(), 'route', 'green', route('admin.routes.index', ['status' => 'active'])),
                // There is no separate driver table: drivers and vehicle owners register through the partner form.
                $this->kpi('Registered Drivers', PartnerEnquiry::query(), 'users', 'orange', route('admin.partner-enquiries.index')),
                $this->kpi('Customer Enquiries', ContactMessage::query(), 'message', 'purple', route('admin.messages.index')),
            ],
            'statusCounts' => Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'days' => $this->dailyBookings(7),
            'months' => $this->monthlyTotals(),
            'recentBookings' => Booking::with(['pickupCity', 'destinationCity', 'vehicleCategory'])->latest()->limit(8)->get(),
        ]);
    }

    /**
     * One KPI card: the current total, how many were added in the last 7 days and a daily sparkline.
     *
     * @return array{label: string, total: int, week: int, spark: list<int>, icon: string, tone: string, href: string}
     */
    private function kpi(string $label, Builder $query, string $icon, string $tone, string $href): array
    {
        $spark = $this->dailyCounts(clone $query, self::SPARK_DAYS);

        return [
            'label' => $label, 'total' => (clone $query)->count(), 'week' => array_sum(array_slice($spark, -7)),
            'spark' => $spark, 'icon' => $icon, 'tone' => $tone, 'href' => $href,
        ];
    }

    /**
     * Records created per day for the last $days days, oldest first (grouped in PHP so it works on every driver).
     *
     * @return list<int>
     */
    private function dailyCounts(Builder $query, int $days): array
    {
        $start = today()->subDays($days - 1);
        $counts = array_fill_keys(array_map(fn ($i) => $start->copy()->addDays($i)->toDateString(), range(0, $days - 1)), 0);
        $query->where('created_at', '>=', $start)->toBase()->pluck('created_at')
            ->each(function ($createdAt) use (&$counts) {
                $day = Carbon::parse($createdAt)->toDateString();
                if (isset($counts[$day])) {
                    $counts[$day]++;
                }
            });

        return array_values($counts);
    }

    /**
     * Booking enquiries per day for the booking overview chart.
     *
     * @return list<array{label: string, title: string, bookings: int}>
     */
    private function dailyBookings(int $days): array
    {
        $counts = $this->dailyCounts(Booking::query(), $days);
        $start = today()->subDays($days - 1);

        return array_map(fn ($i) => [
            'label' => $start->copy()->addDays($i)->format('d M'),
            'title' => $start->copy()->addDays($i)->format('l, j F Y'),
            'bookings' => $counts[$i],
        ], range(0, $days - 1));
    }

    /**
     * Enquiries and estimated fares per month for the last six months (oldest first), grouped in PHP so it works on
     * every database driver. Revenue is the sum of the fare estimates of enquiries that were not cancelled: the CMS
     * records no payments, so these are estimates, not takings.
     *
     * @return list<array{label: string, title: string, bookings: int, paise: int}>
     */
    private function monthlyTotals(): array
    {
        $start = now()->startOfMonth()->subMonths(self::MONTHS - 1);
        $months = [];
        for ($i = 0; $i < self::MONTHS; $i++) {
            $month = $start->copy()->addMonths($i);
            $months[$month->format('Y-m')] = ['label' => $month->format('M'), 'title' => $month->format('F Y'), 'bookings' => 0, 'paise' => 0];
        }

        Booking::query()->where('created_at', '>=', $start)->toBase()
            ->select(['created_at', 'estimated_fare', 'status'])->orderBy('id')
            ->each(function (object $row) use (&$months) {
                $key = Carbon::parse($row->created_at)->format('Y-m');
                if (! isset($months[$key])) {
                    return;
                }
                $months[$key]['bookings']++;
                if ($row->status !== BookingStatus::Cancelled->value && $row->estimated_fare !== null) {
                    $months[$key]['paise'] += Money::toPaise($row->estimated_fare);
                }
            });

        return array_values($months);
    }
}
