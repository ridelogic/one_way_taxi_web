<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverEnrollment;
use App\Services\Fleet\DriverEnrollmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Website driver enrollments. Each one is created in the fleet admin as a Deactive driver + taxi; activate it there
 * (Driver → Manage Drivers). This screen shows whether it reached the fleet admin and lets the team resend failures.
 */
class DriverEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), [DriverEnrollment::PENDING, DriverEnrollment::SENT, DriverEnrollment::FAILED], true) ? $request->query('status') : null;
        $term = trim((string) $request->query('q'));

        return view('admin.driver-enrollments.index', [
            'status' => $status,
            'search' => $term,
            'enrollments' => DriverEnrollment::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('reference', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")->orWhere('taxi_no', 'like', "%{$term}%")))
                ->latest()->paginate(25)->withQueryString(),
            'counts' => DriverEnrollment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /** Send a failed / pending enrollment to the fleet admin again. */
    public function resend(DriverEnrollment $enrollment, DriverEnrollmentService $service): RedirectResponse
    {
        if ($enrollment->status === DriverEnrollment::SENT) {
            return back()->with('status', "{$enrollment->reference} is already in the fleet admin (driver #{$enrollment->fleet_driver_id}).");
        }

        $result = $service->send($enrollment->fresh());

        return $result['ok']
            ? back()->with('status', "{$enrollment->reference} sent — fleet driver #{$enrollment->fresh()->fleet_driver_id} (inactive until activated).")
            : back()->with('error', "{$enrollment->reference} could not be sent: ".implode(' ', $result['errors']));
    }
}
