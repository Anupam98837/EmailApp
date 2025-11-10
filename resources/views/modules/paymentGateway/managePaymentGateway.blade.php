<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Payment Gateways</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    body { background: #f5f6f8; }
    .summary-card { background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.05); }
    .summary-card .icon { font-size:1.25rem;color:var(--secondary-color,#26a69a); }
    .table-actions button{ margin-right:.25rem }
    .form-text-xs{ font-size:.75rem;color:#6b7280 }
    .badge-active{ background:#198754 }
    .badge-inactive{ background:#dc3545 }
    .badge-default{ background:#0d6efd }
    .btn-spinner{ display:inline-flex;gap:.5rem;align-items:center }
    .btn-spinner i{ display:none }
    .btn-spinner.loading i{ display:inline-block }
    .btn-spinner.loading span{ opacity:.75 }
    .overlay-loading{ position:absolute; inset:0; background:rgba(255,255,255,.6); display:flex; align-items:center; justify-content:center; border-radius:.5rem; z-index:5 }
    .code-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
    .table td .small-muted{ font-size:.8rem;color:#6c757d }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0"><i class="fa-solid fa-credit-card me-2"></i>Manage Payment Gateways</h4>
      <button id="refreshAllBtn" class="btn btn-outline-secondary btn-spinner" title="Refresh everything">
        <i class="fa-solid fa-spinner fa-spin"></i><span>Refresh</span>
      </button>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="mainTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="razorpay-tab" data-bs-toggle="tab" data-bs-target="#razorpayTab" type="button" role="tab">
          Razorpay
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="upi-tab" data-bs-toggle="tab" data-bs-target="#upiTab" type="button" role="tab">
          UPI
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="cash-tab" data-bs-toggle="tab" data-bs-target="#cashTab" type="button" role="tab">
          Cash
        </button>
      </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-white p-3 position-relative" id="mainTabsContent">

      <!-- ============ RAZORPAY TAB ============ -->
      <div class="tab-pane fade show active" id="razorpayTab" role="tabpanel" aria-labelledby="razorpay-tab">
        <div class="row row-cols-1 row-cols-md-4 g-3 mb-3 text-center">
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-rectangle-list"></i></div>
              <h6 id="rzpTotal" class="mb-0">0</h6><small>Total</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-on"></i></div>
              <h6 id="rzpActive" class="mb-0">0</h6><small>Active</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-off"></i></div>
              <h6 id="rzpInactive" class="mb-0">0</h6><small>Inactive</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-star"></i></div>
              <h6 id="rzpDefault" class="mb-0">0</h6><small>Default</small>
            </div>
          </div>
        </div>

        <div class="row g-2 mb-2 align-items-center">
          <div class="col-md-4">
            <input id="rzpSearch" class="form-control form-control-sm" placeholder="Search code/name/key id">
          </div>
          <div class="col text-end">
            <button id="newRazorBtn" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Razorpay</button>
            <button id="reloadRazorBtn" class="btn btn-sm btn-outline-secondary btn-spinner">
              <i class="fa-solid fa-spinner fa-spin"></i><span>Reload</span>
            </button>
          </div>
        </div>

        <div class="table-responsive position-relative" id="razorTableWrap">
          <table class="table table-hover align-middle" id="razorTable">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Key ID</th>
                <th>Status</th>
                <th>Default</th>
                <th>Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody><!-- rows --></tbody>
          </table>
        </div>
      </div>

      <!-- ============ UPI TAB ============ -->
      <div class="tab-pane fade" id="upiTab" role="tabpanel" aria-labelledby="upi-tab">
        <div class="row row-cols-1 row-cols-md-4 g-3 mb-3 text-center">
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-rectangle-list"></i></div>
              <h6 id="upiTotal" class="mb-0">0</h6><small>Total</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-on"></i></div>
              <h6 id="upiActive" class="mb-0">0</h6><small>Active</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-off"></i></div>
              <h6 id="upiInactive" class="mb-0">0</h6><small>Inactive</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-star"></i></div>
              <h6 id="upiDefault" class="mb-0">0</h6><small>Default</small>
            </div>
          </div>
        </div>

        <div class="row g-2 mb-2 align-items-center">
          <div class="col-md-4">
            <input id="upiSearch" class="form-control form-control-sm" placeholder="Search code/name/VPA">
          </div>
          <div class="col text-end">
            <button id="newUpiBtn" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add UPI</button>
            <button id="reloadUpiBtn" class="btn btn-sm btn-outline-secondary btn-spinner">
              <i class="fa-solid fa-spinner fa-spin"></i><span>Reload</span>
            </button>
          </div>
        </div>

        <div class="table-responsive position-relative" id="upiTableWrap">
          <table class="table table-hover align-middle" id="upiTable">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>VPA</th>
                <th>Status</th>
                <th>Default</th>
                <th>Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody><!-- rows --></tbody>
          </table>
        </div>
      </div>

      <!-- ============ CASH TAB ============ -->
      <div class="tab-pane fade" id="cashTab" role="tabpanel" aria-labelledby="cash-tab">
        <div class="row row-cols-1 row-cols-md-4 g-3 mb-3 text-center">
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-rectangle-list"></i></div>
              <h6 id="cashTotal" class="mb-0">0</h6><small>Total</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-on"></i></div>
              <h6 id="cashActive" class="mb-0">0</h6><small>Active</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-off"></i></div>
              <h6 id="cashInactive" class="mb-0">0</h6><small>Inactive</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-star"></i></div>
              <h6 id="cashDefault" class="mb-0">0</h6><small>Default</small>
            </div>
          </div>
        </div>

        <div class="row g-2 mb-2 align-items-center">
          <div class="col-md-4">
            <input id="cashSearch" class="form-control form-control-sm" placeholder="Search code/name">
          </div>
          <div class="col text-end">
            <button id="newCashBtn" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Cash</button>
            <button id="reloadCashBtn" class="btn btn-sm btn-outline-secondary btn-spinner">
              <i class="fa-solid fa-spinner fa-spin"></i><span>Reload</span>
            </button>
          </div>
        </div>

        <div class="table-responsive position-relative" id="cashTableWrap">
          <table class="table table-hover align-middle" id="cashTable">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Status</th>
                <th>Default</th>
                <th>Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody><!-- rows --></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  <!-- ======== RAZORPAY MODAL ======== -->
  <div class="modal fade" id="rzpModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" id="rzpForm">
        <div class="modal-header">
          <h5 class="modal-title" id="rzpModalTitle">New Razorpay</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="rzpId">
          <div class="mb-2">
            <label class="form-label">Code *</label>
            <input id="rzpCode" class="form-control" required placeholder="razorpay_main">
          </div>
          <div class="mb-2">
            <label class="form-label">Display Name *</label>
            <input id="rzpName" class="form-control" required placeholder="Razorpay (Primary)">
          </div>
          <div class="mb-2">
            <label class="form-label">Key ID *</label>
            <input id="rzpKeyId" class="form-control code-mono" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Key Secret *</label>
            <input id="rzpKeySecret" class="form-control code-mono" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Webhook Secret *</label>
            <input id="rzpWebhook" class="form-control code-mono" required>
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="rzpActive" checked>
            <label class="form-check-label" for="rzpActive">Active</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="rzpDefault">
            <label class="form-check-label" for="rzpDefault">Set as Default</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button id="rzpSaveBtn" class="btn btn-primary btn-spinner" type="submit">
            <i class="fa-solid fa-spinner fa-spin"></i><span>Save</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ======== UPI MODAL ======== -->
  <div class="modal fade" id="upiModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" id="upiForm">
        <div class="modal-header">
          <h5 class="modal-title" id="upiModalTitle">New UPI</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="upiId">
          <div class="mb-2">
            <label class="form-label">Code *</label>
            <input id="upiCode" class="form-control" required placeholder="upi_primary">
          </div>
          <div class="mb-2">
            <label class="form-label">Display Name *</label>
            <input id="upiName" class="form-control" required placeholder="UPI – Corporate">
          </div>
          <div class="mb-2">
            <label class="form-label">VPA *</label>
            <input id="upiVpa" class="form-control code-mono" required placeholder="merchant@bank">
          </div>
          <div class="mb-2">
            <label class="form-label">Merchant Name</label>
            <input id="upiMerchant" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">QR Image Path</label>
            <input id="upiQr" class="form-control" placeholder="/uploads/upi_qr/acme.png">
          </div>
          <div class="mb-2">
            <label class="form-label">Deeplink Base</label>
            <input id="upiDeeplink" class="form-control" placeholder="upi://pay?pa={vpa}&pn={name}&am={amount}&cu=INR&tn={note}">
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="upiActive" checked>
            <label class="form-check-label" for="upiActive">Active</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="upiDefault">
            <label class="form-check-label" for="upiDefault">Set as Default</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button id="upiSaveBtn" class="btn btn-primary btn-spinner" type="submit">
            <i class="fa-solid fa-spinner fa-spin"></i><span>Save</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ======== CASH MODAL ======== -->
  <div class="modal fade" id="cashModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" id="cashForm">
        <div class="modal-header">
          <h5 class="modal-title" id="cashModalTitle">New Cash Gateway</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="cashId">
          <div class="mb-2">
            <label class="form-label">Code *</label>
            <input id="cashCode" class="form-control" required placeholder="cash_counter">
          </div>
          <div class="mb-2">
            <label class="form-label">Display Name *</label>
            <input id="cashName" class="form-control" required placeholder="Cash">
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="cashActive" checked>
            <label class="form-check-label" for="cashActive">Active</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="cashDefault">
            <label class="form-check-label" for="cashDefault">Set as Default</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button id="cashSaveBtn" class="btn btn-primary btn-spinner" type="submit">
            <i class="fa-solid fa-spinner fa-spin"></i><span>Save</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    /* =========================
       AUTH / CORE
       ========================= */
    const token = sessionStorage.getItem('token') || localStorage.getItem('token');
    if (!token) location.href = '/';

    const rzpModal  = new bootstrap.Modal('#rzpModal');
    const upiModal  = new bootstrap.Modal('#upiModal');
    const cashModal = new bootstrap.Modal('#cashModal');

    function setBtnLoading(btn, loading=true){
      if(!btn) return;
      btn.classList.toggle('loading', !!loading);
      btn.disabled = !!loading;
    }
    function setWrapOverlay(wrap, on=true){
      if(!wrap) return;
      let overlay = wrap.querySelector('.overlay-loading');
      if(on){
        if(!overlay){
          overlay = document.createElement('div');
          overlay.className = 'overlay-loading';
          overlay.innerHTML = '<div class="spinner-border" role="status"></div>';
          wrap.appendChild(overlay);
        }
      }else{
        overlay && overlay.remove();
      }
    }
    function showTableLoading(tbody, colspan){
      tbody.innerHTML = `
        <tr>
          <td colspan="${colspan}" class="py-4 text-center">
            <div class="spinner-border" role="status"></div>
          </td>
        </tr>`;
    }
    function esc(s=''){ return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
    function fmtDate(s){ try { return new Date(s).toLocaleString(); } catch { return s || ''; } }

    async function api(url, {method='GET', body, headers={}} = {}) {
      const base = { Accept: 'application/json', Authorization: `Bearer ${token}` };
      if (body && !(body instanceof FormData)) {
        base['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
      }
      const res = await fetch(url, { method, headers: { ...base, ...headers }, body });
      const ct = res.headers.get('content-type') || '';
      let data = {};
      if (ct.includes('application/json')) {
        try { data = await res.json(); } catch {}
      }
      if (!res.ok) {
        const msg = data.message || data.error || `HTTP ${res.status}`;
        const err = new Error(msg); err.status = res.status; err.payload = data; throw err;
      }
      return data;
    }

    /* =========================
       STATE
       ========================= */
    let rzpList = [], upiList = [], cashList = [];

    /* =========================
       LOADERS
       ========================= */
    async function loadRazorpay(showOverlay=true){
      const wrap = document.getElementById('razorTableWrap');
      const btn = document.getElementById('reloadRazorBtn');
      const tbody = document.querySelector('#razorTable tbody');
      showTableLoading(tbody, 7);
      if(showOverlay) setWrapOverlay(wrap,true), setBtnLoading(btn,true);
      try{
        const res = await api('/api/admin/payment-gateways?type=razorpay');
        rzpList = res.data || [];
        renderRazorpay();
        updateSummary('rzp', rzpList);
      }catch(e){
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-4">${esc(e.message || 'Failed to load')}</td></tr>`;
      }finally{
        setWrapOverlay(wrap,false); setBtnLoading(btn,false);
      }
    }

    async function loadUpi(showOverlay=true){
      const wrap = document.getElementById('upiTableWrap');
      const btn = document.getElementById('reloadUpiBtn');
      const tbody = document.querySelector('#upiTable tbody');
      showTableLoading(tbody, 7);
      if(showOverlay) setWrapOverlay(wrap,true), setBtnLoading(btn,true);
      try{
        const res = await api('/api/admin/payment-gateways?type=upi');
        upiList = res.data || [];
        renderUpi();
        updateSummary('upi', upiList);
      }catch(e){
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-4">${esc(e.message || 'Failed to load')}</td></tr>`;
      }finally{
        setWrapOverlay(wrap,false); setBtnLoading(btn,false);
      }
    }

    async function loadCash(showOverlay=true){
      const wrap = document.getElementById('cashTableWrap');
      const btn = document.getElementById('reloadCashBtn');
      const tbody = document.querySelector('#cashTable tbody');
      showTableLoading(tbody, 6);
      if(showOverlay) setWrapOverlay(wrap,true), setBtnLoading(btn,true);
      try{
        const res = await api('/api/admin/payment-gateways?type=cash');
        cashList = res.data || [];
        renderCash();
        updateSummary('cash', cashList);
      }catch(e){
        tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center py-4">${esc(e.message || 'Failed to load')}</td></tr>`;
      }finally{
        setWrapOverlay(wrap,false); setBtnLoading(btn,false);
      }
    }

    function updateSummary(prefix, list){
      const total = list.length;
      const active = list.filter(x => x.is_active).length;
      const def = list.filter(x => x.is_default).length;
      document.getElementById(prefix+'Total').textContent = total;
      document.getElementById(prefix+'Active').textContent = active;
      document.getElementById(prefix+'Inactive').textContent = total - active;
      document.getElementById(prefix+'Default').textContent = def;
    }

    /* =========================
       RENDERERS
       ========================= */
    function renderRazorpay(){
      const q = document.getElementById('rzpSearch').value.trim().toLowerCase();
      const list = !q ? rzpList : rzpList.filter(r =>
        (r.code||'').toLowerCase().includes(q) ||
        (r.display_name||'').toLowerCase().includes(q) ||
        (r.key_id||'').toLowerCase().includes(q)
      );
      const tbody = document.querySelector('#razorTable tbody');
      tbody.innerHTML = '';
      if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No Razorpay gateways</td></tr>`;
        return;
      }
      for(const r of list){
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td class="code-mono">${esc(r.code)}</td>
            <td>${esc(r.display_name)}</td>
            <td class="code-mono">${esc(r.key_id || '')}</td>
            <td><span class="badge ${r.is_active?'badge-active':'badge-inactive'}">${r.is_active?'active':'inactive'}</span></td>
            <td>${r.is_default?'<span class="badge badge-default">default</span>':'<span class="text-muted">—</span>'}</td>
            <td>${fmtDate(r.updated_at)}</td>
            <td class="table-actions">
              <button class="btn btn-sm btn-outline-primary" onclick="openRzpModal(${r.id})" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
              <button class="btn btn-sm btn-outline-secondary" onclick="toggleActive('razorpay', ${r.id}, ${r.is_active?0:1})" title="${r.is_active?'Deactivate':'Activate'}">
                <i class="fa-solid ${r.is_active?'fa-toggle-off':'fa-toggle-on'}"></i>
              </button>
              <button class="btn btn-sm btn-outline-success" onclick="makeDefault('razorpay', ${r.id})" title="Make Default"><i class="fa-solid fa-star"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteGateway('razorpay', ${r.id})" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
            </td>
          </tr>
        `);
      }
    }

    function renderUpi(){
      const q = document.getElementById('upiSearch').value.trim().toLowerCase();
      const list = !q ? upiList : upiList.filter(u =>
        (u.code||'').toLowerCase().includes(q) ||
        (u.display_name||'').toLowerCase().includes(q) ||
        (u.vpa||'').toLowerCase().includes(q)
      );
      const tbody = document.querySelector('#upiTable tbody');
      tbody.innerHTML = '';
      if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No UPI gateways</td></tr>`;
        return;
      }
      for(const u of list){
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td class="code-mono">${esc(u.code)}</td>
            <td>${esc(u.display_name)}</td>
            <td class="code-mono">${esc(u.vpa || '')}<div class="small-muted">${esc(u.merchant_name || '')}</div></td>
            <td><span class="badge ${u.is_active?'badge-active':'badge-inactive'}">${u.is_active?'active':'inactive'}</span></td>
            <td>${u.is_default?'<span class="badge badge-default">default</span>':'<span class="text-muted">—</span>'}</td>
            <td>${fmtDate(u.updated_at)}</td>
            <td class="table-actions">
              <button class="btn btn-sm btn-outline-primary" onclick="openUpiModal(${u.id})" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
              <button class="btn btn-sm btn-outline-secondary" onclick="toggleActive('upi', ${u.id}, ${u.is_active?0:1})" title="${u.is_active?'Deactivate':'Activate'}">
                <i class="fa-solid ${u.is_active?'fa-toggle-off':'fa-toggle-on'}"></i>
              </button>
              <button class="btn btn-sm btn-outline-success" onclick="makeDefault('upi', ${u.id})" title="Make Default"><i class="fa-solid fa-star"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteGateway('upi', ${u.id})" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
            </td>
          </tr>
        `);
      }
    }

    function renderCash(){
      const q = document.getElementById('cashSearch').value.trim().toLowerCase();
      const list = !q ? cashList : cashList.filter(c =>
        (c.code||'').toLowerCase().includes(q) ||
        (c.display_name||'').toLowerCase().includes(q)
      );
      const tbody = document.querySelector('#cashTable tbody');
      tbody.innerHTML = '';
      if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No Cash gateways</td></tr>`;
        return;
      }
      for(const c of list){
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td class="code-mono">${esc(c.code)}</td>
            <td>${esc(c.display_name)}</td>
            <td><span class="badge ${c.is_active?'badge-active':'badge-inactive'}">${c.is_active?'active':'inactive'}</span></td>
            <td>${c.is_default?'<span class="badge badge-default">default</span>':'<span class="text-muted">—</span>'}</td>
            <td>${fmtDate(c.updated_at)}</td>
            <td class="table-actions">
              <button class="btn btn-sm btn-outline-primary" onclick="openCashModal(${c.id})" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
              <button class="btn btn-sm btn-outline-secondary" onclick="toggleActive('cash', ${c.id}, ${c.is_active?0:1})" title="${c.is_active?'Deactivate':'Activate'}">
                <i class="fa-solid ${c.is_active?'fa-toggle-off':'fa-toggle-on'}"></i>
              </button>
              <button class="btn btn-sm btn-outline-success" onclick="makeDefault('cash', ${c.id})" title="Make Default"><i class="fa-solid fa-star"></i></button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteGateway('cash', ${c.id})" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
            </td>
          </tr>
        `);
      }
    }

    /* =========================
       CRUD HELPERS
       ========================= */
    function clearForm(idMap){
      Object.values(idMap).forEach(id => {
        const el = document.getElementById(id);
        if (el?.type === 'checkbox') el.checked = false;
        else if (el) el.value = '';
      });
    }

    // ---------- Razorpay ----------
    function openRzpModal(id=null){
      document.getElementById('rzpModalTitle').textContent = id ? 'Edit Razorpay' : 'New Razorpay';
      const ids = {id:'rzpId', code:'rzpCode', name:'rzpName', key:'rzpKeyId', secret:'rzpKeySecret', wh:'rzpWebhook', active:'rzpActive', def:'rzpDefault'};
      clearForm(ids);
      if(id){
        const item = rzpList.find(x => x.id === id);
        if(!item) return;
        document.getElementById(ids.id).value = item.id;
        document.getElementById(ids.code).value = item.code || '';
        document.getElementById(ids.name).value = item.display_name || '';
        document.getElementById(ids.key).value = item.key_id || '';
        document.getElementById(ids.secret).value = item.key_secret || '';
        document.getElementById(ids.wh).value = item.webhook_secret || '';
        document.getElementById(ids.active).checked = !!item.is_active;
        document.getElementById(ids.def).checked = !!item.is_default;
      } else {
        document.getElementById(ids.active).checked = true;
      }
      rzpModal.show();
    }
    document.getElementById('newRazorBtn').addEventListener('click', ()=>openRzpModal());
    document.getElementById('rzpForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = document.getElementById('rzpSaveBtn'); setBtnLoading(btn,true);
      const id = document.getElementById('rzpId').value;
      const body = {
        type: 'razorpay',
        code: document.getElementById('rzpCode').value.trim(),
        display_name: document.getElementById('rzpName').value.trim(),
        key_id: document.getElementById('rzpKeyId').value.trim(),
        key_secret: document.getElementById('rzpKeySecret').value.trim(),
        webhook_secret: document.getElementById('rzpWebhook').value.trim(),
        is_active: document.getElementById('rzpActive').checked,
        is_default: document.getElementById('rzpDefault').checked,
      };
      try{
        if (id) await api(`/api/admin/payment-gateways/razorpay/${id}`, { method:'PUT', body });
        else    await api('/api/admin/payment-gateways', { method:'POST', body });
        rzpModal.hide();
        await loadRazorpay(false);
        Swal.fire('Success','Saved','success');
      }catch(err){
        Swal.fire('Error', err.message || 'Save failed', 'error');
      }finally{ setBtnLoading(btn,false); }
    });

    // ---------- UPI ----------
    function openUpiModal(id=null){
      document.getElementById('upiModalTitle').textContent = id ? 'Edit UPI' : 'New UPI';
      const ids = {id:'upiId', code:'upiCode', name:'upiName', vpa:'upiVpa', merch:'upiMerchant', qr:'upiQr', deeplink:'upiDeeplink', active:'upiActive', def:'upiDefault'};
      clearForm(ids);
      if(id){
        const item = upiList.find(x => x.id === id);
        if(!item) return;
        document.getElementById(ids.id).value = item.id;
        document.getElementById(ids.code).value = item.code || '';
        document.getElementById(ids.name).value = item.display_name || '';
        document.getElementById(ids.vpa).value = item.vpa || '';
        document.getElementById(ids.merch).value = item.merchant_name || '';
        document.getElementById(ids.qr).value = item.qr_code_path || '';
        document.getElementById(ids.deeplink).value = item.deeplink_base || '';
        document.getElementById(ids.active).checked = !!item.is_active;
        document.getElementById(ids.def).checked = !!item.is_default;
      } else {
        document.getElementById(ids.active).checked = true;
      }
      upiModal.show();
    }
    document.getElementById('newUpiBtn').addEventListener('click', ()=>openUpiModal());
    document.getElementById('upiForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = document.getElementById('upiSaveBtn'); setBtnLoading(btn,true);
      const id = document.getElementById('upiId').value;
      const body = {
        type: 'upi',
        code: document.getElementById('upiCode').value.trim(),
        display_name: document.getElementById('upiName').value.trim(),
        vpa: document.getElementById('upiVpa').value.trim(),
        merchant_name: document.getElementById('upiMerchant').value.trim() || null,
        qr_code_path: document.getElementById('upiQr').value.trim() || null,
        deeplink_base: document.getElementById('upiDeeplink').value.trim() || null,
        is_active: document.getElementById('upiActive').checked,
        is_default: document.getElementById('upiDefault').checked,
      };
      try{
        if (id) await api(`/api/admin/payment-gateways/upi/${id}`, { method:'PUT', body });
        else    await api('/api/admin/payment-gateways', { method:'POST', body });
        upiModal.hide();
        await loadUpi(false);
        Swal.fire('Success','Saved','success');
      }catch(err){
        Swal.fire('Error', err.message || 'Save failed', 'error');
      }finally{ setBtnLoading(btn,false); }
    });

    // ---------- Cash ----------
    function openCashModal(id=null){
      document.getElementById('cashModalTitle').textContent = id ? 'Edit Cash Gateway' : 'New Cash Gateway';
      const ids = {id:'cashId', code:'cashCode', name:'cashName', active:'cashActive', def:'cashDefault'};
      clearForm(ids);
      if(id){
        const item = cashList.find(x => x.id === id);
        if(!item) return;
        document.getElementById(ids.id).value = item.id;
        document.getElementById(ids.code).value = item.code || '';
        document.getElementById(ids.name).value = item.display_name || '';
        document.getElementById(ids.active).checked = !!item.is_active;
        document.getElementById(ids.def).checked = !!item.is_default;
      } else {
        document.getElementById(ids.active).checked = true;
      }
      cashModal.show();
    }
    document.getElementById('newCashBtn').addEventListener('click', ()=>openCashModal());
    document.getElementById('cashForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = document.getElementById('cashSaveBtn'); setBtnLoading(btn,true);
      const id = document.getElementById('cashId').value;
      const body = {
        type: 'cash',
        code: document.getElementById('cashCode').value.trim(),
        display_name: document.getElementById('cashName').value.trim(),
        is_active: document.getElementById('cashActive').checked,
        is_default: document.getElementById('cashDefault').checked,
      };
      try{
        if (id) await api(`/api/admin/payment-gateways/cash/${id}`, { method:'PUT', body });
        else    await api('/api/admin/payment-gateways', { method:'POST', body });
        cashModal.hide();
        await loadCash(false);
        Swal.fire('Success','Saved','success');
      }catch(err){
        Swal.fire('Error', err.message || 'Save failed', 'error');
      }finally{ setBtnLoading(btn,false); }
    });

    // ---------- Common actions ----------
    async function toggleActive(type, id, active){
      try {
        await api(`/api/admin/payment-gateways/${type}/${id}/activate`, { method:'PATCH', body:{ is_active: !!active }});
        await refreshType(type);
      } catch (err) {
        Swal.fire('Error', err.message || 'Failed to update status', 'error');
      }
    }
    async function makeDefault(type, id){
      try {
        await api(`/api/admin/payment-gateways/${type}/${id}/default`, { method:'PATCH' });
        await refreshType(type);
      } catch (err) {
        Swal.fire('Error', err.message || 'Failed to set default', 'error');
      }
    }
    async function deleteGateway(type, id){
      const ok = await Swal.fire({ title:'Delete this gateway?', icon:'warning', showCancelButton:true });
      if (!ok.isConfirmed) return;
      try {
        await api(`/api/admin/payment-gateways/${type}/${id}`, { method:'DELETE' });
        await refreshType(type);
        Swal.fire('Deleted','Gateway deleted','success');
      } catch (err) {
        Swal.fire('Error', err.message || 'Delete failed', 'error');
      }
    }

    async function refreshType(type){
      if (type === 'razorpay') await loadRazorpay(false);
      else if (type === 'upi') await loadUpi(false);
      else await loadCash(false);
    }

    /* =========================
       WIRING
       ========================= */
    document.getElementById('rzpSearch').addEventListener('input', renderRazorpay);
    document.getElementById('upiSearch').addEventListener('input', renderUpi);
    document.getElementById('cashSearch').addEventListener('input', renderCash);

    document.getElementById('reloadRazorBtn').addEventListener('click', ()=>loadRazorpay());
    document.getElementById('reloadUpiBtn').addEventListener('click', ()=>loadUpi());
    document.getElementById('reloadCashBtn').addEventListener('click', ()=>loadCash());

    document.getElementById('refreshAllBtn').addEventListener('click', async (e)=>{
      setBtnLoading(e.currentTarget,true);
      try { await Promise.all([loadRazorpay(), loadUpi(), loadCash()]); }
      finally { setBtnLoading(e.currentTarget,false); }
    });

    // initial
    (async function init(){
      try {
        setBtnLoading(document.getElementById('refreshAllBtn'),true);
        await Promise.all([loadRazorpay(), loadUpi(), loadCash()]);
      } catch (e) {
        Swal.fire('Error', e.message || 'Failed to load', 'error');
      } finally {
        setBtnLoading(document.getElementById('refreshAllBtn'),false);
      }
    })();
  </script>
</body>
</html>
