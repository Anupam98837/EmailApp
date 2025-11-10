<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Mailers</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/pages/manageMailer/manageMailer.css') }}">
  <style>
    table { font-size: 13px; }
    table thead th { font-size: 14px; }
    .btn { font-size: 13px; }
    .badge { font-size: 13px; }
    .text-mono { font-family: monospace, monospace; }
    /* .disabled-overlay {
      position: absolute;
      inset: 0;
      background: rgba(255,255,255,0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .9rem;
      color: #6c757d;
      pointer-events: none;
      border-radius: .25rem;
    }
    .no-permission {
      opacity: 0.6;
    } */
  </style>
</head>
<body class="bg-light">

  <div class="container py-5">

    <!-- Summary Row -->
    <div class="row row-cols-2 row-cols-md-6 g-4 mb-4 text-center" id="summaryRow">
      <div class="col position-relative">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-envelope-open-text"></i></div>
          <h6>Default Driver</h6>
          <p class="text-mono mb-0" id="defMailer">-</p>
        </div>
      </div>
      <div class="col position-relative">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-server"></i></div>
          <h6>Default Host</h6>
          <p class="text-mono mb-0" id="defHost">-</p>
        </div>
      </div>
      <div class="col position-relative">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-network-wired"></i></div>
          <h6>Default Port</h6>
          <p class="text-mono mb-0" id="defPort">-</p>
        </div>
      </div>
      <div class="col position-relative">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-user"></i></div>
          <h6>Default Username</h6>
          <p class="text-mono mb-0" id="defUsername">-</p>
        </div>
      </div>
      <div class="col position-relative">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-lock"></i></div>
          <h6>Encryption</h6>
          <p class="text-mono mb-0" id="defEncryption">-</p>
        </div>
      </div>
      <div class="col position-relative">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-at"></i></div>
          <h6>From Address</h6>
          <p class="text-mono small mb-0" id="defFromAddress">-</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3 position-relative" id="actionsWrapper">
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" id="refreshBtn" title="Refresh">
          <i class="fa-solid fa-arrows-rotate"></i>
        </button>
        <button class="btn btn-warning text-dark" id="testMailBtn">
          <i class="fa-solid fa-paper-plane"></i> Test Mail
        </button>
      </div>
      <div class="position-relative">
        <button class="btn btn-primary" id="addMailerBtn">
          <i class="fa-solid fa-plus me-1"></i> New Mailer
        </button>
        <div id="noAddOverlay" class="disabled-overlay d-none" style="inset:auto 0 0 auto; padding:6px 12px; border-radius:6px;">
          Cannot add/edit: plan restricts mailers
        </div>
      </div>
    </div>

    <!-- Mailers Table -->
    <div class="table-responsive position-relative" id="tableWrapper">
      <table class="table table-hover align-middle" id="mailerTable">
        <thead class="table-light text-uppercase">
          <tr>
            <th>Driver</th>
            <th>Host</th>
            <th>Port</th>
            <th>Username</th>
            <th>Encryption</th>
            <th>From Address</th>
            <th>From Name</th>
            <th class="text-nowrap">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr id="loadingRow">
            <td colspan="8" class="py-4 text-center">
              <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
            </td>
          </tr>
        </tbody>
      </table>
      <div id="noMailers" class="text-center py-4 text-muted d-none">
        <i class="fa-solid fa-envelope-slash fa-2x mb-2"></i><br>No mailers found.
      </div>
    </div>

  </div>

  <!-- Mailer Modal -->
  <div class="modal fade" id="mailerModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" id="mailerForm">
        <div class="modal-header">
          <h5 class="modal-title" id="mailerModalLabel"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          @csrf
          <input type="hidden" id="modalMailerId">

          <div class="mb-2">
            <label class="form-label">Driver</label>
            <select id="modalMailer" class="form-select" required></select>
          </div>

          <div class="mb-2">
            <label class="form-label">Host</label>
            <input type="text" id="modalHost" class="form-control" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Port</label>
            <select id="modalPort" class="form-select" required></select>
          </div>

          <div class="mb-2">
            <label class="form-label">Username</label>
            <input type="text" id="modalUsername" class="form-control" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" id="modalPassword" class="form-control" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Encryption</label>
            <select id="modalEncryption" class="form-select"></select>
          </div>

          <div class="mb-2">
            <label class="form-label">From Address</label>
            <input type="email" id="modalFromAddress" class="form-control" required>
          </div>

          <div class="mb-2">
            <label class="form-label">From Name</label>
            <input type="text" id="modalFromName" class="form-control" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveMailerBtn">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Test Mail Modal -->
  <div class="modal fade" id="testMailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form class="modal-content" id="testMailForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-paper-plane"></i> Send Test Mail</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">From Address</label>
              <input type="email" id="testFromAddress" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">From Name</label>
              <input type="text" id="testFromName" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Reply-To (optional)</label>
              <input type="email" id="testReplyTo" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">To (recipient)</label>
              <input type="email" id="testTo" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Subject</label>
              <input type="text" id="testSubject" class="form-control" value="Test Email" required>
            </div>
            <div class="col-12">
              <label class="form-label">HTML Body</label>
              <textarea id="testHtml" class="form-control" rows="8"><p>Hi, this is a <strong>test</strong> email.</p></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning text-dark">
            <i class="fa-solid fa-paper-plane"></i> Send Test
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const token = sessionStorage.getItem('token');
    const DRIVER_OPTIONS = ['smtp','sendmail','ses','mailgun','postmark','log','array'];
    const PORT_OPTIONS   = [587,465,2525,25];
    const ENC_OPTIONS    = ['tls','ssl','starttls',''];

    const mailerModal   = new bootstrap.Modal('#mailerModal');
    const testMailModal = new bootstrap.Modal('#testMailModal');

    let canAddMailer = false;
    let allMailers = [];

    function populateStaticSelects() {
      document.getElementById('modalMailer').innerHTML =
        DRIVER_OPTIONS.map(v => `<option value="${v}">${v.toUpperCase()}</option>`).join('');
      document.getElementById('modalPort').innerHTML =
        PORT_OPTIONS.map(v => `<option value="${v}">${v}</option>`).join('');
      document.getElementById('modalEncryption').innerHTML =
        ENC_OPTIONS.map(v => `<option value="${v}">${v || 'NONE'}</option>`).join('');
    }
    populateStaticSelects();

    async function fetchMyPlan() {
      const res = await fetch('/api/plans/my', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (!res.ok) throw new Error('Failed to fetch plan');
      const json = await res.json();
      if (json.status !== 'success') throw new Error(json.message || 'Plan error');
      return json.data;
    }

    function setSummaryDefault(row) {
      if (row) {
        document.getElementById('defMailer').textContent      = row.mailer;
        document.getElementById('defHost').textContent        = row.host;
        document.getElementById('defPort').textContent        = row.port;
        document.getElementById('defUsername').textContent    = row.username;
        document.getElementById('defEncryption').textContent  = row.encryption || 'none';
        document.getElementById('defFromAddress').textContent = row.from_address;
      } else {
        ['defMailer','defHost','defPort','defUsername','defEncryption','defFromAddress']
          .forEach(id => document.getElementById(id).textContent = '-');
      }
    }

    function renderMailerRows(data) {
      const tbody = document.querySelector('#mailerTable tbody');

      if (!data.length) {
        tbody.innerHTML = `
          <tr>
            <td colspan="8" class="py-4 text-center text-muted">
              <i class="fa-solid fa-envelope-slash fa-2x mb-2"></i><br>No mailers found.
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = data.map(m => {
        const editableAllowed = canAddMailer && m.action === true;
        const editButtons = editableAllowed ? `
          <button class="btn btn-sm btn-outline-secondary" title="Edit"
            onclick='openMailerModal("edit","${m.id}",${JSON.stringify({
              mailer: m.mailer,
              host: m.host,
              port: m.port,
              username: m.username,
              encryption: m.encryption,
              from_address: m.from_address,
              from_name: m.from_name
            })})'>
            <i class="fa-regular fa-pen-to-square"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteMailer(${m.id})">
            <i class="fa-solid fa-trash-can"></i>
          </button>
        ` : '';
        return `
          <tr class="${!editableAllowed ? 'no-permission' : ''}">
            <td>${m.mailer}</td>
            <td>${m.host}</td>
            <td>${m.port}</td>
            <td>${m.username}</td>
            <td>${m.encryption || '<em>none</em>'}</td>
            <td>${m.from_address}</td>
            <td>${m.from_name}</td>
            <td class="text-nowrap">
              <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="checkbox"
                       class="form-check-input default-checkbox"
                       data-id="${m.id}"
                       ${m.is_default ? 'checked' : ''}
                       title="Default">
                ${editButtons}
                <button class="btn btn-sm btn-warning text-dark" title="Send Test" onclick='openTestMailModal({from_address:"${m.from_address}",from_name:"${m.from_name}"})'>
                  <i class="fa-solid fa-paper-plane"></i>
                </button>
              </div>
            </td>
          </tr>`;
      }).join('');
    }

    async function loadEverything() {
      try {
        const wrapper = await fetchMyPlan();
        canAddMailer = !!(wrapper.plan && wrapper.plan.can_add_mailer);

        document.getElementById('addMailerBtn').style.display = canAddMailer ? 'inline-block' : 'none';
        if (!canAddMailer) {
          document.getElementById('noAddOverlay').classList.remove('d-none');
        } else {
          document.getElementById('noAddOverlay').classList.add('d-none');
        }
        document.getElementById('testMailBtn').disabled = false;

        const mailerResp = await fetch('/api/mailer', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        const mailerJson = await mailerResp.json();
        allMailers = Array.isArray(mailerJson.data) ? mailerJson.data : [];

        const defaultRow = allMailers.find(m => m.is_default) || allMailers.find(m => m.is_active) || allMailers[0];
        setSummaryDefault(defaultRow);

        renderMailerRows(allMailers);
        bindDefaultCheckboxEvents();

      } catch (err) {
        console.error(err);
        Swal.fire('Error', err.message || 'Failed to load mailers or plan', 'error');
      } finally {
        const loading = document.getElementById('loadingRow');
        if (loading) loading.remove();
      }
    }

    function bindDefaultCheckboxEvents() {
      document.querySelectorAll('.default-checkbox').forEach(cb => {
        cb.onchange = async () => {
          const id = cb.dataset.id;
          Swal.fire({ title: 'Updating default…', didOpen: () => Swal.showLoading() });
          try {
            const res = await fetch(`/api/mailer/${id}/default`, {
              method: 'PUT',
              headers: { 'Authorization': `Bearer ${token}` }
            });
            const json = await res.json();
            Swal.close();
            if (json.status === 'success') {
              await loadEverything();
            } else {
              Swal.fire('Error', json.message || 'Failed', 'error');
            }
          } catch (e) {
            Swal.close();
            Swal.fire('Error', 'Failed to update default', 'error');
          }
        };
      });
    }

    function openMailerModal(mode, id = '', data = {}) {
      if (!canAddMailer) return;
      document.getElementById('mailerModalLabel').textContent = mode === 'add' ? 'Add Mailer' : 'Edit Mailer';
      document.getElementById('modalMailerId').value = id;
      const set = (sel, val) => document.getElementById(sel).value = val ?? '';
      set('modalMailer',      mode === 'edit' ? data.mailer      : DRIVER_OPTIONS[0]);
      set('modalHost',        mode === 'edit' ? data.host        : '');
      set('modalPort',        mode === 'edit' ? data.port        : PORT_OPTIONS[0]);
      set('modalUsername',    mode === 'edit' ? data.username    : '');
      set('modalPassword',    '');
      set('modalEncryption',  mode === 'edit' ? (data.encryption||'') : '');
      set('modalFromAddress', mode === 'edit' ? data.from_address : '');
      set('modalFromName',    mode === 'edit' ? data.from_name    : '');
      mailerModal.show();
    }

    function openTestMailModal(prefill = {}) {
      const set = (id,val) => document.getElementById(id).value = val||'';
      set('testFromAddress', prefill.from_address || '');
      set('testFromName',    prefill.from_name    || '');
      set('testReplyTo',     '');
      set('testTo',          '');
      set('testSubject',     'Test Email');
      testMailModal.show();
    }

    document.getElementById('mailerForm').onsubmit = async e => {
      e.preventDefault();
      if (!canAddMailer) return;
      const id = document.getElementById('modalMailerId').value;
      const payload = {
        mailer:       document.getElementById('modalMailer').value.trim(),
        host:         document.getElementById('modalHost').value.trim(),
        port:         parseInt(document.getElementById('modalPort').value, 10),
        username:     document.getElementById('modalUsername').value.trim(),
        password:     document.getElementById('modalPassword').value,
        encryption:   document.getElementById('modalEncryption').value.trim() || null,
        from_address: document.getElementById('modalFromAddress').value.trim(),
        from_name:    document.getElementById('modalFromName').value.trim(),
      };
      Swal.fire({ title: id ? 'Updating…' : 'Adding…', didOpen: () => Swal.showLoading() });
      try {
        const res = await fetch(id ? `/api/mailer/${id}` : '/api/mailer', {
          method: id ? 'PUT' : 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        Swal.close();
        if (json.status === 'success') {
          mailerModal.hide();
          await loadEverything();
          Swal.fire('Success', json.message, 'success');
        } else {
          Swal.fire('Error', json.message || 'Failed', 'error');
        }
      } catch (err) {
        Swal.close();
        Swal.fire('Error', 'Failed to save mailer', 'error');
      }
    };

    document.getElementById('testMailForm').onsubmit = async e => {
      e.preventDefault();
      const payload = {
        fromAddress: document.getElementById('testFromAddress').value.trim(),
        fromName:    document.getElementById('testFromName').value.trim(),
        reply_to:    document.getElementById('testReplyTo').value.trim() || null,
        to:          document.getElementById('testTo').value.trim(),
        subject:     document.getElementById('testSubject').value.trim(),
        html:        document.getElementById('testHtml').value
      };
      if (!payload.fromAddress || !payload.fromName || !payload.to || !payload.subject) {
        return Swal.fire('Error', 'Please fill required fields', 'error');
      }
      Swal.fire({ title: 'Sending test email…', didOpen: () => Swal.showLoading() });
      try {
        const res = await fetch('/api/mailer/test', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        Swal.close();
        if (json.status === 'success') {
          testMailModal.hide();
          Swal.fire('Sent', 'Test email queued / sent', 'success');
        } else {
          Swal.fire('Error', json.message || 'Failed', 'error');
        }
      } catch (err) {
        Swal.close();
        Swal.fire('Error', 'Failed to send test email', 'error');
      }
    };

    async function deleteMailer(id) {
      if (!canAddMailer) return;
      const { isConfirmed } = await Swal.fire({
        title: 'Delete this mailer?',
        icon: 'warning',
        showCancelButton: true
      });
      if (!isConfirmed) return;
      try {
        const res = await fetch(`/api/mailer/${id}`, {
          method: 'DELETE',
          headers: { 'Authorization': `Bearer ${token}` }
        });
        const json = await res.json();
        Swal.fire(json.status === 'success' ? 'Deleted' : 'Error', json.message, json.status);
        await loadEverything();
      } catch (err) {
        Swal.fire('Error', 'Delete failed', 'error');
      }
    }

    document.getElementById('refreshBtn').addEventListener('click', loadEverything);
    document.getElementById('addMailerBtn').addEventListener('click', () => openMailerModal('add'));
    document.getElementById('testMailBtn').addEventListener('click', () => openTestMailModal());

    document.addEventListener('DOMContentLoaded', () => {
      if (!token) return location.href = '/';
      loadEverything();
    });
  </script>
</body>
</html>
