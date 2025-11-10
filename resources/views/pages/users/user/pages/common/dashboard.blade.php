@extends('pages.users.user.layout.structure')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@push('styles')
<style>
  :root {
    --pn-classic-blue:   #0F4C81;
    --pn-aspengold:      #FFB703;
    --pn-peach-fuzz:     #F4A261;
    --pn-teal:           #2A9D8F;
    --pn-soft-gray:      #F1FAEE;
    --pn-card-shadow:    0 4px 12px rgba(0, 0, 0, 0.08);
    --pn-card-radius:    12px;
    --pn-transition:     all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    --pn-gradient-blue:  linear-gradient(135deg, #0F4C81 0%, #1A66B2 100%);
    --pn-gradient-gold:  linear-gradient(135deg, #FFB703 0%, #FFD166 100%);
    --pn-gradient-peach: linear-gradient(135deg, #F4A261 0%, #F7C59F 100%);
    --pn-gradient-teal:  linear-gradient(135deg, #2A9D8F 0%, #4CC9F0 100%);
  }

  /* Dashboard container */
  .dashboard-container {
    padding: 2rem 0;
    background-color: #f8fafc;
    min-height: calc(100vh - 80px);
  }

  /* Dashboard cards */
  .dashboard-card {
    height: 100%;
    border: none;
    border-radius: var(--pn-card-radius);
    background: white;
    padding: 1.75rem;
    position: relative;
    transition: var(--pn-transition);
    box-shadow: var(--pn-card-shadow);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  
  .dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  }

  /* Card header styles */
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
    z-index: 2;
  }
  
  .card-header h5 {
    font-weight: 600;
    color: #2d3748;
    margin: 0;
    font-size: 1.1rem;
  }

  /* Stat cards */
  .stat-card {
    position: relative;
    color: white;
    overflow: hidden;
  }
  
  .stat-card::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
  }
  
  .stat-card .card-icon {
    width: 60px;
    height: 60px;
    margin-bottom: 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(5px);
  }
  
  .stat-card .stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
    line-height: 1;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .stat-card .stat-label {
    font-weight: 500;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
  }
  
  .stat-card .stat-link {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
    transition: var(--pn-transition);
    z-index: 3;
  }
  
  .stat-card .stat-link:hover {
    color: white;
    transform: translateX(3px);
  }

  /* Chart cards */
  .chart-card {
    background: white;
  }
  
  .chart-container {
    position: relative;
    flex: 1;
    min-height: 220px;
  }

  /* Metric cards */
  .metric-card {
    text-align: center;
    background: white;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 1.5rem;
  }
  
  .metric-card .metric-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 1rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
  }
  
  .metric-card .metric-value {
    font-size: 2rem;
    font-weight: 600;
    margin: 0.5rem 0;
    color: #1a202c;
  }
  
  .metric-card .metric-label {
    font-weight: 500;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.8rem;
  }
  
  /* Activity feed */
  .activity-feed {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  
  .activity-item {
    display: flex;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  }
  
  .activity-item:last-child {
    border-bottom: none;
  }
  
  .activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
    color: white;
    font-size: 0.9rem;
  }
  
  .activity-content {
    flex: 1;
  }
  
  .activity-title {
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: #2d3748;
  }
  
  .activity-time {
    font-size: 0.75rem;
    color: #718096;
  }

  /* Stats row */
  .stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 2rem;
  }
  
  .stats-row .metric-card {
    flex: 1;
    min-width: 200px;
  }

  /* Responsive adjustments */
  @media (max-width: 992px) {
    .stat-card .stat-value {
      font-size: 2rem;
    }
    .metric-card .metric-value {
      font-size: 1.75rem;
    }
  }

  @media (max-width: 768px) {
    .dashboard-container {
      padding: 1rem 0;
    }
    .stats-row .metric-card {
      min-width: calc(50% - 0.5rem);
    }
  }

  @media (max-width: 576px) {
    .stats-row .metric-card {
      min-width: 100%;
    }
  }
</style>
@endpush

@section('content')
<div class="dashboard-container">
  <div class="container">

    {{-- Top 4 stats with quick-link icons --}}
    <div class="row g-4 mb-4">
      @foreach ([
        ['icon'=>'fa-bullhorn', 'bg'=>'var(--pn-gradient-blue)', 'stat'=>'total_campaigns', 'label'=>'Campaigns', 'link'=>'/user/campaign/view'],
        ['icon'=>'fa-list', 'bg'=>'var(--pn-gradient-teal)', 'stat'=>'total_lists', 'label'=>'Lists', 'link'=>'/user/list/manage'],
        ['icon'=>'fa-file-alt', 'bg'=>'var(--pn-gradient-peach)', 'stat'=>'total_templates', 'label'=>'Templates', 'link'=>'/user/template/manage'],
        ['icon'=>'fa-image', 'bg'=>'var(--pn-gradient-gold)', 'stat'=>'total_media', 'label'=>'Media', 'link'=>'/mailer/manage'],
      ] as $c)
      <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="dashboard-card stat-card" style="background: {{ $c['bg'] }}">
          <a href="{{ $c['link'] }}" class="stat-link"><i class="fa-solid fa-arrow-right"></i></a>
          <div class="card-icon">
            <i class="fa-solid {{ $c['icon'] }}"></i>
          </div>
          <div class="stat-value" id="stat-{{ $c['stat'] }}">—</div>
          <div class="stat-label">{{ $c['label'] }}</div>
        </div>
      </div>
      @endforeach
    </div>

    

    {{-- Main content row --}}
    <div class="row g-4 mb-4">
      {{-- Left column - Charts --}}
      <div class="col-lg-8">
        <div class="row g-4">
          {{-- Campaign Status --}}
          <div class="col-md-6">
            <div class="dashboard-card chart-card">
              <div class="card-header">
                <h5>Campaign Status</h5>
              </div>
              <div class="chart-container">
                <canvas id="chartStatus"></canvas>
              </div>
            </div>
          </div>
          
          {{-- Engagement Metrics --}}
          <div class="col-md-6">
            <div class="dashboard-card chart-card">
              <div class="card-header">
                <h5>Engagement Metrics</h5>
              </div>
              <div class="chart-container">
                <canvas id="chartEngagement"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      {{-- Right column - Activity --}}
      <div class="col-lg-4">
        <div class="dashboard-card">
          <div class="card-header">
            <h5>Recent Activity</h5>
          </div>
          <ul class="activity-feed" id="recentActivity">
            <!-- Will be populated by JavaScript -->
          </ul>
        </div>
      </div>
    </div>

    {{-- Performance metrics row --}}
    <div class="stats-row">
      @foreach ([
        ['icon'=>'fa-play', 'bg'=>'var(--pn-aspengold)', 'stat'=>'campaigns_run', 'label'=>'Campaigns Run'],
        ['icon'=>'fa-envelope-open-text', 'bg'=>'var(--pn-classic-blue)', 'stat'=>'total_opens', 'label'=>'Total Opens'],
        ['icon'=>'fa-hand-pointer', 'bg'=>'var(--pn-peach-fuzz)', 'stat'=>'total_clicks', 'label'=>'Total Clicks'],
        ['icon'=>'fa-users', 'bg'=>'var(--pn-teal)', 'stat'=>'total_subscribers', 'label'=>'Subscribers'],
      ] as $c)
      <div class="dashboard-card metric-card">
        <div class="metric-icon" style="background-color: {{ $c['bg'] }}">
          <i class="fa-solid {{ $c['icon'] }}"></i>
        </div>
        <div class="metric-value" id="stat-{{ $c['stat'] }}">—</div>
        <div class="metric-label">{{ $c['label'] }}</div>
      </div>
      @endforeach
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

  // Get CSS variables
  const rootStyles = getComputedStyle(document.documentElement);
  const COLORS = {
    classicBlue: rootStyles.getPropertyValue('--pn-classic-blue').trim(),
    aspenGold:   rootStyles.getPropertyValue('--pn-aspengold').trim(),
    peachFuzz:   rootStyles.getPropertyValue('--pn-peach-fuzz').trim(),
    teal:        rootStyles.getPropertyValue('--pn-teal').trim(),
    softGray:    rootStyles.getPropertyValue('--pn-soft-gray').trim()
  };

  try {
    const res = await fetch('/api/dashboard', {
      headers: { Authorization: `Bearer ${token}` }
    });
    const json = await res.json();
    if (json.status !== 'success') throw new Error('Failed to load dashboard');
    const d = json.data;

    // Fill stats
    [
      'total_campaigns', 'total_lists', 'total_templates', 'total_media',
      'campaigns_run', 'total_opens', 'total_clicks', 'total_subscribers', 'total_bounces'
    ].forEach(key => {
      const el = document.getElementById(`stat-${key}`);
      if (el) el.innerText = d[key]?.toLocaleString() || '0';
    });

    // Derived values
    const scheduled = d.total_campaigns - d.campaigns_run;

    // Format recent activity
    const formatActivity = (items, type) => {
      return items.map(item => {
        const date = new Date(item.created_at);
        return {
          icon: type === 'campaign' ? 'fa-bullhorn' : 
                type === 'list' ? 'fa-list' : 
                type === 'template' ? 'fa-file-alt' : 'fa-user',
          color: type === 'campaign' ? COLORS.classicBlue : 
                 type === 'list' ? COLORS.teal : 
                 type === 'template' ? COLORS.peachFuzz : COLORS.aspenGold,
          title: type === 'campaign' ? `New Campaign: ${item.title}` : 
                 type === 'list' ? `New List: ${item.title}` : 
                 type === 'template' ? `New Template: ${item.name}` : `New Subscriber: ${item.email}`,
          time: date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
        };
      });
    };

    const recentActivities = [
      ...formatActivity(d.recent_activity.recent_campaigns, 'campaign'),
      ...formatActivity(d.recent_activity.recent_lists, 'list'),
      ...formatActivity(d.recent_activity.recent_templates, 'template'),
      ...formatActivity(d.recent_activity.recent_subscribers, 'subscriber')
    ].sort((a, b) => new Date(b.time) - new Date(a.time)).slice(0, 5);

    // Populate activity feed
    const activityFeed = document.getElementById('recentActivity');
    recentActivities.forEach(activity => {
      const item = document.createElement('li');
      item.className = 'activity-item';
      item.innerHTML = `
        <div class="activity-icon" style="background-color: ${activity.color}">
          <i class="fa-solid ${activity.icon}"></i>
        </div>
        <div class="activity-content">
          <div class="activity-title">${activity.title}</div>
          <div class="activity-time">${activity.time}</div>
        </div>
      `;
      activityFeed.appendChild(item);
    });

    // Campaign Status Chart (Doughnut)
    new Chart(document.getElementById('chartStatus'), {
      type: 'doughnut',
      data: {
        labels: ['Completed', 'Scheduled'],
        datasets: [{
          data: [d.campaigns_run, scheduled],
          backgroundColor: [COLORS.aspenGold, COLORS.classicBlue],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.label}: ${ctx.raw.toLocaleString()}`
            }
          },
          datalabels: {
            color: '#fff',
            formatter: (value) => {
              const total = d.campaigns_run + scheduled;
              return total > 0 ? Math.round(value / total * 100) + '%' : '0%';
            },
            font: {
              weight: 'bold'
            }
          }
        }
      },
      plugins: [ChartDataLabels]
    });

    // Engagement Metrics Chart (Bar)
    new Chart(document.getElementById('chartEngagement'), {
      type: 'bar',
      data: {
        labels: ['Opens', 'Clicks', 'Bounces'],
        datasets: [{
          data: [d.total_opens, d.total_clicks, d.total_bounces],
          backgroundColor: [
            COLORS.teal,
            COLORS.peachFuzz,
            COLORS.classicBlue
          ],
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
              callback: value => value.toLocaleString()
            },
            grid: {
              drawBorder: false,
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: {
              display: false,
              drawBorder: false
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.label}: ${ctx.raw.toLocaleString()}`
            }
          }
        }
      }
    });

  } catch (error) {
    console.error('Dashboard error:', error);
    // Show error to user
    const errorEl = document.createElement('div');
    errorEl.className = 'alert alert-danger';
    errorEl.textContent = 'Failed to load dashboard data. Please try again.';
    document.querySelector('.dashboard-container').prepend(errorEl);
  }
});
</script>
@endsection