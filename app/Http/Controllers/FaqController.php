<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function __invoke(): View
    {
        $faqs = Faq::active()->ordered()->get();

        return view('faqs', [
            'groups' => $faqs->groupBy('category'),
            'faqs' => $faqs,
            'seo' => Seo::make('Frequently Asked Questions', 'Answers to common questions about booking a one-way taxi, fares, tolls, vehicles and support.'),
        ]);
    }
}
