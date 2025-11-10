{{-- resources/views/dashboard.blade.php --}}

@extends('pages.users.admin.layout.structure')

@section('title', 'Admin Dashboard')
@section('header', 'Admin Dashboard')

@push('styles')
<style>
  :root {
    --pn-classic-blue:   #0F4C81;
    --pn-aspengold:      #FFB703;
    --pn-peach-fuzz:     #F4A261;
    --pn-teal:           #2A9D8F;
    --pn-soft-gray:      #F1FAEE;
    --pn-card-shadow:    0 4px 18px rgba(0, 0, 0, 0.1);
    --pn-card-radius:    12px;
    --pn-transition:     all 0.25s ease-in-out;
    --pn-gradient-blue:  linear-gradient(135deg, #0F4C81 0%, #1A66B2 100%);
    --pn-gradient-gold:  linear-gradient(135deg, #FFB703 0%, #FFD166 100%);
    --pn-gradient-peach: linear-gradient(135deg, #F4A261 0%, #F7C59F 100%);
    --pn-gradient-teal:  linear-gradient(135deg, #2A9D8F 0%, #4CC9F0 100%);
  }

  body {
    background: #f4f7fa;
  }

  .dashboard-container {
    padding: 2rem 0;
    min-height: calc(100vh - 100px);
  }

  .dashboard-card {
    background: white;
    border: none;
    border-radius: var(--pn-card-radius);
    box-shadow: var(--pn-card-shadow);
    padding: 1.75rem;
    position: relative;
    transition: var(--pn-transition);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(0,0,0,0.12);
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding-bottom: 0.5rem;
  }

  .card-header h5 {
    margin: 0;
    font-weight: 600;
    font-size: 1.1rem;
    color: #2d3748;
  }

  .stat-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    margin-bottom: 1.5rem;
  }

  .stat-card {
    position: relative;
    border-radius: var(--pn-card-radius);
    padding: 1rem 1rem 1.25rem;
    color: white;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .stat-card .label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    opacity: .85;
    margin-bottom: 0.25rem;
  }

  .stat-card .value {
    font-size: 1.9rem;
    font-weight: 700;
    line-height: 1.1;
  }

  .stat-card .sub {
    font-size: 0.65rem;
    margin-top: 4px;
    opacity: .9;
  }

  .chart-wrapper {
    position: relative;
    min-height: 320px;
    display: flex;
    flex-direction: column;
  }

  .activity-feed {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 650px;
    overflow: auto;
  }
  .activity-item {
    display: flex;
    padding: .75rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    align-items: flex-start;
  }
  .activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--pn-classic-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: .75rem;
    color: white;
    flex-shrink: 0;
    font-size: .75rem;
  }
  .activity-content {
    flex: 1;
  }
  .activity-title {
    margin: 0;
    font-weight: 600;
    font-size: .9rem;
  }
  .activity-time {
    font-size: .65rem;
    color: #718096;
  }

  .overview-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 2rem;
  }
  .overview-col {
    flex: 1 1 250px;
    min-width: 220px;
  }

  @media (max-width: 992px) {
    .overview-row { flex-direction: column; }
    .chart-wrapper { min-height: 240px; }
  }
</style>
@endpush

@section('content')
<div class="dashboard-container">
  <div class="container">
    {{-- Top overview stat cards --}}
    <div class="overview-row mb-4">
      <div class="overview-col">
        <div class="dashboard-card">
          <div class="card-header">
            <h5>Users</h5>
          </div>
          <div class="stat-grid">
            <div class="stat-card" style="background: var(--pn-gradient-teal);">
              <div class="label">Total Users</div>
              <div class="value" id="stat-total_users">—</div>
              <div class="sub">Registered</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-blue);">
              <div class="label">Active</div>
              <div class="value" id="stat-active_users">—</div>
              <div class="sub">Status = active</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-peach);">
              <div class="label">Inactive</div>
              <div class="value" id="stat-inactive_users">—</div>
              <div class="sub">Status = inactive</div>
            </div>
          </div>
        </div>
      </div>
      <div class="overview-col">
        <div class="dashboard-card">
          <div class="card-header">
            <h5>Campaigns</h5>
          </div>
          <div class="stat-grid">
            <div class="stat-card" style="background: var(--pn-gradient-gold);">
              <div class="label">Total</div>
              <div class="value" id="stat-campaigns_total">—</div>
              <div class="sub">All campaigns</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-blue);">
              <div class="label">Run</div>
              <div class="value" id="stat-campaigns_run">—</div>
              <div class="sub">Executed</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-peach);">
              <div class="label">Running</div>
              <div class="value" id="stat-campaigns_running">—</div>
              <div class="sub">Currently active</div>
            </div>
          </div>
        </div>
      </div>
      <div class="overview-col">
        <div class="dashboard-card">
          <div class="card-header">
            <h5>Payments</h5>
          </div>
          <div class="stat-grid">
            <div class="stat-card" style="background: var(--pn-gradient-blue);">
              <div class="label">Total Revenue</div>
              <div class="value" id="stat-total_revenue">—</div>
              <div class="sub">Paid</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-teal);">
              <div class="label">Paid</div>
              <div class="value" id="stat-paid_payments">—</div>
              <div class="sub">Success</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-peach);">
              <div class="label">Failed</div>
              <div class="value" id="stat-failed_payments">—</div>
              <div class="sub">Errors</div>
            </div>
          </div>
        </div>
      </div>
      <div class="overview-col">
        <div class="dashboard-card">
          <div class="card-header">
            <h5>Plans & Mailers</h5>
          </div>
          <div class="stat-grid">
            <div class="stat-card" style="background: var(--pn-gradient-gold);">
              <div class="label">Plans</div>
              <div class="value" id="stat-plans_total">—</div>
              <div class="sub">Subscription plans</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-blue);">
              <div class="label">Active Plans</div>
              <div class="value" id="stat-plans_active">—</div>
              <div class="sub">Status = active</div>
            </div>
            <div class="stat-card" style="background: var(--pn-gradient-teal);">
              <div class="label">Admin Mailers</div>
              <div class="value" id="stat-admin_mailers_total">—</div>
              <div class="sub">Templates</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Charts + recent activity --}}
    <div class="row g-4 mb-4">
      <div class="col-lg-8 d-flex flex-column">
        <div class="dashboard-card mb-3" style="flex:1; display:flex; flex-direction:column;">
          <div class="card-header">
            <h5>Campaign Breakdown</h5>
          </div>
          <div class="chart-wrapper" style="flex:1;">
            <canvas id="chartCampaignBreakdown"></canvas>
          </div>
        </div>
        <div class="dashboard-card" style="flex:1; display:flex; flex-direction:column;">
          <div class="card-header">
            <h5>Payment Status</h5>
          </div>
          <div class="chart-wrapper" style="flex:1;">
            <canvas id="chartPaymentStatus"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="dashboard-card" style="height:100%;">
          <div class="card-header">
            <h5>Recent Activity</h5>
          </div>
          <ul class="activity-feed" id="recentActivity">
            <!-- Populated by JS -->
          </ul>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const token = sessionStorage.getItem('token');
  if (!token) return location.href = '/';

  const toLocale = v => {
    if (typeof v === 'number') return v.toLocaleString();
    if (typeof v === 'string' && !isNaN(Number(v))) return Number(v).toLocaleString();
    return v;
  };

  try {
    const res = await fetch('/api/admin/dashboard', {
      headers: { Authorization: `Bearer ${token}` }
    });
    const json = await res.json();
    if (json.status !== 'success') throw new Error('Failed to load admin dashboard');
    const d = json.data;

    // Fill top stats
    document.getElementById('stat-total_users').innerText = toLocale(d.users.total);
    document.getElementById('stat-active_users').innerText = toLocale(d.users.active);
    document.getElementById('stat-inactive_users').innerText = toLocale(d.users.inactive);

    document.getElementById('stat-campaigns_total').innerText = toLocale(d.campaigns.total);
    document.getElementById('stat-campaigns_run').innerText = toLocale(d.campaigns.run);
    document.getElementById('stat-campaigns_running').innerText = toLocale(d.campaigns.running);

    document.getElementById('stat-total_revenue').innerText = '₹' + toLocale(d.payments.total_revenue);
    document.getElementById('stat-paid_payments').innerText = toLocale(d.payments.paid);
    document.getElementById('stat-failed_payments').innerText = toLocale(d.payments.failed);

    document.getElementById('stat-plans_total').innerText = toLocale(d.subscription_plans.total);
    document.getElementById('stat-plans_active').innerText = toLocale(d.subscription_plans.active);
    document.getElementById('stat-admin_mailers_total').innerText = toLocale(d.admin_mailers.total);

    // Recent activity aggregation
    const activities = [];
    d.recent.users.forEach(u => {
      activities.push({
        type: 'user',
        title: `New User: ${u.name}`,
        time: u.created_at,
        meta: u.email
      });
    });
    d.recent.payments.forEach(p => {
      activities.push({
        type: 'payment',
        title: `Payment ${p.status.toUpperCase()}: ₹${toLocale(p.amount_decimal)}`,
        time: p.created_at,
        meta: `User ${p.user_id}`
      });
    });
    d.recent.campaigns.forEach(c => {
      activities.push({
        type: 'campaign',
        title: `Campaign: ${c.title}`,
        time: c.created_at,
        meta: `Status: ${c.status}`
      });
    });

    activities.sort((a,b)=> new Date(b.time) - new Date(a.time));
    const feed = document.getElementById('recentActivity');
    activities.slice(0, 8).forEach(act => {
      const li = document.createElement('li');
      li.className = 'activity-item';
      const iconClass = act.type === 'user' ? 'fa-user' : act.type === 'payment' ? 'fa-credit-card' : 'fa-bullhorn';
      const bgColor = act.type === 'user' ? 'var(--pn-teal)' : act.type === 'payment' ? 'var(--pn-aspengold)' : 'var(--pn-classic-blue)';
      li.innerHTML = `
        <div class="activity-icon" style="background: ${bgColor}">
          <i class="fa-solid ${iconClass}"></i>
        </div>
        <div class="activity-content">
          <div class="activity-title">${act.title}</div>
          <div class="activity-time">${new Date(act.time).toLocaleString()}</div>
          <div class="activity-time" style="font-size:.6rem; margin-top:2px;">${act.meta}</div>
        </div>
      `;
      feed.appendChild(li);
    });

    // Campaign Breakdown (doughnut)
    new Chart(document.getElementById('chartCampaignBreakdown'), {
      type: 'doughnut',
      data: {
        labels: ['Run', 'Running', 'Scheduled', 'Waiting'],
        datasets: [{
          data: [
            d.campaigns.run,
            d.campaigns.running,
            d.campaigns.scheduled || 0,
            d.campaigns.waiting || 0
          ],
          backgroundColor: [
            'rgba(255, 183, 3, 0.85)',
            'rgba(15, 76, 129, 0.85)',
            'rgba(242, 153, 74, 0.85)',
            'rgba(42, 157, 143, 0.85)'
          ],
          borderWidth: 0
        }]
      },
      options: {
        cutout: '60%',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          datalabels: {
            formatter: (value, ctx) => {
              const sum = ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);
              if (!sum) return '0%';
              return Math.round(value / sum * 100) + '%';
            },
            color: '#fff',
            font: { weight: '600' }
          }
        }
      },
      plugins: [ChartDataLabels]
    });

    // Payment status bar
    new Chart(document.getElementById('chartPaymentStatus'), {
      type: 'bar',
      data: {
        labels: ['Paid', 'Pending', 'Failed'],
        datasets: [{
          label: 'Payments',
          data: [d.payments.paid, d.payments.pending, d.payments.failed],
          backgroundColor: [
            'rgba(42, 157, 143, 0.85)',
            'rgba(15, 76, 129, 0.85)',
            'rgba(229, 62, 62, 0.85)'
          ],
          borderRadius: 6,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0 }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.label}: ${toLocale(ctx.raw)}`
            }
          }
        }
      }
    });

  } catch (err) {
    console.error('Admin dashboard error', err);
    const container = document.querySelector('.dashboard-container .container');
    if (container) {
      const alert = document.createElement('div');
      alert.className = 'alert alert-danger';
      alert.textContent = 'Failed to load admin dashboard data.';
      container.prepend(alert);
    }
  }
});
</script>
@endsection
