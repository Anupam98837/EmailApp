<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Lists</title>

  <!-- Bootstrap & Icons -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
  />

  <!-- CSRF -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Your CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/pages/manageList/manageList.css') }}">

  <!-- Spinner & Refresh Animation -->
  <style>
    @keyframes spin { 100% { transform: rotate(360deg); } }
    #refreshBtn.rotating .fa-arrows-rotate { animation: spin 1s linear infinite; }
  </style>
</head>
<body class="bg-light">

  <div class="container py-5">
    <!-- Summary Row -->
    <div class="row mb-4" id="summaryRow">
      <div class="col-4">
        <div class="summary-card">
          <div class="icon"><i class="fa-solid fa-list-check"></i></div>
          <h5 id="totalLists">0</h5>
          <small>Lists</small>
        </div>
      </div>
      <div class="col-4">
        <div class="summary-card">
          <div class="icon"><i class="fa-solid fa-user-group"></i></div>
          <h5 id="totalSubscribers">0</h5>
          <small>Subscribers</small>
        </div>
      </div>
      <div class="col-4">
        <div class="summary-card">
          <div class="icon"><i class="fa-solid fa-toggle-on"></i></div>
          <h5 id="activeLists">0</h5>
          <small>Lists</small>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="search-export">
        <input type="text" id="globalSearch" class="form-control form-control-sm" placeholder="Search all lists…">
        <button class="btn btn-outline-success btn-sm" id="exportCsvBtn">
          <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </button>
      </div>
      <div>
        <button class="btn btn-outline-secondary" id="refreshBtn" title="Refresh">
          <i class="fa-solid fa-arrows-rotate"></i>
        </button>
        <button class="btn btn-primary" onclick="openListModal('add')">
          <i class="fa-solid fa-list-plus me-1"></i> New List
        </button>
      </div>
    </div>

    <div class="accordion" id="listAccordion">
      <!-- loading spinner initially -->
      <div class="text-center py-5" id="accordionLoader">
        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
      </div>
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
            <label class="form-label">
              <i class="fa-solid fa-heading me-1 text-primary"></i> List Title
            </label>
            <input type="text" id="modalListTitle" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">
              <i class="fa-solid fa-align-left me-1 text-primary"></i> Description
            </label>
            <textarea id="modalListDesc" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
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
              <label class="form-label">
                <i class="fa-solid fa-user me-1 text-primary"></i> Name
              </label>
              <input type="text" id="modalSubscriberName" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">
                <i class="fa-solid fa-envelope me-1 text-primary"></i> Email
              </label>
              <input type="email" id="modalSubscriberEmail" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">
                <i class="fa-solid fa-phone me-1 text-primary"></i> Phone
              </label>
              <input type="text" id="modalSubscriberPhone" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Globals
    const token             = sessionStorage.getItem('token');
    const rowsPerPage       = 10;
    let importListId        = null;
    let allSubscribers      = {};    // keyed by listIndex
    let currentPage         = {};    // keyed by listIndex
    const totalListsEl      = document.getElementById('totalLists');
    const totalSubsEl       = document.getElementById('totalSubscribers');
    const activeListsEl     = document.getElementById('activeLists');
    const globalSearchInput = document.getElementById('globalSearch');
    const exportCsvBtn      = document.getElementById('exportCsvBtn');
    const refreshBtn        = document.getElementById('refreshBtn');

    const listModal       = new bootstrap.Modal('#listModal');
    const subscriberModal = new bootstrap.Modal('#subscriberModal');
    let currentListId, listAction, subscriberAction, openIndex;

    document.addEventListener('DOMContentLoaded', () => {
      if (!token) return location.href='/';

      // Refresh button triggers fetchLists with rotation
      refreshBtn.onclick = () => fetchLists();

      // Global search
      globalSearchInput.oninput = () => {
        document.querySelectorAll('#listAccordion .accordion-item').forEach(item => {
          const title = item.querySelector('.accordion-button').textContent.toLowerCase();
          item.style.display = title.includes(globalSearchInput.value.toLowerCase()) ? '' : 'none';
        });
      };

      // Export CSV
      exportCsvBtn.onclick = () => {
        let csv = 'List Title,Description,Subscribers,Status\n';
        document.querySelectorAll('#listAccordion .accordion-item').forEach((item,i) => {
          const header = item.querySelector('.accordion-button').textContent.trim().split('\n')[0];
          const badge  = item.querySelector('.badge').textContent.trim();
          const desc   = item.querySelector('textarea')?.value.replace(/,/g,' ') || '';
          const count  = allSubscribers[i]?.length || 0;
          csv += `${header},${desc},${count},${badge}\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'lists.csv';
        a.click();
        URL.revokeObjectURL(url);
      };

      // List form
      document.getElementById('listForm').onsubmit = async e => {
        e.preventDefault();
        const id    = document.getElementById('modalListId').value;
        const title = document.getElementById('modalListTitle').value.trim();
        if (!title) return Swal.fire('Error','Title is required','error');
        Swal.fire({
          title: listAction==='add' ? 'Creating list…' : 'Updating list…',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });
        try {
          const url    = listAction==='add' ? '/api/lists' : `/api/lists/${id}`;
          const method = listAction==='add' ? 'POST' : 'PUT';
          const res    = await fetch(url, {
            method, headers:{
              'Content-Type':'application/json',
              'Authorization':`Bearer ${token}`
            },
            body: JSON.stringify({
              title,
              description: document.getElementById('modalListDesc').value.trim()
            })
          }).then(r=>r.json());
          Swal.close();
          if (res.status==='success') {
            listModal.hide();
            fetchLists();
            Swal.fire('Success', res.message, 'success');
          } else {
            Swal.fire('Error', res.message||'Failed','error');
          }
        } catch(err) {
          Swal.close();
          Swal.fire('Error', err.message, 'error');
        }
      };

      // Subscriber form
      document.getElementById('subscriberForm').onsubmit = async e => {
        e.preventDefault();
        const uuid  = document.getElementById('modalSubscriberUuid').value;
        const name  = document.getElementById('modalSubscriberName').value.trim();
        const email = document.getElementById('modalSubscriberEmail').value.trim();
        if (!name||!email) return Swal.fire('Error','Name & Email required','error');
        Swal.fire({
          title: subscriberAction==='add' ? 'Adding subscriber…' : 'Updating subscriber…',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });
        try {
          const url    = subscriberAction==='add'
            ? `/api/lists/${currentListId}/users`
            : `/api/lists/${currentListId}/users/${uuid}`;
          const method = subscriberAction==='add'?'POST':'PUT';
          const res    = await fetch(url,{
            method, headers:{
              'Content-Type':'application/json',
              'Authorization':`Bearer ${token}`
            },
            body: JSON.stringify({
              name, email,
              phone: document.getElementById('modalSubscriberPhone').value.trim()
            })
          }).then(r=>r.json());
          Swal.close();
          if (res.status==='success') {
            subscriberModal.hide();
            loadSubscribers(currentListId, openIndex);
            Swal.fire('Success', res.message, 'success');
          } else {
            Swal.fire('Error', res.message||'Failed','error');
          }
        } catch(err) {
          Swal.close();
          Swal.fire('Error', err.message, 'error');
        }
      };

      // Initial load
      fetchLists();
    });

    async function fetchLists() {
      const acc = document.getElementById('listAccordion');
      // start refresh animation & show loader
      refreshBtn.classList.add('rotating');
      acc.innerHTML = `<div class="text-center py-5">
        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
      </div>`;
      try {
        const { data } = await fetch('/api/lists', {
          headers:{ 'Authorization':`Bearer ${token}` }
        }).then(r=>r.json());
        // stop refresh animation
        refreshBtn.classList.remove('rotating');

        // update summary
        totalListsEl.textContent  = data.length;
        activeListsEl.textContent = data.filter(l=>l.is_active).length;
        // total subscribers
        let allSubs = 0;
        for (let l of data) {
          const res = await fetch(`/api/lists/${l.id}/users`,{
            headers:{ 'Authorization':`Bearer ${token}` }
          }).then(r=>r.json());
          allSubs += res.data.length;
        }
        totalSubsEl.textContent = allSubs;

        // render accordion
        if (!data.length) {
          acc.innerHTML = `
            <div class="text-center py-5 text-muted">
              <i class="fa-solid fa-inbox fa-3x mb-3"></i>
              <p>No lists added yet.</p>
            </div>`;
          return;
        }
        acc.innerHTML = data.map((l,i)=> listCard(l,i) ).join('');
        data.forEach((l,i)=>{
          document.getElementById(`collapse${i}`)
            .addEventListener('show.bs.collapse',()=>{
              currentListId = l.id;
              openIndex     = i;
              loadSubscribers(l.id,i);
            });
        });
      } catch (err) {
        refreshBtn.classList.remove('rotating');
        acc.innerHTML = `<div class="text-center py-5 text-danger">
          <p>Error loading lists.</p>
        </div>`;
      }
    }

    function listCard(l,i) {
      return `
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading${i}">
          <button class="accordion-button collapsed"
            type="button" data-bs-toggle="collapse" data-bs-target="#collapse${i}">
            <i class="fa-solid fa-list me-2"></i>${l.title}
            <span class="badge bg-${l.is_active?'success':'danger'} ms-3">
              ${l.is_active?'Active':'Inactive'}
            </span>
          </button>
        </h2>
        <div id="collapse${i}" class="accordion-collapse collapse" data-bs-parent="#listAccordion">
          <div class="accordion-body">
            <div class="d-flex mb-3 w-100 search-input">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" placeholder="Search…" class="form-control form-control-sm me-2"
                oninput="filterTable(this,'subTable${i}')">
              <button class="btn btn-sm btn-primary" onclick="openSubscriberModal('add','${l.id}')">
                <i class="fa-solid fa-user-plus"></i>
              </button>
              <button class="btn btn-sm btn-secondary ms-2"
                onclick="openImportModal(${l.id})">
                Import
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless table-sm" id="subTable${i}">
                <thead class="table-light text-center">
                  <tr>
                    <th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- spinner while loading subscribers -->
                  <tr><td colspan="5" class="text-center py-3">
                    <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                  </td></tr>
                </tbody>
              </table>
            </div>
            <div id="pager${i}" class="mt-2 text-center"></div>
          </div>
        </div>
        <div class="ps-3 pb-3 d-flex">
          <button class="btn btn-sm btn-outline-secondary "
            onclick="openListModal('edit','${l.id}','${l.title}','${l.description}')">
            <i class="fa-regular fa-pen-to-square me-1"></i>Edit
          </button>
          <button class="btn btn-sm btn-outline-${l.is_active?'danger':'success'} mx-2"
            onclick="toggleList(${l.id})">
            <i class="fa-solid ${l.is_active?'fa-ban':'fa-power-off'} me-1"></i>
            ${l.is_active?'Disable':'Enable'}
          </button>
          <button class="btn btn-sm btn-outline-warning"
            onclick="emptyList(${l.id})"
            title="Remove all subscribers in this list">
            <i class="fa-solid fa-broom me-1"></i> Empty
          </button>
        </div>
      </div>`;
    }

    async function loadSubscribers(listId, idx) {
      const tbody = document.querySelector(`#subTable${idx} tbody`);
      const pager = document.getElementById(`pager${idx}`);
      // show loading spinner
      tbody.innerHTML = `
        <tr><td colspan="5" class="text-center py-3">
          <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
        </td></tr>`;
      try {
        const { data } = await fetch(`/api/lists/${listId}/users`, {
          headers:{ 'Authorization':`Bearer ${token}` }
        }).then(r=>r.json());
        if (!data.length) {
          tbody.innerHTML = `
            <tr><td colspan="5" class="text-center text-muted py-4">
              <i class="fa-solid fa-user-slash fa-2x mb-2"></i><br>No list users yet.
            </td></tr>`;
          pager.innerHTML = '';
          return;
        }
        allSubscribers[idx] = data;
        currentPage[idx]    = 1;
        renderPage(idx);
        renderPager(idx);
      } catch (_) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">
          Error loading subscribers.
        </td></tr>`;
        pager.innerHTML = '';
      }
    }

    function renderPage(idx) {
      const data = allSubscribers[idx] || [];
      const page = currentPage[idx] || 1;
      const start = (page-1)*rowsPerPage;
      const slice = data.slice(start, start+rowsPerPage);
      const tbody = document.querySelector(`#subTable${idx} tbody`);
      tbody.innerHTML = slice.map(u=>`
        <tr class="text-center">
          <td>${u.name}</td>
          <td>${u.email}</td>
          <td>${u.phone||''}</td>
          <td><span class="badge bg-${u.is_active?'success':'danger'}">
            ${u.is_active?'Active':'Inactive'}</span></td>
          <td class="table-actions">
            <button class="btn btn-sm btn-outline-secondary me-1"
              onclick="openSubscriberModal('edit','${u.list_id}','${u.user_uuid}','${u.name}','${u.email}','${u.phone}')">
              <i class="fa-regular fa-pen-to-square"></i>
            </button>
            <button class="btn btn-sm btn-outline-${u.is_active?'danger':'success'}"
              onclick="toggleSubscriber(${u.list_id},'${u.user_uuid}')">
              <i class="fa-solid ${u.is_active?'fa-user-slash':'fa-user-check'}"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger"
              onclick="deleteSubscriber(${u.list_id},'${u.user_uuid}')"
              title="Delete">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>`).join('');
    }

    function renderPager(idx) {
      const data  = allSubscribers[idx] || [];
      const pages = Math.ceil(data.length / rowsPerPage);
      const page  = currentPage[idx] || 1;
      const ctr   = document.getElementById(`pager${idx}`);
      ctr.innerHTML = '';
      ctr.className = 'pagination';

      function btn(html, p, disabled) {
        const b = document.createElement('button');
        b.type       = 'button';
        b.innerHTML  = html;
        b.disabled   = disabled;
        b.className  = 'btn btn-outline-primary pager-btn';
        if (!disabled) b.addEventListener('click', () => changePage(idx, p));
        return b;
      }

      ctr.appendChild(btn('<i class="fa-solid fa-angles-left"></i>', 1, page===1));
      ctr.appendChild(btn('<i class="fa-solid fa-angle-left"></i>', page-1, page===1));
      ctr.appendChild(btn(`${page} / ${pages}`, page, true));
      ctr.appendChild(btn('<i class="fa-solid fa-angle-right"></i>', page+1, page===pages));
      ctr.appendChild(btn('<i class="fa-solid fa-angles-right"></i>', pages, page===pages));
    }

    function changePage(idx,p) {
      if (p<1) p=1;
      const max = Math.ceil((allSubscribers[idx]||[]).length/rowsPerPage);
      if (p>max) p=max;
      currentPage[idx] = p;
      renderPage(idx);
      renderPager(idx);
    }

    async function toggleList(id) {
      let { isConfirmed } = await Swal.fire({title:'Change list status?', icon:'warning', showCancelButton:true});
      if (!isConfirmed) return;
      let res = await fetch(`/api/lists/${id}/toggle`,{ method:'PATCH', headers:{ 'Authorization':`Bearer ${token}` }})
        .then(r=>r.json());
      Swal.fire(res.status==='success'?'success':'error', res.message, res.status);
      fetchLists();
    }

    async function toggleSubscriber(listId,uuid) {
      let { isConfirmed } = await Swal.fire({title:'Change subscriber status?', icon:'warning', showCancelButton:true});
      if (!isConfirmed) return;
      let res = await fetch(`/api/lists/${listId}/users/${uuid}/toggle`,{ method:'PATCH', headers:{ 'Authorization':`Bearer ${token}` }})
        .then(r=>r.json());
      Swal.fire(res.status==='success'?'success':'error', res.message, res.status);
      loadSubscribers(listId, openIndex);
    }

    function filterTable(input,tableId) {
      const term = input.value.toLowerCase();
      document.getElementById(tableId).querySelectorAll('tbody tr')
        .forEach(r=> r.style.display = r.textContent.toLowerCase().includes(term)? '':'none');
    }

    window.openListModal = (mode, id='', title='', desc='') => {
      listAction = mode;
      document.getElementById('modalListId').value    = id;
      document.getElementById('modalListTitle').value = title;
      document.getElementById('modalListDesc').value  = desc;
      document.getElementById('listModalLabel').textContent =
        mode==='add' ? 'Add List' : 'Edit List';
      listModal.show();
    };

    window.openSubscriberModal = (mode, listId, uuid='', name='', email='', phone='') => {
      subscriberAction = mode;
      currentListId = listId;
      document.getElementById('modalSubscriberUuid').value   = uuid;
      document.getElementById('modalSubscriberName').value   = name;
      document.getElementById('modalSubscriberEmail').value  = email;
      document.getElementById('modalSubscriberPhone').value  = phone;
      document.getElementById('subscriberModalLabel').textContent =
        mode==='add' ? 'Add Subscriber' : 'Edit Subscriber';
      subscriberModal.show();
    };

    window.openImportModal = (listId) => {
      importListId = listId;
      Swal.fire({
        title: 'Select a CSV file',
        html: `<input type="file" id="swalImportFile" accept=".csv" class="swal2-file">`,
        showCancelButton: true,
        confirmButtonText: 'Upload',
        preConfirm: () => {
          const file = Swal.getPopup().querySelector('#swalImportFile').files[0];
          if (!file) Swal.showValidationMessage('Please select a CSV file');
          return file;
        }
      }).then(async (result) => {
        if (!result.isConfirmed) return;
        const file = result.value, form = new FormData();
        form.append('csv_file', file);
        Swal.fire({ title: 'Importing…', allowOutsideClick: false, didOpen: ()=> Swal.showLoading() });
        try {
          const res = await fetch(`/api/lists/${importListId}/users/import`, {
            method:'POST',
            headers:{ 'Authorization':`Bearer ${token}` },
            body: form
          }).then(r=>r.json());
          Swal.close();
          if (res.status==='success') {
            Swal.fire('Imported!', res.message, 'success');
            loadSubscribers(importListId, openIndex);
          } else {
            Swal.fire('Error', res.message||'Import failed', 'error');
          }
        } catch(err) {
          Swal.close();
          Swal.fire('Error', err.message, 'error');
        }
      });
    };
    async function deleteSubscriber(listId, uuid) {
    const { isConfirmed } = await Swal.fire({
      title: 'Delete this subscriber?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete',
      confirmButtonColor: '#d33'
    });
    if (!isConfirmed) return;

    Swal.fire({ title: 'Deleting…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
      const res = await fetch(`/api/lists/${listId}/users/${uuid}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      }).then(r => r.json());

      Swal.close();

      if (res.status === 'success') {
        Swal.fire('Deleted!', res.message || 'Subscriber removed.', 'success');
        loadSubscribers(listId, openIndex); // refresh current list
        // Update global summary counts (optional quick decrement):
        totalSubsEl.textContent = (parseInt(totalSubsEl.textContent, 10) - 1).toString();
      } else {
        Swal.fire('Error', res.message || 'Could not delete subscriber.', 'error');
      }
    } catch (e) {
      Swal.close();
      Swal.fire('Error', e.message, 'error');
    }
  }
  async function emptyList(listId) {
      const { isConfirmed } = await Swal.fire({
        title: 'Empty this list?',
        html: 'All subscribers in this list will be <strong>permanently deleted</strong>.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, empty it',
        confirmButtonColor: '#d33'
      });
      if (!isConfirmed) return;

      Swal.fire({ title: 'Working…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      try {
        const res = await fetch(`/api/lists/${listId}/users/empty`, {
          method: 'DELETE',
          headers: { 'Authorization': `Bearer ${token}` }
        }).then(r => r.json());

        Swal.close();

        if (res.status === 'success') {
          Swal.fire('Emptied', res.message, 'success');
          // If the list accordion is open, refresh its subscribers table
            if (typeof openIndex !== 'undefined' && currentListId == listId) {
              loadSubscribers(listId, openIndex);
            }
          // Recompute total subscribers quickly (subtract deleted count if we tracked before)
          // Easiest: refetch all lists for accurate summary:
          fetchLists();
        } else {
          Swal.fire('Error', res.message || 'Could not empty list.', 'error');
        }
      } catch (e) {
        Swal.close();
        Swal.fire('Error', e.message, 'error');
      }
    }

        
  </script>
</body>
</html>
