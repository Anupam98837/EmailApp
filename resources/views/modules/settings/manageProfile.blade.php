{{-- resources/views/profile.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    .profile-card {
      background: #fff;
      border-radius: .5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 2rem;
    }
    .tabs-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    .nav-tabs .nav-link.active {
      border-color: #0d6efd #0d6efd #fff;
    }
    .spinner-center {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 200px;
    }
    .img-preview, .icon-preview {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 1rem;
      border: 2px solid #dee2e6;
    }
    .icon-preview {
      font-size: 4rem;
      line-height: 120px;
      color: #6c757d;
      border: none;
    }
    .toggle-password {
      position: absolute;
      top: 50%;
      right: .75rem;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6c757d;
      z-index: 2;
    }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="profile-card mx-auto" style="max-width: 700px;">
      <div class="tabs-header">
        <ul class="nav nav-tabs mb-0" id="profileTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="view-tab"
                    data-bs-toggle="tab" data-bs-target="#viewPane" type="button">
              Profile
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="password-tab"
                    data-bs-toggle="tab" data-bs-target="#passwordPane" type="button">
              Change Password
            </button>
          </li>
        </ul>
        <button class="btn btn-outline-secondary" id="logoutBtn">
          <i class="fa-solid fa-right-from-bracket"></i>
        </button>
      </div>

      <div class="tab-content">
        {{-- View / Edit Profile --}}
        <div class="tab-pane fade show active" id="viewPane" role="tabpanel">
          <div id="profileLoader" class="spinner-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading profile…</span>
            </div>
          </div>
          <form id="profileForm" class="d-none">
            <div class="text-center" id="photoContainer">
              <i id="photoIcon" class="fa-solid fa-user-circle icon-preview"></i>
              <img src="" alt="Photo" id="photoPreview" class="img-preview d-none">
            </div>
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" id="nameInput" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email (readonly)</label>
              <input type="email" id="emailInput" class="form-control" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" id="phoneInput" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary w-100" id="saveProfileBtn">
              <span id="saveProfileText">Save Changes</span>
              <span id="saveProfileSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
          </form>
        </div>

        {{-- Change Password --}}
        <div class="tab-pane fade" id="passwordPane" role="tabpanel">
          <form id="passwordForm">
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <div class="position-relative">
                <input type="password" id="currentPassword" class="form-control pe-5" required>
                <i class="fa-solid fa-eye toggle-password" data-target="currentPassword"></i>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <div class="position-relative">
                <input type="password" id="newPassword" class="form-control pe-5" required>
                <i class="fa-solid fa-eye toggle-password" data-target="newPassword"></i>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <div class="position-relative">
                <input type="password" id="confirmNewPassword" class="form-control pe-5" required>
                <i class="fa-solid fa-eye toggle-password" data-target="confirmNewPassword"></i>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="savePassBtn">
              <span id="savePassText">Change Password</span>
              <span id="savePassSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS + Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const token = sessionStorage.getItem('token');
    if (!token) location.href = '/login';

    // Toggle show/hide password
    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', () => {
        const input = document.getElementById(icon.dataset.target);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        icon.classList.toggle('fa-eye-slash');
      });
    });

    // --- Fetch profile on load ---
    async function loadProfile() {
      try {
        const res = await fetch('/api/profile', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!res.ok) throw new Error();
        const { user } = await res.json();

        document.getElementById('nameInput').value  = user.name;
        document.getElementById('emailInput').value = user.email;
        document.getElementById('phoneInput').value = user.phone || '';

        if (user.photo) {
          document.getElementById('photoPreview').src = `/storage/${user.photo}`;
          document.getElementById('photoPreview').classList.remove('d-none');
          document.getElementById('photoIcon').classList.add('d-none');
        } else {
          document.getElementById('photoIcon').classList.remove('d-none');
          document.getElementById('photoPreview').classList.add('d-none');
        }
      } catch {
        Swal.fire('Error','Could not load profile','error');
      } finally {
        document.getElementById('profileLoader').classList.add('d-none');
        document.getElementById('profileForm').classList.remove('d-none');
      }
    }

    // --- Update profile ---
    document.getElementById('profileForm').onsubmit = async e => {
      e.preventDefault();
      const btn     = document.getElementById('saveProfileBtn');
      const spinner = document.getElementById('saveProfileSpinner');
      btn.disabled   = true; spinner.classList.remove('d-none');

      const form = new FormData();
      form.append('_method','PUT');
      form.append('name',  document.getElementById('nameInput').value.trim());
      form.append('phone', document.getElementById('phoneInput').value.trim());

      try {
        const res  = await fetch('/api/profile', {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${token}` },
          body: form
        });
        const json = await res.json();
        if (res.ok) {
          Swal.fire('Saved','Profile updated','success');
        } else {
          throw new Error(json.message || 'Error');
        }
      } catch (err) {
        Swal.fire('Error', err.message, 'error');
      } finally {
        btn.disabled = false; spinner.classList.add('d-none');
      }
    };

    // --- Change password ---
    document.getElementById('passwordForm').onsubmit = async e => {
      e.preventDefault();
      const btn     = document.getElementById('savePassBtn');
      const spinner = document.getElementById('savePassSpinner');
      btn.disabled   = true; spinner.classList.remove('d-none');

      const payload = {
        current_password:          document.getElementById('currentPassword').value,
        new_password:              document.getElementById('newPassword').value,
        new_password_confirmation: document.getElementById('confirmNewPassword').value
      };

      try {
        const res  = await fetch('/api/password', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (res.ok) {
          Swal.fire('Success','Password changed','success');
          document.getElementById('passwordForm').reset();
        } else {
          throw new Error(json.message || 'Error');
        }
      } catch (err) {
        Swal.fire('Error', err.message, 'error');
      } finally {
        btn.disabled = false; spinner.classList.add('d-none');
      }
    };

    // --- Logout ---
    document.getElementById('logoutBtn').onclick = async () => {
      await fetch('/api/logout', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      sessionStorage.removeItem('token');
      location.href = '/login';
    };

    // Initialize
    loadProfile();
  </script>
</body>
</html>
