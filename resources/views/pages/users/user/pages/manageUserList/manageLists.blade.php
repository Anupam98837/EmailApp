<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Lists</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
  />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    .accordion-button .fa-list { color: var(--bs-primary); }
    .table-actions button { margin-right: .25rem; }
    #refreshBtn { margin-left: .5rem; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Email Lists &amp; Subscribers</h1>
      <div>
        <button class="btn btn-outline-secondary" id="refreshBtn" title="Refresh">
          <i class="fa-solid fa-arrows-rotate"></i>
        </button>
        <button class="btn btn-primary" onclick="openListModal('add')">
          <i class="fa-solid fa-list-plus"></i> New List
        </button>
      </div>
    </div>

    <div class="accordion" id="listAccordion">
      <!-- populated by JS -->
    </div>
  </div>

  <!-- List Modal -->
  <div class="modal fade" id="listModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" id="listForm">
        <div class="modal-header">
          <h5 class="modal-title" id="listModalLabel"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @csrf
          <input type="hidden" id="modalListId">
          <div class="mb-3">
            <label class="form-label">List Title</label>
            <input type="text" id="modalListTitle" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea id="modalListDesc" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="modalListSubmit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Subscriber Modal -->
  <div class="modal fade" id="subscriberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form class="modal-content" id="subscriberForm">
        <div class="modal-header">
          <h5 class="modal-title" id="subscriberModalLabel"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @csrf
          <input type="hidden" id="modalSubscriberUuid">
          <div class="row gy-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" id="modalSubscriberName" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" id="modalSubscriberEmail" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" id="modalSubscriberPhone" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="modalSubscriberSubmit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const token = sessionStorage.getItem('access_token');
    const listModal       = new bootstrap.Modal('#listModal');
    const subscriberModal = new bootstrap.Modal('#subscriberModal');
    let currentListId, listAction, subscriberAction;

    document.addEventListener('DOMContentLoaded', () => {
      if (!token) return location.href = '/';
      document.getElementById('refreshBtn').onclick = () => location.reload();
      fetchLists();

      // List form
      document.getElementById('listForm').onsubmit = async e => {
        e.preventDefault();
        const id    = document.getElementById('modalListId').value;
        const title = document.getElementById('modalListTitle').value.trim();
        const desc  = document.getElementById('modalListDesc').value.trim();
        if (!title) return Swal.fire('Error','Title is required','error');

        const method = listAction==='add' ? 'POST' : 'PUT';
        const url    = listAction==='add'
          ? '/api/lists'
          : `/api/lists/${id}`;

        const res = await fetch(url, {
          method,
          headers: {
            'Content-Type':'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ title, description: desc })
        }).then(r => r.json());

        if (res.status==='success') {
          listModal.hide();
          fetchLists();
          Swal.fire('Success', res.message, 'success');
        } else {
          Swal.fire('Error', res.message||'Failed', 'error');
        }
      };

      // Subscriber form
      document.getElementById('subscriberForm').onsubmit = async e => {
        e.preventDefault();
        const uuid  = document.getElementById('modalSubscriberUuid').value;
        const name  = document.getElementById('modalSubscriberName').value.trim();
        const email = document.getElementById('modalSubscriberEmail').value.trim();
        const phone = document.getElementById('modalSubscriberPhone').value.trim();
        if (!name||!email) return Swal.fire('Error','Name & Email required','error');

        const method = subscriberAction==='add' ? 'POST' : 'PUT';
        const url    = subscriberAction==='add'
          ? `/api/lists/${currentListId}/users`
          : `/api/lists/${currentListId}/users/${uuid}`;

        const res = await fetch(url, {
          method,
          headers: {
            'Content-Type':'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ name, email, phone })
        }).then(r => r.json());

        if (res.status==='success') {
          subscriberModal.hide();
          loadSubscribers(currentListId, openIndex);
          Swal.fire('Success', res.message, 'success');
        } else {
          Swal.fire('Error', res.message||'Failed', 'error');
        }
      };
    });

    // Fetch and render lists
    async function fetchLists() {
      const { data } = await fetch('/api/lists', {
        headers: { 'Authorization': `Bearer ${token}` }
      }).then(r => r.json());

      const acc = document.getElementById('listAccordion');
      acc.innerHTML = data.map((l,i) => listCard(l,i)).join('');
      data.forEach((l,i) => {
        document.getElementById(`collapse${i}`)
          .addEventListener('show.bs.collapse', () => {
            currentListId = l.id;
            loadSubscribers(l.id, i);
          });
      });
    }

    // List accordion item
    function listCard(l, i) {
      return `
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading${i}">
          <div class="d-flex align-items-center w-100">
            <button
              class="accordion-button collapsed flex-grow-1"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#collapse${i}"
            >
              <i class="fa-solid fa-list me-2"></i>
              <span>${l.title}</span>
              <span class="badge bg-${l.is_active?'success':'danger'} ms-3">
                ${l.is_active?'Active':'Inactive'}
              </span>
            </button>
            <div class="ms-2 d-flex">
              <button class="btn btn-sm btn-outline-secondary me-1"
                onclick="openListModal('edit','${l.id}','${l.title}','${l.description}')">
                <i class="fa-regular fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-outline-${l.is_active?'danger':'success'}"
                onclick="toggleList(${l.id})">
                <i class="fa-solid ${l.is_active?'fa-ban':'fa-power-off'}"></i>
              </button>
            </div>
          </div>
        </h2>
        <div id="collapse${i}" class="accordion-collapse collapse" data-bs-parent="#listAccordion">
          <div class="accordion-body">
            <div class="d-flex mb-2">
              <input type="text" placeholder="Search subscribers…" class="form-control form-control-sm me-2"
                oninput="filterTable(this,'subTable${i}')">
              <button class="btn btn-sm btn-primary"
                onclick="openSubscriberModal('add','${l.id}')">
                <i class="fa-solid fa-user-plus"></i>
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless table-sm" id="subTable${i}">
                <thead class="table-light text-center">
                  <tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>`;
    }

    // Load subscribers for list
    async function loadSubscribers(listId, idx) {
      const { data } = await fetch(`/api/lists/${listId}/users`, {
        headers:{ 'Authorization': `Bearer ${token}` }
      }).then(r => r.json());
      const tb = document.querySelector(`#subTable${idx} tbody`);
      tb.innerHTML = data.map(u => `
        <tr class="text-center">
          <td>${u.name}</td>
          <td>${u.email}</td>
          <td>${u.phone||''}</td>
          <td><span class="badge bg-${u.is_active?'success':'danger'}">
            ${u.is_active?'Active':'Inactive'}</span></td>
          <td class="table-actions">
            <button class="btn btn-sm btn-outline-secondary" 
              onclick="openSubscriberModal('edit','${listId}','${u.user_uuid}','${u.name}','${u.email}','${u.phone}')">
              <i class="fa-regular fa-pen-to-square"></i>
            </button>
            <button class="btn btn-sm btn-outline-${u.is_active?'danger':'success'}"
              onclick="toggleSubscriber('${listId}','${u.user_uuid}')">
              <i class="fa-solid ${u.is_active?'fa-user-slash':'fa-user-check'}"></i>
            </button>
          </td>
        </tr>`).join('');
    }

    // Modals openers
    function openListModal(mode, id='', title='', desc='') {
      listAction = mode;
      document.getElementById('modalListId').value = id;
      document.getElementById('modalListTitle').value = title;
      document.getElementById('modalListDesc').value  = desc;
      document.getElementById('listModalLabel').textContent = mode==='add' ? 'Add List' : 'Edit List';
      listModal.show();
    }

    function openSubscriberModal(mode, listId, uuid='', name='', email='', phone='') {
      subscriberAction = mode;
      currentListId = listId;
      document.getElementById('modalSubscriberUuid').value   = uuid;
      document.getElementById('modalSubscriberName').value   = name;
      document.getElementById('modalSubscriberEmail').value  = email;
      document.getElementById('modalSubscriberPhone').value  = phone;
      document.getElementById('subscriberModalLabel').textContent = mode==='add' ? 'Add Subscriber' : 'Edit Subscriber';
      subscriberModal.show();
    }

    // Toggle list active status
    function toggleList(id) {
      Swal.fire({
        title: 'Change list status?',
        icon: 'warning',
        showCancelButton: true
      }).then(ok => {
        if (!ok.isConfirmed) return;
        fetch(`/api/lists/${id}/toggle`, {
          method:'PATCH',
          headers:{ 'Authorization':`Bearer ${token}` }
        })
        .then(r=>r.json())
        .then(d=>{
          Swal.fire('Done', d.message, 'success');
          fetchLists();
        });
      });
    }

    // Toggle subscriber active status
    function toggleSubscriber(listId, uuid) {
      Swal.fire({
        title: 'Change subscriber status?',
        icon: 'warning',
        showCancelButton: true
      }).then(ok => {
        if (!ok.isConfirmed) return;
        fetch(`/api/lists/${listId}/users/${uuid}/toggle`, {
          method:'PATCH',
          headers:{ 'Authorization':`Bearer ${token}` }
        })
        .then(r=>r.json())
        .then(d=>{
          Swal.fire('Done', d.message, 'success');
          document.querySelector('.accordion-collapse.show button').click();
          document.querySelector('.accordion-collapse.show button').click();
        });
      });
    }

    // Filter table rows
    function filterTable(input, tableId) {
      const term = input.value.toLowerCase();
      document.getElementById(tableId)
        .querySelectorAll('tbody tr')
        .forEach(r => r.style.display = r.textContent.toLowerCase().includes(term) ? '' : 'none');
    }
  </script>
</body>
</html>
