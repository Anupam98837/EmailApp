<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Campaigns</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Common CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    /* Summary chart cards */
    .chart-card {
      background: #fff;
      border-radius: .5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 1rem;
      text-align: center;
    }
    .chart-card canvas {
      max-width: 100%;
      height: 120px !important;
    }

    /* Pagination styling */
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 1rem;
      gap: .25rem;
    }
    .pager-btn {
      min-width: 2.5rem;
      padding: .5em .75em;
      border: 1px solid #d1d5db;
      border-radius: .5rem;
      background: #fff;
      color: #374151;
      transition: .2s;
    }
    .pager-btn:hover:not(:disabled) {
      background: #f3f4f6;
      border-color: #1e3a8a;
      color: #1e3a8a;
    }

    /* Refresh button spin */
    @keyframes spin { 100% { transform: rotate(360deg); } }
    #refreshBtn.rotating .fa-arrows-rotate { animation: spin 1s linear infinite; }

    /* Pulse animation for running badge */
    @keyframes pulse { 0%,100%{opacity:1;}50%{opacity:0.5;} }
    .badge.running { animation: pulse 1s infinite; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-4">

    <!-- Alerts -->
    <div id="alertBox" class="mb-3" style="display:none;"></div>

    <!-- Top charts -->
    <div class="row mb-4 gx-3">
      <div class="col-md-4">
        <div class="chart-card">
          <h6 class="mb-2">Total vs Active</h6>
          <canvas id="chartTotalActive"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-card">
          <h6 class="mb-2">Sent vs Unsent</h6>
          <canvas id="chartSent"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-card">
          <h6 class="mb-2">Schedules per Day</h6>
          <canvas id="chartSchedule"></canvas>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="input-group w-50">
        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search campaigns…">
      </div>
      <div>
        <button class="btn btn-outline-success btn-sm me-2" id="exportCsv">
          <i class="fa-solid fa-file-csv me-1"></i>Export CSV
        </button>
        <button class="btn btn-outline-secondary btn-sm" id="refreshBtn" title="Refresh">
          <i class="fa-solid fa-arrows-rotate"></i>
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-responsive bg-white rounded shadow-sm p-3">
      <table class="table table-hover text-center mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>List</th>
            <th>Template</th>
            <th>Scheduled At</th>
            <th>Status</th>
            <th>Total Sent</th>
            <th>Progress</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="campaignsBody">
          <tr>
            <td colspan="9" style="height:200px;">
              <div class="d-flex justify-content-center align-items-center h-100">
                <div class="spinner-border" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div id="pager" class="pagination"></div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    let campaigns = [], filtered = [];
    let currentPage = 1, pageSize = 10, totalPages = 1;
    const token = sessionStorage.getItem('token');

    // Chart instances
    let totalActiveChart, sentChart, scheduleChart;

    function flash(type, message) {
      const $box = $('#alertBox');
      $box
        .removeClass()
        .addClass(`alert alert-${type}`)
        .html(message)
        .show();

      setTimeout(() => $box.fadeOut(250), 3000);
    }

    function initCharts() {
      totalActiveChart = new Chart(document.getElementById('chartTotalActive'), {
        type:'doughnut',
        data:{ labels:['Active','Inactive'], datasets:[{ data:[0,0], backgroundColor:['#88B04B','#D3D3D3'] }] },
        options:{ maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
      });
      sentChart = new Chart(document.getElementById('chartSent'), {
        type:'doughnut',
        data:{ labels:['Sent','Unsent'], datasets:[{ data:[0,0], backgroundColor:['#FF6F61','#D3D3D3'] }] },
        options:{ maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
      });
      scheduleChart = new Chart(document.getElementById('chartSchedule'), {
        type:'bar',
        data:{ labels:[], datasets:[{label:'Campaigns', data:[], backgroundColor:'#34568B'}] },
        options:{ maintainAspectRatio:false, scales:{ x:{grid:{display:false}}, y:{beginAtZero:true} } }
      });
    }

    function updateCharts() {
      const total = campaigns.length;
      const active = campaigns.filter(c=>c.is_active).length;
      totalActiveChart.data.datasets[0].data = [active, total-active];
      totalActiveChart.update();

      const sent = campaigns.filter(c=>c.has_run).length;
      sentChart.data.datasets[0].data = [sent, total-sent];
      sentChart.update();

      const counts = {};
      for(let i=6;i>=0;i--){
        const d=new Date(); d.setDate(d.getDate()-i);
        counts[d.toISOString().slice(0,10)] = 0;
      }
      campaigns.forEach(c=>{
        const date=(c.scheduled_at||'').slice(0,10);
        if(counts.hasOwnProperty(date)) counts[date]++;
      });
      scheduleChart.data.labels = Object.keys(counts);
      scheduleChart.data.datasets[0].data = Object.values(counts);
      scheduleChart.update();
    }

    function fetchCampaigns(showSpinner = false) {
      $('#refreshBtn').addClass('rotating');
      if (showSpinner) {
        $('#campaignsBody').html(`
          <tr>
            <td colspan="9" style="height:200px;">
              <div class="d-flex justify-content-center align-items-center h-100">
                <div class="spinner-border" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </td>
          </tr>
        `);
      }

      $.ajax({
        url: `/api/campaign`,
        headers: { Authorization:`Bearer ${token}` }
      })
      .done(res => {
        // handle either res.data (array) or res.data.data
        const data = Array.isArray(res.data) ? res.data : (Array.isArray(res.data.data) ? res.data.data : []);
        campaigns = data;
        updateCharts();
        applyFilter();
      })
      .fail(() => {
        $('#campaignsBody').html(`<tr><td colspan="9" class="text-danger py-4">Error loading campaigns</td></tr>`);
        $('#pager').empty();
      })
      .always(() => {
        $('#refreshBtn').removeClass('rotating');
      });
    }

    function applyFilter() {
      const term = ($('#searchInput').val() || '').toLowerCase();
      filtered = campaigns.filter(c =>
        (c.title||'').toLowerCase().includes(term) ||
        (c.list_title||'').toLowerCase().includes(term) ||
        (c.template_subject||'').toLowerCase().includes(term)
      );
      currentPage = 1;
      totalPages = Math.ceil(filtered.length / pageSize) || 1;
      renderTable();
      renderPager();
    }

    function destroyCampaign(id) {
      if (!token) return;
      if (!confirm('This will permanently delete the campaign and purge related queue jobs. Continue?')) {
        return;
      }

      // find and disable button
      const $btn = $(`#del-${id}`);
      const originalHtml = $btn.html();
      $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

      $.ajax({
        url: `/api/campaign/${id}`,
        type: 'DELETE',
        headers: { Authorization:`Bearer ${token}` }
      })
      .done(res => {
        flash('success', res?.message || 'Campaign deleted.');
        fetchCampaigns(false);
      })
      .fail(xhr => {
        const msg = xhr?.responseJSON?.message || 'Failed to delete campaign.';
        flash('danger', msg);
      })
      .always(() => {
        $btn.prop('disabled', false).html(originalHtml);
      });
    }

    function renderTable(){
      const $b = $('#campaignsBody').empty();
      if (!filtered.length) {
        return $b.html('<tr><td colspan="9" class="text-muted py-4">No campaigns found.</td></tr>');
      }
      const start = (currentPage - 1) * pageSize;
      const end = start + pageSize;
      const pageItems = filtered.slice(start, end);
      pageItems.forEach(c => {
        const sentCount   = Number(c.sent_count || 0);
        const totalSubs   = Number(c.list_length || 0);
        const unsentCount = Math.max(totalSubs - sentCount, 0);
        const sentPct     = totalSubs ? Math.round(sentCount/totalSubs*100) : 0;
        const badgeClass  = ({
          scheduled:'badge-info',
          waiting:  'badge-secondary',
          running:  'badge-warning running',
          completed:'badge-success',
          done:     'badge-success',
          failed:   'badge-danger',
          terminated:'badge-dark'
        }[c.status]||'badge-secondary');

        $b.append(`
          <tr>
            <td>${c.title}</td>
            <td>${c.list_title}</td>
            <td>${c.template_subject}</td>
            <td>${c.scheduled_at ? new Date(c.scheduled_at).toLocaleString() : '-'}</td>
            <td>
              <span class="badge ${badgeClass}">
                ${String(c.status||'').charAt(0).toUpperCase()+String(c.status||'').slice(1)}
              </span>
            </td>
            <td>${totalSubs}</td>
            <td style="min-width:170px">
              <div class="progress" style="height:1rem">
                <div class="progress-bar bg-success" role="progressbar" style="width:${sentPct}%"></div>
                <div class="progress-bar bg-secondary" role="progressbar" style="width:${100-sentPct}%"></div>
              </div>
              <small>${sentCount}/${totalSubs} sent | ${unsentCount} unsent</small>
            </td>
            <td>${new Date(c.created_at).toLocaleString()}</td>
            <td class="d-flex gap-2 justify-content-center">
              <button class="btn btn-sm btn-outline-primary"
                      onclick="location.href='/campaigns/${c.id}/report'"
                      title="View report">
                <i class="fa-solid fa-chart-simple"></i>
              </button>

              <!-- NEW: Destroy button -->
              <button id="del-${c.id}"
                      class="btn btn-sm btn-outline-danger"
                      title="Delete campaign"
                      onclick="destroyCampaign(${c.id})">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        `);
      });
    }

    function renderPager(){
      const $p = $('#pager').empty();
      function btn(html, pageNum, disabled){
        const b = $(`<button class="pager-btn btn btn-outline-primary">${html}</button>`);
        b.prop('disabled', disabled).click(() => {
          if (!disabled) {
            currentPage = pageNum;
            renderTable();
            renderPager();
          }
        });
        return b;
      }
      $p
        .append(btn('<i class="fa-solid fa-angles-left"></i>', 1, currentPage===1))
        .append(btn('<i class="fa-solid fa-angle-left"></i>', currentPage-1, currentPage===1))
        .append(`<span class="align-self-center mx-2">${currentPage}/${totalPages}</span>`)
        .append(btn('<i class="fa-solid fa-angle-right"></i>', currentPage+1, currentPage===totalPages))
        .append(btn('<i class="fa-solid fa-angles-right"></i>', totalPages, currentPage===totalPages));
    }

    $(function(){
      if (!token) return location.href='/';
      initCharts();
      fetchCampaigns(true);
      // auto-refresh every 10s without spinner
      setInterval(() => fetchCampaigns(false), 10000);
      $('#searchInput').on('input', applyFilter);
      $('#refreshBtn').click(() => fetchCampaigns(true));
      $('#exportCsv').click(()=>{
        let csv = 'Title,List,Template,Scheduled,Sent,Created\n';
        filtered.forEach(c=>{
          csv+=`"${c.title}","${c.list_title}","${c.template_subject}",`+
               `"${c.scheduled_at ? new Date(c.scheduled_at).toLocaleString() : ''}",`+
               `${c.has_run},"${new Date(c.created_at).toLocaleString()}"\n`;
        });
        const blob=new Blob([csv],{type:'text/csv'});
        const url=URL.createObjectURL(blob);
        const a=document.createElement('a');
        a.href=url; a.download='campaigns.csv'; a.click();
        URL.revokeObjectURL(url);
      });
    });
  </script>
</body>
</html>
