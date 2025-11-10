<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function getAuthenticatedUserId(Request $request)
    {
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/Bearer\s(\S+)/', $header, $m)) {
            abort(response()->json(['status'=>'error','message'=>'Token not provided'],401));
        }
        $record = DB::table('personal_access_tokens')
            ->where('token', hash('sha256',$m[1]))
            ->where('tokenable_type','App\\Models\\User')
            ->first();
        if (! $record) {
            abort(response()->json(['status'=>'error','message'=>'Invalid token'],401));
        }
        return $record->tokenable_id;
    }

    /**
     * GET /api/reports/{campaignId}/overview
     */
    public function overview(Request $request, $campaignId)
    {
        $userId = $this->getAuthenticatedUserId($request);

        // 1) Load campaign + template
        $campaign = DB::table('campaigns')
            ->where('campaigns.id',$campaignId)
            ->where('campaigns.user_id',$userId)
            ->first(['campaigns.*']);
        if (! $campaign) {
            return response()->json(['status'=>'error','message'=>'Campaign not found'],404);
        }
        $template = DB::table('templates')
            ->where('id',$campaign->template_id)
            ->first(['body_html']);

        // 2) Subscribers
        $subs = DB::table('list_users')
            ->where('list_id',$campaign->list_id)
            ->pluck('email')
            ->toArray();

        // 3) Totals
        $uuid = $campaign->campaign_uuid;
        $totalSent   = DB::table('campaign_deliveries')
                          ->where('campaign_uuid',$uuid)
                          ->whereNotIn('status',['skipped'])
                          ->count();
        $opens       = DB::table('campaign_events')->where('campaign_uuid',$uuid)->where('type','open')->count();
        $uniqueOpens = DB::table('campaign_events')->where('campaign_uuid',$uuid)->where('type','open')->distinct('subscriber_id')->count('subscriber_id');
        $clicks      = DB::table('campaign_events')->where('campaign_uuid',$uuid)->where('type','click')->count();
        $uniqueClicks= DB::table('campaign_events')->where('campaign_uuid',$uuid)->where('type','click')->distinct('subscriber_id')->count('subscriber_id');
        $bounces     = DB::table('campaign_bounces')->where('campaign_uuid',$uuid)->count();
        $hardBounce  = DB::table('campaign_bounces')->where('campaign_uuid',$uuid)->where('bounce_type','hard')->count();
        $softBounce  = DB::table('campaign_bounces')->where('campaign_uuid',$uuid)->where('bounce_type','soft')->count();

        // 4) Rates
        $openRate  = $totalSent ? round($uniqueOpens / $totalSent * 100, 1) : 0;
        $clickRate = $totalSent ? round($uniqueClicks / $totalSent * 100, 1) : 0;

        return response()->json([
            'status'=>'success',
            'data'=>[
                'campaign_id'       => $campaignId,
                'title'             => $campaign->title,
                'scheduled_at'      => $campaign->scheduled_at,
                'template_body_html'=> $template->body_html,
                'subscriber_emails' => $subs,
                'total_sent'        => $totalSent,
                'total_opens'       => $opens,
                'unique_opens'      => $uniqueOpens,
                'open_rate'         => $openRate,
                'total_clicks'      => $clicks,
                'unique_clicks'     => $uniqueClicks,
                'click_rate'        => $clickRate,
                'total_bounces'     => $bounces,
                'hard_bounces'      => $hardBounce,
                'soft_bounces'      => $softBounce,
            ],
        ],200);
    }

    /**
     * GET /api/reports/{campaignId}/detailed
     */
    public function detailed(Request $request, $campaignId)
{
    $userId = $this->getAuthenticatedUserId($request);

    // 1) Load campaign
    $campaign = DB::table('campaigns')
        ->where('id', $campaignId)
        ->where('user_id', $userId)
        ->first(['campaigns.*','campaigns.title']);
    if (! $campaign) {
        return response()->json(['status'=>'error','message'=>'Campaign not found'],404);
    }
    $uuid = $campaign->campaign_uuid;

    // 2) Events & deliveries
    $opens        = DB::table('campaign_events')->where('campaign_uuid',$uuid)->where('type','open')->get();
    $clicks       = DB::table('campaign_events')->where('campaign_uuid',$uuid)->where('type','click')->get();
    $unsubs       = DB::table('campaign_events')->where('campaign_uuid',$uuid)->where('type','unsubscribe')->pluck('subscriber_id')->toArray();
    $deliveries   = DB::table('campaign_deliveries')
                       ->where('campaign_uuid',$uuid)
                       ->get()
                       ->keyBy('subscriber_id');
    $subs = DB::table('list_users')
        ->where('list_id',$campaign->list_id)
        ->get();

    $rows = [];
    foreach ($subs as $sub) {
        $d        = $deliveries->get($sub->id);
        $opened   = $opens->where('subscriber_id',$sub->id)->count();
        $clicked  = $clicks->where('subscriber_id',$sub->id)->count();
        $bounce   = DB::table('campaign_bounces')
                      ->where('campaign_uuid',$uuid)
                      ->where('subscriber_id',$sub->id)
                      ->first();
        $skip     = DB::table('campaign_skips')
                      ->where('campaign_uuid',$uuid)
                      ->where('subscriber_id',$sub->id)
                      ->first();
        $isUnsub  = in_array($sub->id, $unsubs);

        $rows[] = [
            'campaign_title'   => $campaign->title,
            'name'             => $sub->name,
            'email'            => $sub->email,
            'phone'            => $sub->phone,
            'sent_at'          => $d->created_at ?? null,
            'status'           => $d->status ?? 'not_sent',
            'open_count'       => $opened,
            'click_count'      => $clicked,
            'bounced'          => (bool)$bounce,
            'bounce_type'      => $bounce->bounce_type ?? null,
            'skip_reason'      => $skip->reason ?? null,
            'failure_reason'   => $d->error_user_message ?? null,
            'unsubscribed'     => $isUnsub,
        ];
    }

    return response()->json([
        'status' => 'success',
        'data'   => ['subscribers' => $rows],
    ], 200);
}

}
