{{-- resources/views/pages/admin/managePlans/manageSubscriptionPlans.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Subscription Plans</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    :root {
      --radius: 10px;
      --shadow-light: 0 14px 40px rgba(50,50,93,0.04);
      --transition: .25s ease;
    }
    body { background: #f1f5fa; font-family: 'Inter', system-ui,-apple-system,BlinkMacSystemFont,sans-serif; }
    h2 { font-weight: 600; }
    .card { border-radius: var(--radius); box-shadow: var(--shadow-light); border: none; }
    .badge-small { font-size: 0.55rem; padding: 4px 10px; }
    .form-label { font-weight: 600; }
    .required::after { content: "*"; color: #d9534f; margin-left: 3px; }
    .invalid-feedback { display: block; }
    .mailer-checkbox { background: #fff; border: 1px solid #e7eaf3; border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; transition: var(--transition); }
    .mailer-checkbox:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.05); }
    .mailer-item { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .mailer-label { flex: 1; min-width: 200px; }
    .status-badge { text-transform: uppercase; letter-spacing: .5px; }
    .plan-title { font-size: 1rem; margin-bottom: .25rem; }
    .plan-desc { font-size: 0.75rem; color: #6c757d; }
    .limit-row { display: flex; justify-content: space-between; gap: 6px; margin-bottom: 4px; }
    .info-icon { cursor: pointer; color: #6c757d; }
    .small-muted { font-size: 0.75rem; color: #6c757d; }
    .action-btns .btn { padding: .35rem .6rem; }
    .btn-rounded { border-radius: 6px; }
    .popover-header { font-weight: 600; }
    td > .inline-gap { display: inline-flex; align-items: center; gap: 4px; }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h2 class="mb-1">Subscription Plans</h2>
        <div class="small-muted">Manage tiers, limits and associated mailers</div>
      </div>
      <div>
        <button class="btn btn-primary btn-rounded" id="newPlanBtn">
          <i class="fa-solid fa-plus me-1"></i> New Plan
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3 g-2">
      <div class="col-md-4">
        <input type="text" id="searchPlan" class="form-control form-control-sm" placeholder="Search plans...">
      </div>
      <div class="col-md-2">
        <select id="filterStatus" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Plans Table -->
    <div class="card mb-5">
      <div class="card-body p-3">
        <div class="table-responsive">
          <table class="table table-hover align-middle" id="plansTable">
            <thead class="table-light text-uppercase small">
              <tr>
                <th>Title</th>
                <th>Price</th>
                <th>Billing</th>
                <th>Mailers</th>
                <th>Limits</th>
                <th>Discount</th>
                <th>Add Mailer</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <div id="noPlans" class="text-center py-4 text-muted d-none">
            <i class="fa-solid fa-box-open fa-2x mb-2"></i><br>No subscription plans found.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Create / Edit Modal -->
  <div class="modal fade" id="planModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form class="modal-content" id="planForm" novalidate>
        <div class="modal-header border-bottom">
          <div>
            <h5 class="modal-title" id="planModalLabel">New Plan</h5>
            <div class="small-muted">Define plan details and assign mailers</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="planId" />

          <div class="row g-4">

            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label required">Title</label>
                <input type="text" id="title" class="form-control" required>
                <div class="invalid-feedback" data-field="title"></div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Billing Cycle</label>
                <select id="billing_cycle" class="form-select">
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
                <div class="small-muted">Recurrence frequency</div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label required">Price</label>
                <div class="input-group">
                  <span class="input-group-text">₹</span>
                  <input type="number" id="price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="invalid-feedback" data-field="price"></div>
              </div>
            </div>

            <!-- Template Limit -->
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Template Limit</label>
                <div class="d-flex gap-3 mb-2">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="template_limit_mode" id="template_unlimited" value="unlimited" checked>
                    <label class="form-check-label" for="template_unlimited">Unlimited</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="template_limit_mode" id="template_custom" value="custom">
                    <label class="form-check-label" for="template_custom">Custom</label>
                  </div>
                </div>
                <input type="number" id="template_limit" class="form-control" min="0" placeholder="Enter limit" disabled>
                <div class="small-muted">Unlimited or define cap per billing cycle.</div>
                <div class="invalid-feedback" data-field="template_limit"></div>
              </div>
            </div>

            <!-- Send Limit -->
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Send Limit</label>
                <div class="d-flex gap-3 mb-2">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="send_limit_mode" id="send_unlimited" value="unlimited" checked>
                    <label class="form-check-label" for="send_unlimited">Unlimited</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="send_limit_mode" id="send_custom" value="custom">
                    <label class="form-check-label" for="send_custom">Custom</label>
                  </div>
                </div>
                <input type="number" id="send_limit" class="form-control" min="0" placeholder="Enter limit" disabled>
                <div class="small-muted">Unlimited or define cap per billing cycle.</div>
                <div class="invalid-feedback" data-field="send_limit"></div>
              </div>
            </div>

            <!-- List Limit -->
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">List Limit</label>
                <div class="d-flex gap-3 mb-2">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="list_limit_mode" id="list_unlimited" value="unlimited" checked>
                    <label class="form-check-label" for="list_unlimited">Unlimited</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="list_limit_mode" id="list_custom" value="custom">
                    <label class="form-check-label" for="list_custom">Custom</label>
                  </div>
                </div>
                <input type="number" id="list_limit" class="form-control" min="0" placeholder="Enter limit" disabled>
                <div class="small-muted">Unlimited or define cap per billing cycle.</div>
                <div class="invalid-feedback" data-field="list_limit"></div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Discount (%)</label>
                <input type="number" id="discount" class="form-control" min="0" max="100" step="0.01" placeholder="0">
                <div class="invalid-feedback" data-field="discount"></div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Can Add Mailer</label>
                <select id="can_add_mailer" class="form-select">
                  <option value="1">Yes</option>
                  <option value="0" selected>No</option>
                </select>
              </div>
            </div>

            <!-- Mailer checkboxes full width row-wise -->
            <div class="col-md-12">
              <div class="form-group">
                <label class="form-label required">Mailers</label>
                <div id="mailerCheckboxContainer">
                  <!-- JS-generated mailer rows -->
                </div>
                <div class="invalid-feedback" data-field="mailer_settings_admin_ids"></div>
                <div class="small-muted">Select one or more mailer templates. Each row is selectable.</div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select id="status" class="form-select">
                  <option value="active" selected>Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-light btn-rounded" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-rounded" id="savePlanBtn">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Plan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    const token = sessionStorage.getItem('token');
    const API_PLANS = '/api/plans';
    const API_MAILERS = '/api/admin/mailer';
    const planModal = new bootstrap.Modal(document.getElementById('planModal'));
    let plans = [];
    let mailers = [];

    const headers = (json = true) => ({
      'Authorization': `Bearer ${token}`,
      ...(json ? {'Content-Type': 'application/json'} : {})
    });

    async function init() {
      if (!token) { location.href = '/'; return; }
      await Promise.all([fetchMailers(), fetchPlans()]);
      attachListeners();
    }

    async function fetchMailers() {
      try {
        const res = await fetch(API_MAILERS, { headers: headers() });
        const j = await res.json();
        mailers = Array.isArray(j.data) ? j.data : [];
        renderMailerCheckboxes();
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed to load mailers', 'error');
      }
    }

    async function fetchPlans() {
      try {
        const res = await fetch(API_PLANS, { headers: headers() });
        const j = await res.json();
        plans = Array.isArray(j.data) ? j.data : [];
        renderTable();
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed to load plans', 'error');
      }
    }

    function escapeHtml(str='') {
      return String(str)
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#39;');
    }

    function renderMailerCheckboxes() {
      const container = document.getElementById('mailerCheckboxContainer');
      container.innerHTML = mailers.map(m => {
        return `
          <div class="mailer-checkbox" data-mailer-id="${m.id}">
            <div class="mailer-item">
              <div class="form-check">
                <input class="form-check-input mailer-checkbox-input" type="checkbox" value="${m.id}" id="mailer_${m.id}">
              </div>
              <div class="mailer-label">
                <div class="plan-title">${escapeHtml(m.mailer.toUpperCase())} — ${escapeHtml(m.from_address)}</div>
                <div class="plan-desc">${escapeHtml(m.from_name)}</div>
              </div>
              <div class="me-2">
                <span class="badge ${m.status === 'active' ? 'bg-success' : 'bg-secondary'} badge-small">${m.status}</span>
              </div>
              <div class="ms-auto">
                <i class="fa-solid fa-envelope-circle-check" title="Mailer template"></i>
              </div>
            </div>
          </div>`;
      }).join('');
    }

    function formatLimits(plan) {
      const template = plan.template_limit === null ? 'Unlimited' : plan.template_limit;
      const send = plan.send_limit === null ? 'Unlimited' : plan.send_limit;
      const list = plan.list_limit === null ? 'Unlimited' : plan.list_limit;
      return `
        <div class="limit-row"><div><strong>Templates:</strong></div><div>${template}</div></div>
        <div class="limit-row"><div><strong>Sends:</strong></div><div>${send}</div></div>
        <div class="limit-row"><div><strong>Lists:</strong></div><div>${list}</div></div>
      `;
    }

    function getAssignedMailers(plan) {
      try {
        const ids = JSON.parse(plan.mailer_settings_admin_ids || '[]');
        return mailers.filter(m => ids.includes(m.id));
      } catch {
        return [];
      }
    }

    function showMailersModalById(planId) {
      const plan = plans.find(p => String(p.id) === String(planId));
      if (!plan) return;
      const assigned = getAssignedMailers(plan);
      let html = '';
      if (!assigned.length) {
        html = '<div class="text-muted">No mailers assigned.</div>';
      } else {
        html = assigned.map(m => `
          <div class="mb-3">
            <div><strong>${escapeHtml(m.mailer.toUpperCase())}</strong> — ${escapeHtml(m.from_address)}</div>
            <div class="small-muted">${escapeHtml(m.from_name)} &middot; <span class="${m.status === 'active' ? 'text-success' : 'text-secondary'}">${escapeHtml(m.status)}</span></div>
          </div>
        `).join('');
      }

      Swal.fire({
        title: 'Assigned Mailers',
        html: `<div style="text-align:left;">${html}</div>`,
        width: 500,
        showCloseButton: true,
        focusConfirm: false,
        confirmButtonText: 'Close'
      });
    }

    function renderTable() {
      const tbody = document.querySelector('#plansTable tbody');
      const search = document.getElementById('searchPlan').value.toLowerCase();
      const filterStatus = document.getElementById('filterStatus').value;
      let filtered = plans;

      if (filterStatus) filtered = filtered.filter(p => p.status === filterStatus);
      if (search) filtered = filtered.filter(p => (p.title || '').toLowerCase().includes(search));

      if (!filtered.length) {
        document.getElementById('noPlans').classList.remove('d-none');
        tbody.innerHTML = '';
        return;
      } else {
        document.getElementById('noPlans').classList.add('d-none');
      }

      tbody.innerHTML = filtered.map(p => {
        const mailerCount = (() => {
          try {
            const ids = JSON.parse(p.mailer_settings_admin_ids || '[]');
            return ids.length;
          } catch { return 0; }
        })();
        const limitsSummary = `
          T:${p.template_limit===null?'∞':p.template_limit} /
          S:${p.send_limit===null?'∞':p.send_limit} /
          L:${p.list_limit===null?'∞':p.list_limit}
        `;
        return `
          <tr data-id="${p.id}">
            <td>
              <div><strong>${escapeHtml(p.title)}</strong></div>
              <div class="small-muted">${escapeHtml(p.description||'')}</div>
            </td>
            <td>₹${parseFloat(p.price).toFixed(2)}</td>
            <td>${escapeHtml(p.billing_cycle)}</td>
            <td>
              <div class="inline-gap">
                <span>${mailerCount}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" title="View assigned mailers" onclick="showMailersModalById('${p.id}')">
                  <i class="fa-solid fa-envelope-open-text"></i>
                </button>
              </div>
            </td>
            <td>
              <div class="inline-gap">
                <span>${limitsSummary}</span>
                <i tabindex="0" class="fa-solid fa-circle-info info-icon ms-1" data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="focus" title="Limits" data-content='${escapeHtml(formatLimits(p))}'></i>
              </div>
            </td>
            <td>${p.discount ? `${parseFloat(p.discount).toFixed(2)}%` : '0%'}</td>
            <td>${p.can_add_mailer ? '<span class="badge bg-success badge-small">Yes</span>' : '<span class="badge bg-secondary badge-small">No</span>'}</td>
            <td><span class="badge ${p.status === 'active' ? 'bg-success' : 'bg-danger'} status-badge">${p.status}</span></td>
            <td class="text-end">
              <div class="d-flex justify-content-end gap-2 action-btns">
                <button class="btn btn-sm btn-outline-secondary edit-plan" data-id="${p.id}" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                <button class="btn btn-sm btn-outline-${p.status === 'active' ? 'danger' : 'success'} toggle-status" data-id="${p.id}" title="Toggle Status">
                  <i class="fa-solid ${p.status === 'active' ? 'fa-ban' : 'fa-arrow-up'}"></i>
                </button>
              </div>
            </td>
          </tr>`;
      }).join('');

      // initialize popovers for limits
      const popoverTriggerList = [...document.querySelectorAll('[data-bs-toggle="popover"]')];
      popoverTriggerList.forEach(el => {
        // dispose if exists to avoid duplicates
        if (el._popoverInstance) el._popoverInstance.dispose?.();
        el._popoverInstance = new bootstrap.Popover(el, {
          container: 'body',
          sanitize: false,
          placement: 'auto',
          trigger: 'focus',
          html: true
        });
      });
    }

    function attachListeners() {
      document.getElementById('newPlanBtn').addEventListener('click', () => openModal('add'));
      document.getElementById('searchPlan').addEventListener('input', renderTable);
      document.getElementById('filterStatus').addEventListener('change', renderTable);
      document.querySelector('#plansTable').addEventListener('click', async (e) => {
        const edit = e.target.closest('.edit-plan');
        if (edit) {
          const id = edit.dataset.id;
          const plan = plans.find(p => String(p.id) === String(id));
          if (plan) openModal('edit', plan);
          return;
        }
        const toggle = e.target.closest('.toggle-status');
        if (toggle) {
          const id = toggle.dataset.id;
          await togglePlanStatus(id);
        }
      });
      document.getElementById('planForm').addEventListener('submit', submitPlan);

      // limit toggles enabling/disabling
      document.querySelectorAll('input[name="template_limit_mode"]').forEach(r => {
        r.addEventListener('change', () => {
          document.getElementById('template_limit').disabled = document.getElementById('template_unlimited').checked;
          if (document.getElementById('template_unlimited').checked) document.getElementById('template_limit').value = '';
        });
      });
      document.querySelectorAll('input[name="send_limit_mode"]').forEach(r => {
        r.addEventListener('change', () => {
          document.getElementById('send_limit').disabled = document.getElementById('send_unlimited').checked;
          if (document.getElementById('send_unlimited').checked) document.getElementById('send_limit').value = '';
        });
      });
      document.querySelectorAll('input[name="list_limit_mode"]').forEach(r => {
        r.addEventListener('change', () => {
          document.getElementById('list_limit').disabled = document.getElementById('list_unlimited').checked;
          if (document.getElementById('list_unlimited').checked) document.getElementById('list_limit').value = '';
        });
      });
    }

    function openModal(mode, plan = null) {
      clearFormErrors();
      document.getElementById('planModalLabel').textContent = mode === 'add' ? 'New Subscription Plan' : 'Edit Plan';
      if (mode === 'add') {
        document.getElementById('planId').value = '';
        document.getElementById('title').value = '';
        document.getElementById('description').value = '';
        document.getElementById('price').value = '';
        document.getElementById('billing_cycle').value = 'monthly';
        document.getElementById('discount').value = '';
        document.getElementById('can_add_mailer').value = '0';
        document.getElementById('status').value = 'active';

        document.getElementById('template_unlimited').checked = true;
        document.getElementById('template_limit').disabled = true;
        document.getElementById('template_limit').value = '';

        document.getElementById('send_unlimited').checked = true;
        document.getElementById('send_limit').disabled = true;
        document.getElementById('send_limit').value = '';

        document.getElementById('list_unlimited').checked = true;
        document.getElementById('list_limit').disabled = true;
        document.getElementById('list_limit').value = '';

        Array.from(document.querySelectorAll('.mailer-checkbox-input')).forEach(i => i.checked = false);
      } else if (plan) {
        populateForm(plan);
      }
      planModal.show();
    }

    function populateForm(plan) {
      clearFormErrors();
      document.getElementById('planId').value = plan.id;
      document.getElementById('title').value = plan.title;
      document.getElementById('description').value = plan.description || '';
      document.getElementById('price').value = plan.price;
      document.getElementById('billing_cycle').value = plan.billing_cycle;
      document.getElementById('discount').value = plan.discount ?? '';
      document.getElementById('can_add_mailer').value = plan.can_add_mailer ? '1' : '0';
      document.getElementById('status').value = plan.status;

      if (plan.template_limit === null) {
        document.getElementById('template_unlimited').checked = true;
        document.getElementById('template_limit').disabled = true;
        document.getElementById('template_limit').value = '';
      } else {
        document.getElementById('template_custom').checked = true;
        document.getElementById('template_limit').disabled = false;
        document.getElementById('template_limit').value = plan.template_limit;
      }

      if (plan.send_limit === null) {
        document.getElementById('send_unlimited').checked = true;
        document.getElementById('send_limit').disabled = true;
        document.getElementById('send_limit').value = '';
      } else {
        document.getElementById('send_custom').checked = true;
        document.getElementById('send_limit').disabled = false;
        document.getElementById('send_limit').value = plan.send_limit;
      }

      if (plan.list_limit === null) {
        document.getElementById('list_unlimited').checked = true;
        document.getElementById('list_limit').disabled = true;
        document.getElementById('list_limit').value = '';
      } else {
        document.getElementById('list_custom').checked = true;
        document.getElementById('list_limit').disabled = false;
        document.getElementById('list_limit').value = plan.list_limit;
      }

      try {
        const selected = JSON.parse(plan.mailer_settings_admin_ids || '[]').map(String);
        Array.from(document.querySelectorAll('.mailer-checkbox-input')).forEach(i => {
          i.checked = selected.includes(i.value);
        });
      } catch {}
    }

    function clearFormErrors() {
      document.querySelectorAll('.invalid-feedback').forEach(f => f.textContent = '');
    }

    async function submitPlan(e) {
      e.preventDefault();
      clearFormErrors();
      const id = document.getElementById('planId').value;
      const selectedMailers = Array.from(document.querySelectorAll('.mailer-checkbox-input:checked')).map(i => parseInt(i.value,10));

      const payload = {
        title: document.getElementById('title').value.trim(),
        description: document.getElementById('description').value.trim() || null,
        price: parseFloat(document.getElementById('price').value) || 0,
        billing_cycle: document.getElementById('billing_cycle').value,
        template_limit: document.getElementById('template_unlimited').checked ? null : (parseInt(document.getElementById('template_limit').value,10) || 0),
        send_limit: document.getElementById('send_unlimited').checked ? null : (parseInt(document.getElementById('send_limit').value,10) || 0),
        list_limit: document.getElementById('list_unlimited').checked ? null : (parseInt(document.getElementById('list_limit').value,10) || 0),
        discount: document.getElementById('discount').value === '' ? null : parseFloat(document.getElementById('discount').value),
        can_add_mailer: document.getElementById('can_add_mailer').value === '1' ? 1 : 0,
        status: document.getElementById('status').value,
        mailer_settings_admin_ids: selectedMailers,
      };

      if (!payload.title) {
        document.querySelector('[data-field=title]').textContent = 'Title is required.';
        return;
      }
      if (isNaN(payload.price)) {
        document.querySelector('[data-field=price]').textContent = 'Valid price required.';
        return;
      }
      if (!payload.mailer_settings_admin_ids.length) {
        document.querySelector('[data-field=mailer_settings_admin_ids]').textContent = 'Select at least one mailer.';
        return;
      }

      Swal.fire({ title: id ? 'Updating plan…' : 'Creating plan…', didOpen: () => Swal.showLoading() });

      try {
        const url = id ? `${API_PLANS}/${id}` : API_PLANS;
        const method = id ? 'PUT' : 'POST';
        const res = await fetch(url, {
          method,
          headers: headers(),
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        Swal.close();
        if (json.status === 'success') {
          await fetchPlans();
          planModal.hide();
          Swal.fire('Success', json.message, 'success');
        } else if (json.errors) {
          Object.entries(json.errors).forEach(([field, msgs]) => {
            const feedback = document.querySelector(`[data-field=${field}]`);
            if (feedback) feedback.textContent = msgs.join(' ');
          });
          Swal.fire('Error', 'Validation failed', 'error');
        } else {
          Swal.fire('Error', json.message || 'Failed', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.close();
        Swal.fire('Error', 'Server error occurred', 'error');
      }
    }

    async function togglePlanStatus(id) {
      Swal.fire({ title: 'Toggling status…', didOpen: () => Swal.showLoading() });
      try {
        const res = await fetch(`${API_PLANS}/${id}/status`, {
          method: 'PUT',
          headers: headers()
        });
        const json = await res.json();
        Swal.close();
        if (json.status === 'success') {
          await fetchPlans();
          Swal.fire('Success', json.message, 'success');
        } else {
          Swal.fire('Error', json.message || 'Failed', 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Could not toggle status', 'error');
      }
    }

    document.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>
