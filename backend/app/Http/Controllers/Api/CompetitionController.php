<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    public function index()
    {
        $competitions = Competition::orderBy('id', 'asc')->get();

        $formatted = $competitions->map(function ($c) {
            $isClosed = $c->isRegistrationClosed();
            $statusText = $c->quota_status_text;
            return [
                'id' => $c->id,
                'title' => $c->title,
                'category' => $c->category,
                'categoryLabel' => $c->category_label,
                'icon' => $c->icon,
                'quota' => $isClosed ? $statusText : 'Tersedia (' . max(0, $c->max_quota - $c->current_quota) . ' sisa)',
                'maxQuota' => $c->max_quota,
                'currentQuota' => $c->current_quota,
                'isFull' => $c->isQuotaFull(),
                'isClosed' => $isClosed,
                'isManuallyClosed' => $c->isManuallyClosed(),
                'statusText' => $statusText,
                'fee' => $c->fee,
                'desc' => $c->description,
                'peserta' => $c->participants_rule,
                'tmDate' => $c->tm_date,
                'contactPerson' => $c->contact_person
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }
}
