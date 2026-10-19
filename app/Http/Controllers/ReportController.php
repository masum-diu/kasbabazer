<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Comment;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function store(ReportRequest $request): RedirectResponse
    {
        $class = ['listing' => Listing::class, 'comment' => Comment::class, 'user' => User::class][$request->validated('type')];
        $target = $class::whereKey($request->validated('id'))->firstOrFail();

        $report = new Report($request->safe()->only(['reason', 'description']));
        $report->forceFill(['user_id' => $request->user()->id])
            ->reportable()->associate($target);
        $report->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Report submitted. Thank you.']);

        return back();
    }
}
