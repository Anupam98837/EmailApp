<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Campaign Report</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- SheetJS -->
  <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    .card-stat {
      background: #fff;
      border-radius: .75rem;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      padding: 1.25rem;
      text-align: left;
    }
    .card-stat h6 { font-weight: 600; color: #555; }
    .card-stat .value { font-size: 1.75rem; font-weight: 700; color: #212529; }

    .chart-card {
      background: #fff;
      border-radius: .75rem;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      padding: 1rem;
      text-align: center;
      margin-bottom: 1rem;
    }
    .chart-card canvas { width: 100%; height: 160px!important; }

    .filter-bar {
      margin-bottom: 1rem;
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .badge-status { font-size: .85em; text-transform: uppercase; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 id="campaignTitle" class="mb-0">
        <i class="fa-solid fa-chart-line text-primary me-2"></i>Campaign Report
      </h2>
      <button class="btn btn-outline-secondary" onclick="history.back()">
        <i class="fa-solid fa-arrow-left"></i> Back
      </button>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overviewPane">Overview</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#previewPane">Template Preview</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#detailedPane">Detailed</button>
      </li>
    </ul>

    <div class="tab-content">
      <!-- OVERVIEW -->
      <div class="tab-pane fade show active" id="overviewPane">
        <div class="row gx-3 mb-4">
          <div class="col-md-4">
            <div class="chart-card"><h6>Sent vs Opens (%)</h6><canvas id="chartTotalOpen"></canvas></div>
          </div>
          <div class="col-md-4">
            <div class="chart-card"><h6>Sent vs Clicks (%)</h6><canvas id="chartTotalClick"></canvas></div>
          </div>
          <div class="col-md-4">
            <div class="chart-card"><h6>Bounces Hard vs Soft</h6><canvas id="chartBounces"></canvas></div>
          </div>
        </div>
        <div id="overviewStats" class="row gx-3"></div>
      </div>

      <!-- TEMPLATE PREVIEW -->
      <div class="tab-pane fade" id="previewPane">
        <div class="bg-white p-3 rounded shadow-sm">
          <iframe id="templateIframe" style="width:100%;height:600px;border:none;"></iframe>
        </div>
      </div>

      <!-- DETAILED -->
      <div class="tab-pane fade" id="detailedPane">
        <div class="filter-bar">
          <div class="d-flex align-items-center gap-2">
            <label for="statusFilter" class="mb-0">Status:</label>
            <select id="statusFilter" class="form-select w-auto">
              <option value="all">All</option>
              <option value="sent">Sent</option>
              <option value="skipped">Skipped</option>
              <option value="soft_bounce">Soft Bounce</option>
              <option value="hard_bounce">Hard Bounce</option>
              <option value="failed">Failed</option>
            </select>
          </div>

          <div class="d-flex align-items-center gap-2">
            <label for="engagementFilter" class="mb-0">Engagement:</label>
            <select id="engagementFilter" class="form-select w-auto">
              <option value="all">All</option>
              <option value="opens">Opens</option>
              <option value="clicks">Clicks</option>
              <option value="both">Both</option>
              <option value="unsubscribes">Unsubscribes</option>
            </select>
          </div>

          <div class="ms-auto d-flex gap-2">
            <button id="exportExcelBtn" class="btn btn-danger">
              <i class="fa-solid fa-file-export me-1"></i> Export Excel
            </button>
            <button id="exportCsvBtn" class="btn btn-outline-primary">
              <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </button>
          </div>
        </div>

        <div class="table-responsive bg-white p-3 rounded shadow-sm">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Campaign</th>
                <th>Email</th>
                <th>Sent At</th>
                <th>Opens</th>
                <th>Clicks</th>
                <th>Unsubscribed</th>
                <th>Bounced</th>
                <th>Skipped</th>
                <th>Status</th>
                <th>Failure</th>
              </tr>
            </thead>
            <tbody id="detailedBody">
              <tr><td colspan="10" class="py-4 text-center text-muted">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const campaignId = (location.pathname.match(/\d+/)||[])[0];
    const token = sessionStorage.getItem('token');
    let allRows = [], displayedRows = [];

    let chartTotalOpen, chartTotalClick, chartBounces;

    function initCharts(){
      chartTotalOpen = new Chart($('#chartTotalOpen'), {
        type:'doughnut',
        data:{ labels:['Opens','Unopened'], datasets:[{ data:[0,100], backgroundColor:['#4caf50','#ddd'] }]},
        options:{ maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
      });
      chartTotalClick = new Chart($('#chartTotalClick'), {
        type:'doughnut',
        data:{ labels:['Clicks','No Click'], datasets:[{ data:[0,100], backgroundColor:['#2196f3','#ddd'] }]},
        options:{ maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
      });
      chartBounces = new Chart($('#chartBounces'), {
        type:'pie',
        data:{ labels:['Hard','Soft'], datasets:[{ data:[0,0], backgroundColor:['#f44336','#ff9800'] }]},
        options:{ maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
      });
    }

    function loadOverview(){
      $.ajax({
        url: `/api/reports/${campaignId}/overview`,
        headers: { Authorization: `Bearer ${token}` }
      }).done(res => {
        const d = res.data;
        $('#campaignTitle').text(d.title);
        chartTotalOpen.data.datasets[0].data = [d.open_rate,100-d.open_rate]; chartTotalOpen.update();
        chartTotalClick.data.datasets[0].data = [d.click_rate,100-d.click_rate]; chartTotalClick.update();
        chartBounces.data.datasets[0].data = [d.hard_bounces,d.soft_bounces]; chartBounces.update();

        $('#overviewStats').html(`
          <div class="col-md-3"><div class="card-stat">
            <h6>Total Sent</h6><div class="value">${d.total_sent}</div>
          </div></div>
          <div class="col-md-3"><div class="card-stat">
            <h6>Unique Opens</h6><div class="value">${d.unique_opens} <small>(${d.open_rate}%)</small></div>
          </div></div>
          <div class="col-md-3"><div class="card-stat">
            <h6>Unique Clicks</h6><div class="value">${d.unique_clicks} <small>(${d.click_rate}%)</small></div>
          </div></div>
          <div class="col-md-3"><div class="card-stat">
            <h6>Total Bounces</h6><div class="value">${d.total_bounces}</div>
          </div></div>
        `);
      });
    }

    function loadPreview(){
      $.ajax({
        url: `/api/reports/${campaignId}/overview`,
        headers: { Authorization: `Bearer ${token}` }
      }).done(res => {
        document.getElementById('templateIframe').srcdoc = res.data.template_body_html;
      });
    }

    function applyEngagementAndStatusFilter() {
      const statusFilter = $('#statusFilter').val();
      const engagement = $('#engagementFilter').val();

      let filtered = allRows;
      if (statusFilter !== 'all') {
        filtered = filtered.filter(r => r.status === statusFilter);
      }

      if (engagement === 'opens') {
        filtered = filtered.filter(r => r.open_count > 0);
      } else if (engagement === 'clicks') {
        filtered = filtered.filter(r => r.click_count > 0);
      } else if (engagement === 'both') {
        filtered = filtered.filter(r => r.open_count > 0 && r.click_count > 0);
      } else if (engagement === 'unsubscribes') {
        filtered = filtered.filter(r => r.unsubscribed);
      }

      displayedRows = filtered;
      renderDetailed(displayedRows);
    }

    function renderDetailed(rows){
      const $b = $('#detailedBody').empty();
      if (!rows.length) {
        return $b.html('<tr><td colspan="10" class="py-4 text-center text-muted">No data.</td></tr>');
      }
      rows.forEach(r => {
        let sc='secondary';
        if (r.status==='sent') sc='success';
        if (r.status==='skipped') sc='primary';
        if (r.status==='soft_bounce') sc='warning';
        if (r.status==='hard_bounce') sc='danger';
        if (r.status==='failed') sc='dark';

        $b.append(`
          <tr data-status="${r.status}">
            <td>${r.campaign_title}</td>
            <td>${r.email}</td>
            <td>${r.sent_at? new Date(r.sent_at).toLocaleString():'—'}</td>
            <td><span class="badge bg-success">${r.open_count}</span></td>
            <td><span class="badge bg-info">${r.click_count}</span></td>
            <td class="text-center">
              ${r.unsubscribed
                ? '<span class="badge bg-success"><i class="fa-solid fa-check"></i></span>'
                : '<span class="badge bg-danger"><i class="fa-solid fa-times"></i></span>'}
            </td>
            <td class="text-center">
              ${r.bounced
                ? '<span class="badge bg-success"><i class="fa-solid fa-check"></i></span>'
                : '<span class="badge bg-danger"><i class="fa-solid fa-times"></i></span>'}
            </td>
            <td class="text-center">
              ${r.skip_reason
                ? '<span class="badge bg-success"><i class="fa-solid fa-check"></i></span>'
                : '<span class="badge bg-danger"><i class="fa-solid fa-times"></i></span>'}
            </td>
            <td><span class="badge bg-${sc} badge-status">${r.status.replace('_',' ').toUpperCase()}</span></td>
            <td>${r.failure_reason||''}</td>
          </tr>
        `);
      });
    }

    function loadDetailed(){
      $.ajax({
        url: `/api/reports/${campaignId}/detailed`,
        headers: { Authorization: `Bearer ${token}` }
      }).done(res => {
        allRows = res.data.subscribers || [];
        applyEngagementAndStatusFilter();
      });
    }

    function exportExcel(){
      const wb = XLSX.utils.book_new();
      function addSheet(name, dataRows){
        const data = dataRows.map(r=>({
          Campaign: r.campaign_title,
          Email:    r.email,
          'Sent At':r.sent_at,
          Opens:    r.open_count,
          Clicks:   r.click_count,
          Unsubscribed: r.unsubscribed?'Yes':'No',
          Bounced:  r.bounced?'Yes':'No',
          Skipped:  r.skip_reason?'Yes':'No',
          Status:   r.status,
          Failure:  r.failure_reason||''
        }));
        const ws = XLSX.utils.json_to_sheet(data);
        XLSX.utils.book_append_sheet(wb, ws, name);
      }
      addSheet('All', allRows);
      addSheet('Sent', allRows.filter(r=>r.status==='sent'));
      addSheet('Opened', allRows.filter(r=>r.open_count>0));
      addSheet('Clicked', allRows.filter(r=>r.click_count>0));
      addSheet('Unsubscribed', allRows.filter(r=>r.unsubscribed));
      addSheet('Bounced', allRows.filter(r=>r.bounced));
      addSheet('Skipped', allRows.filter(r=>r.skip_reason));
      addSheet('Failed', allRows.filter(r=>r.status==='failed'));
      XLSX.writeFile(wb, `campaign_${campaignId}_report.xlsx`);
    }

    function exportCsvPrompt(){
      // use SweetAlert2 to ask exclude unsubscribed
      Swal.fire({
        title: 'Export CSV',
        html: `<label><input type="checkbox" id="excludeUnsub"> Exclude unsubscribed</label>`,
        showCancelButton: true,
        confirmButtonText: 'Export',
        preConfirm: () => {
          return document.getElementById('excludeUnsub').checked;
        }
      }).then(result => {
        if (!result.isConfirmed) return;
        const exclude = result.value;
        let rows = displayedRows;
        if (exclude) {
          rows = rows.filter(r => !r.unsubscribed);
        }
        // generate CSV
        const headers = ['name','email','phone'];
        const lines = [headers.join(',')];
        rows.forEach(r => {
          const name = (r.name || '').replace(/"/g,'""');
          const email = (r.email || '').replace(/"/g,'""');
          const phone = (r.phone || '').replace(/"/g,'""');
          lines.push(`"${name}","${email}","${phone}"`);
        });
        const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `campaign_${campaignId}_report.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      });
    }

    $(function(){
      if (!token) location.href = '/login';
      initCharts();
      loadOverview();
      loadPreview();
      loadDetailed();
      $('#statusFilter, #engagementFilter').on('change', applyEngagementAndStatusFilter);
      $('#exportExcelBtn').on('click', exportExcel);
      $('#exportCsvBtn').on('click', exportCsvPrompt);
    });
  </script>
</body>
</html>
