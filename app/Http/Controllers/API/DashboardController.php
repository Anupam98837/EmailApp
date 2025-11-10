<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /** Extract & validate Bearer token → user_id */
    private function getAuthenticatedUserId(Request $request)
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
            Log::warning('Dashboard auth failed: no token');
            return response()->json(['status' => 'error', 'message' => 'Token not provided'], 401);
        }

        $tokenHash = hash('sha256', $m[1]);

        $record = DB::table('personal_access_tokens')
            ->where('token', $tokenHash)
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        if (!$record) {
            Log::warning('Dashboard auth failed: invalid token', ['hash' => $tokenHash]);
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
        }

        return $record->tokenable_id;
    }

    /** GET /api/dashboard */
    public function index(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        Log::info('Building dashboard for user', ['user_id' => $userId]);

        // 1) Campaign analytics
        $campaignData = $this->getCampaignAnalytics($userId);

        // 2) List and subscriber analytics
        $listData = $this->getListAnalytics($userId);

        // 3) Template and media analytics
        $templateData = $this->getTemplateAnalytics($userId);

        // 4) Email performance metrics
        $performanceData = $this->getPerformanceMetrics($userId);

        // 5) Recent activity
        $recentActivity = $this->getRecentActivity($userId);

        return response()->json([
            'status' => 'success',
            'data' => array_merge(
                $campaignData,
                $listData,
                $templateData,
                $performanceData,
                ['recent_activity' => $recentActivity]
            ),
        ], 200);
    }

    private function getCampaignAnalytics($userId)
    {
        $totalCampaigns = DB::table('campaigns')
            ->where('user_id', $userId)
            ->count();

        $campaignsRun = DB::table('campaigns')
            ->where('user_id', $userId)
            ->where('has_run', true)
            ->count();

        $campaignsScheduled = DB::table('campaigns')
            ->where('user_id', $userId)
            ->where('has_run', false)
            ->where('is_active', true)
            ->count();

        $campaignsByStatus = DB::table('campaigns')
            ->where('user_id', $userId)
            ->select('is_active', DB::raw('count(*) as count'))
            ->groupBy('is_active')
            ->get()
            ->pluck('count', 'is_active');

        return [
            'total_campaigns' => $totalCampaigns,
            'campaigns_run' => $campaignsRun,
            'campaigns_scheduled' => $campaignsScheduled,
            'active_campaigns' => $campaignsByStatus[1] ?? 0,
            'inactive_campaigns' => $campaignsByStatus[0] ?? 0,
            'campaigns_last_30_days' => DB::table('campaigns')
                ->where('user_id', $userId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),
        ];
    }

    private function getListAnalytics($userId)
    {
        $totalLists = DB::table('lists')
            ->where('user_id', $userId)
            ->count();

        $listIds = DB::table('lists')
            ->where('user_id', $userId)
            ->pluck('id');

        $totalSubscribers = DB::table('list_users')
            ->whereIn('list_id', $listIds)
            ->count();

        $activeSubscribers = DB::table('list_users')
            ->whereIn('list_id', $listIds)
            ->where('is_active', true)
            ->count();

        $subscriberGrowth = DB::table('list_users')
            ->whereIn('list_id', $listIds)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_lists' => $totalLists,
            'active_lists' => DB::table('lists')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->count(),
            'total_subscribers' => $totalSubscribers,
            'active_subscribers' => $activeSubscribers,
            'subscriber_growth' => $subscriberGrowth,
            'lists_last_30_days' => DB::table('lists')
                ->where('user_id', $userId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),
        ];
    }

    private function getTemplateAnalytics($userId)
    {
        $totalTemplates = DB::table('templates')
            ->where('user_id', $userId)
            ->count();

        $totalMedia = DB::table('media')
            ->where('user_id', $userId)
            ->count();

        return [
            'total_templates' => $totalTemplates,
            'active_templates' => DB::table('templates')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->count(),
            'total_media' => $totalMedia,
            'templates_last_30_days' => DB::table('templates')
                ->where('user_id', $userId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),
        ];
    }

    private function getPerformanceMetrics($userId)
    {
        $campaignUuids = DB::table('campaigns')
            ->where('user_id', $userId)
            ->pluck('campaign_uuid');

        $totalOpens = DB::table('campaign_events')
            ->whereIn('campaign_uuid', $campaignUuids)
            ->where('type', 'open')
            ->count();

        $uniqueOpens = DB::table('campaign_events')
            ->whereIn('campaign_uuid', $campaignUuids)
            ->where('type', 'open')
            ->distinct('subscriber_id')
            ->count('subscriber_id');

        $totalClicks = DB::table('campaign_events')
            ->whereIn('campaign_uuid', $campaignUuids)
            ->where('type', 'click')
            ->count();

        $uniqueClicks = DB::table('campaign_events')
            ->whereIn('campaign_uuid', $campaignUuids)
            ->where('type', 'click')
            ->distinct('subscriber_id')
            ->count('subscriber_id');

        $bounces = DB::table('campaign_bounces')
            ->whereIn('campaign_uuid', $campaignUuids)
            ->count();

        $deliveries = DB::table('campaign_deliveries')
            ->whereIn('campaign_uuid', $campaignUuids)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $totalDeliveries = $deliveries->sum();

        return [
            'total_opens'    => $totalOpens,
            'unique_opens'   => $uniqueOpens,
            'total_clicks'   => $totalClicks,
            'unique_clicks'  => $uniqueClicks,
            'total_bounces'  => $bounces,
            'delivery_stats' => $deliveries,
            'open_rate'      => $totalDeliveries > 0 ? round($uniqueOpens / $totalDeliveries * 100, 1) : 0,
            'click_rate'     => $totalDeliveries > 0 ? round($uniqueClicks / $totalDeliveries * 100, 1) : 0,
            'bounce_rate'    => $totalDeliveries > 0 ? round($bounces / $totalDeliveries * 100, 1) : 0,
        ];
    }

    private function getRecentActivity($userId)
    {
        $recentCampaigns = DB::table('campaigns')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentLists = DB::table('lists')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentTemplates = DB::table('templates')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentSubscribers = DB::table('list_users')
            ->whereIn('list_id', function ($q) use ($userId) {
                $q->select('id')->from('lists')->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'recent_campaigns'   => $recentCampaigns,
            'recent_lists'       => $recentLists,
            'recent_templates'   => $recentTemplates,
            'recent_subscribers' => $recentSubscribers,
        ];
    }

/**
 * Resolve authenticated admin ID from Authorization header.
 */
private function getAuthenticatedAdminId(Request $request)
{
    $header = $request->header('Authorization', '');
    if (!$header) {
        Log::warning('Admin dashboard auth failed: no authorization header');
        return null;
    }

    // Accept both plain token or Bearer token
    $token = null;
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        $token = $m[1];
    } else {
        $token = trim($header);
    }

    if (!$token) {
        Log::warning('Admin dashboard auth failed: token missing');
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $record = DB::table('personal_access_tokens')
        ->where('token', $tokenHash)
        ->where('tokenable_type', 'App\\Models\\Admin')
        ->first();

    if (! $record) {
        Log::warning('Admin dashboard auth failed: invalid token', ['hash' => $tokenHash]);
        return null;
    }

    return $record->tokenable_id;
}

/**
 * Build admin dashboard overview array.
 */
private function getAdminOverview(int $adminId)
{
    Log::info('Building admin overview', ['admin_id' => $adminId]);

    // Users
    $totalUsers = DB::table('users')->count();
    $activeUsers = DB::table('users')->where('status', 'active')->count();
    $inactiveUsers = DB::table('users')->where('status', 'inactive')->count();

    // Campaigns
    $totalCampaigns = DB::table('campaigns')->count();
    $campaignsRun = DB::table('campaigns')->where('has_run', true)->count();
    $campaignsRunning = DB::table('campaigns')->where('status', 'running')->count();
    $campaignsScheduled = DB::table('campaigns')
        ->where('has_run', false)
        ->where('status', 'scheduled')
        ->count();
    $campaignsWaiting = DB::table('campaigns')->where('status', 'waiting')->count();

    // Payments
    $totalPayments = DB::table('subscription_payments')->count();
    $paidPayments = DB::table('subscription_payments')->where('status', 'paid')->count();
    $failedPayments = DB::table('subscription_payments')->where('status', 'failed')->count();
    $pendingPayments = DB::table('subscription_payments')->where('status', 'pending')->count();
    $totalRevenue = DB::table('subscription_payments')
        ->where('status', 'paid')
        ->selectRaw('COALESCE(SUM(amount_decimal),0) as revenue')
        ->first()->revenue;

    // Plans
    $totalPlans = DB::table('subscription_plans')->count();
    $activePlans = DB::table('subscription_plans')->where('status', 'active')->count();
    $inactivePlans = DB::table('subscription_plans')->where('status', 'inactive')->count();

    // Admin mailers
    $totalAdminMailers = DB::table('mailer_settings_admin')->count();
    $activeAdminMailers = DB::table('mailer_settings_admin')->where('status', 'active')->count();
    $inactiveAdminMailers = DB::table('mailer_settings_admin')->where('status', 'inactive')->count();

    // Recent activity
    $recentUsers = DB::table('users')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'name', 'email', 'status', 'created_at']);

    $recentPayments = DB::table('subscription_payments')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get([
            'id', 'user_id', 'plan_id', 'amount_decimal', 'billing_cycle', 'currency', 'status', 'created_at'
        ]);

    $recentCampaigns = DB::table('campaigns')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'user_id', 'title', 'status', 'has_run', 'created_at']);

    return [
        'users' => [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'inactive' => $inactiveUsers,
        ],
        'campaigns' => [
            'total' => $totalCampaigns,
            'run' => $campaignsRun,
            'running' => $campaignsRunning,
            'scheduled' => $campaignsScheduled,
            'waiting' => $campaignsWaiting,
        ],
        'payments' => [
            'total_records' => $totalPayments,
            'paid' => $paidPayments,
            'failed' => $failedPayments,
            'pending' => $pendingPayments,
            'total_revenue' => (float)$totalRevenue,
        ],
        'subscription_plans' => [
            'total' => $totalPlans,
            'active' => $activePlans,
            'inactive' => $inactivePlans,
        ],
        'admin_mailers' => [
            'total' => $totalAdminMailers,
            'active' => $activeAdminMailers,
            'inactive' => $inactiveAdminMailers,
        ],
        'recent' => [
            'users' => $recentUsers,
            'payments' => $recentPayments,
            'campaigns' => $recentCampaigns,
        ],
    ];
}

/**
 * Admin dashboard endpoint handler (can be called from route).
 */
public function adminDashboard(Request $request)
{
    $adminId = $this->getAuthenticatedAdminId($request);
    if (! $adminId) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    $overview = $this->getAdminOverview($adminId);

    return response()->json([
        'status' => 'success',
        'data' => $overview,
    ], 200);
}

}
