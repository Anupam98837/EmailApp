{{-- resources/views/pages/admin/manageMailer/manageAdminMailer.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Admin Mailers</title>

  <!-- Bootstrap & Icons -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
  />

  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link
    rel="stylesheet"
    href="{{ asset('assets/css/common/main.css') }}"
  />
  <link
    rel="stylesheet"
    href="{{ asset('assets/css/pages/manageMailer/manageMailer.css') }}"
  />
</head>
<body class="bg-light">
  <div class="container py-5">

    <!-- Header + New Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0">Admin Mailers</h3>
      <button
        class="btn btn-primary"
        onclick="openMailerModal('add')"
      >
        <i class="fa-solid fa-plus me-1"></i>
        New Mailer
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="row row-cols-1 row-cols-md-2 g-4 mb-4" id="summaryRow">
      <div class="col">
        <div class="card text-center p-3">
          <div class="icon mb-2">
            <i class="fa-solid fa-toggle-on fa-2x text-success"></i>
          </div>
          <h6>Active Mailers</h6>
          <p class="h2 mb-0" id="activeCount">0</p>
        </div>
      </div>
      <div class="col">
        <div class="card text-center p-3">
          <div class="icon mb-2">
            <i class="fa-solid fa-toggle-off fa-2x text-danger"></i>
          </div>
          <h6>Inactive Mailers</h6>
          <p class="h2 mb-0" id="inactiveCount">0</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="statusTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button
          class="nav-link active"
          id="active-tab"
          data-bs-toggle="tab"
          data-bs-target="#active-pane"
          type="button"
          role="tab"
          aria-controls="active-pane"
          aria-selected="true"
        >
          Active
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button
          class="nav-link"
          id="inactive-tab"
          data-bs-toggle="tab"
          data-bs-target="#inactive-pane"
          type="button"
          role="tab"
          aria-controls="inactive-pane"
          aria-selected="false"
        >
          Inactive
        </button>
      </li>
    </ul>

    <!-- Tab Panes -->
    <div class="tab-content">
      <div
        class="tab-pane fade show active"
        id="active-pane"
        role="tabpanel"
        aria-labelledby="active-tab"
      >
        <div class="table-responsive">
          <table
            class="table table-hover align-middle"
            id="activeTable"
          >
            <thead class="table-light text-uppercase">
              <tr>
                <th>Driver</th>
                <th>Host</th>
                <th>Port</th>
                <th>Username</th>
                <th>Encryption</th>
                <th>From Address</th>
                <th>From Name</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div
        class="tab-pane fade"
        id="inactive-pane"
        role="tabpanel"
        aria-labelledby="inactive-tab"
      >
        <div class="table-responsive">
          <table
            class="table table-hover align-middle"
            id="inactiveTable"
          >
            <thead class="table-light text-uppercase">
              <tr>
                <th>Driver</th>
                <th>Host</th>
                <th>Port</th>
                <th>Username</th>
                <th>Encryption</th>
                <th>From Address</th>
                <th>From Name</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <!-- Add / Edit Modal -->
  <div class="modal fade" id="adminMailerModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" id="adminMailerForm">
        <div class="modal-header">
          <h5 class="modal-title" id="adminMailerModalLabel"></h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
          ></button>
        </div>
        <div class="modal-body">
          @csrf
          <input
            type="hidden"
            id="modalMailerId"
          />

          <div class="mb-3">
            <label class="form-label">Driver</label>
            <select
              id="modalMailer"
              class="form-select"
              required
            ></select>
          </div>

          <div class="mb-3">
            <label class="form-label">Host</label>
            <input
              type="text"
              id="modalHost"
              class="form-control"
              required
            />
          </div>

          <div class="mb-3">
            <label class="form-label">Port</label>
            <select
              id="modalPort"
              class="form-select"
              required
            ></select>
          </div>

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input
              type="text"
              id="modalUsername"
              class="form-control"
              required
            />
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input
              type="password"
              id="modalPassword"
              class="form-control"
              required
            />
          </div>

          <div class="mb-3">
            <label class="form-label">Encryption</label>
            <select
              id="modalEncryption"
              class="form-select"
            ></select>
          </div>

          <div class="mb-3">
            <label class="form-label">From Address</label>
            <input
              type="email"
              id="modalFromAddress"
              class="form-control"
              required
            />
          </div>

          <div class="mb-3">
            <label class="form-label">From Name</label>
            <input
              type="text"
              id="modalFromName"
              class="form-control"
              required
            />
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="btn btn-primary"
          >
            Save
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
  <script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
  ></script>
  <script>
    const token = sessionStorage.getItem('token');
    const API_URL = '/api/admin/mailer';

    const DRIVER_OPTIONS = [
      'smtp',
      'sendmail',
      'ses',
      'mailgun',
      'postmark',
      'log',
      'array'
    ];
    const PORT_OPTIONS = [587, 465, 2525, 25];
    const ENC_OPTIONS = ['tls', 'ssl', 'starttls', ''];

    const adminMailerModal = new bootstrap.Modal(
      document.getElementById('adminMailerModal')
    );

    function populateSelects() {
      document.getElementById('modalMailer').innerHTML =
        DRIVER_OPTIONS
          .map(v => `<option value="${v}">${v.toUpperCase()}</option>`)
          .join('');
      document.getElementById('modalPort').innerHTML =
        PORT_OPTIONS
          .map(v => `<option value="${v}">${v}</option>`)
          .join('');
      document.getElementById('modalEncryption').innerHTML =
        ENC_OPTIONS
          .map(v => `<option value="${v}">${v || 'NONE'}</option>`)
          .join('');
    }
    populateSelects();

    function openMailerModal(mode, id = '', data = {}) {
      document.getElementById('adminMailerModalLabel').textContent =
        mode === 'add' ? 'Add Mailer' : 'Edit Mailer';
      document.getElementById('modalMailerId').value = id;

      const set = (sel, val) =>
        (document.getElementById(sel).value = val ?? '');

      if (mode === 'edit') {
        set('modalMailer',      data.mailer);
        set('modalHost',        data.host);
        set('modalPort',        data.port);
        set('modalUsername',    data.username);
        set('modalPassword',    data.password);
        set('modalEncryption',  data.encryption  || '');
        set('modalFromAddress', data.from_address);
        set('modalFromName',    data.from_name);
      } else {
        set('modalMailer',      DRIVER_OPTIONS[0]);
        set('modalHost',        '');
        set('modalPort',        PORT_OPTIONS[0]);
        set('modalUsername',    '');
        set('modalPassword',    '');
        set('modalEncryption',  '');
        set('modalFromAddress', '');
        set('modalFromName',    '');
      }

      adminMailerModal.show();
    }

    let mailers = [];

    document
      .getElementById('adminMailerForm')
      .onsubmit = async e => {
      e.preventDefault();

      const id = document.getElementById('modalMailerId').value;
      const payload = {
        mailer:       document.getElementById('modalMailer').value.trim(),
        host:         document.getElementById('modalHost').value.trim(),
        port:         parseInt(document.getElementById('modalPort').value, 10),
        username:     document.getElementById('modalUsername').value.trim(),
        password:     document.getElementById('modalPassword').value,
        encryption:   document.getElementById('modalEncryption').value.trim() || null,
        from_address: document.getElementById('modalFromAddress').value.trim(),
        from_name:    document.getElementById('modalFromName').value.trim()
      };

      Swal.fire({
        title: id ? 'Updating…' : 'Adding…',
        didOpen: () => Swal.showLoading()
      });

      const res = await fetch(
        id ? `${API_URL}/${id}` : API_URL,
        {
          method: id ? 'PUT' : 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`
          },
          body: JSON.stringify(payload)
        }
      ).then(r => r.json());

      Swal.close();

      if (res.status === 'success') {
        adminMailerModal.hide();
        fetchMailers();
        Swal.fire('Success', res.message, 'success');
      } else {
        Swal.fire('Error', res.message || 'Failed', 'error');
      }
    };

    async function fetchMailers() {
      const res = await fetch(API_URL, {
        headers: { Authorization: `Bearer ${token}` }
      }).then(r => r.json());

      mailers = res.data || [];

      renderSummary();
      renderTables();
    }

    function renderSummary() {
      const activeCount = mailers.filter(m => m.status === 'active').length;
      const inactiveCount = mailers.filter(m => m.status === 'inactive').length;
      document.getElementById('activeCount').textContent = activeCount;
      document.getElementById('inactiveCount').textContent = inactiveCount;
    }

    function renderTables() {
      const activeList = mailers.filter(m => m.status === 'active');
      const inactiveList = mailers.filter(m => m.status === 'inactive');

      const aBody = document.querySelector('#activeTable tbody');
      const iBody = document.querySelector('#inactiveTable tbody');

      if (activeList.length === 0) {
        aBody.innerHTML = `
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              No active mailers.
            </td>
          </tr>`;
      } else {
        aBody.innerHTML = activeList.map(m => rowHtml(m)).join('');
      }

      if (inactiveList.length === 0) {
        iBody.innerHTML = `
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              No inactive mailers.
            </td>
          </tr>`;
      } else {
        iBody.innerHTML = inactiveList.map(m => rowHtml(m)).join('');
      }
    }

    function rowHtml(m) {
      const iconClass = m.status === 'active'
        ? 'fa-toggle-on text-success'
        : 'fa-toggle-off text-danger';

      return `
        <tr>
          <td>${m.mailer}</td>
          <td>${m.host}</td>
          <td>${m.port}</td>
          <td>${m.username}</td>
          <td>${m.encryption || '<em>none</em>'}</td>
          <td>${m.from_address}</td>
          <td>${m.from_name}</td>
          <td>
            <div class="d-flex gap-2">
              <button
                class="btn btn-sm btn-outline-secondary"
                onclick='openMailerModal(
                  "edit",
                  "${m.id}",
                  ${JSON.stringify({
                    mailer:       m.mailer,
                    host:         m.host,
                    port:         m.port,
                    username:     m.username,
                    password:     m.password,
                    encryption:   m.encryption,
                    from_address: m.from_address,
                    from_name:    m.from_name
                  })}
                )'
              >
                <i class="fa-regular fa-pen-to-square"></i>
              </button>
              <button
                class="btn btn-sm btn-outline-primary"
                onclick="toggleStatus(${m.id})"
                title="Toggle Status"
              >
                <i class="fa-solid ${iconClass}"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }

    async function toggleStatus(id) {
      Swal.fire({
        title: 'Updating status…',
        didOpen: () => Swal.showLoading()
      });

      const res = await fetch(`${API_URL}/${id}/status`, {
        method: 'PUT',
        headers: { Authorization: `Bearer ${token}` }
      }).then(r => r.json());

      Swal.close();

      if (res.status === 'success') {
        fetchMailers();
        Swal.fire('Success', res.message, 'success');
      } else {
        Swal.fire('Error', res.message || 'Failed', 'error');
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      if (!token) {
        location.href = '/';
      } else {
        fetchMailers();
      }
    });
  </script>
</body>
</html>
