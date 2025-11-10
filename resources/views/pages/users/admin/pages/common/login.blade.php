<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Horizon Alienz | Admin Login</title>

  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <!-- Font Awesome -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
  />
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
  />
  <!-- Main styles -->
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <link
    rel="stylesheet"
    href="{{ asset('assets/css/common/loginSignup.css') }}"
  />
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-body">
  <div class="welcome-container">
    <!-- Left Section - Branding -->
    <div class="brand-section">
      <div class="brand-content">
        <img
          src="{{ asset('assets/media/web_assets/logo1.png') }}"
          alt="Horizon Alienz"
          class="brand-logo img-fluid"
        />
        <h1 class="brand-title">Admin Dashboard</h1>
        <p class="brand-description">
          Access the Horizon Alienz administration panel to manage users, campaigns, and system settings. Maintain full control over your email marketing platform.
        </p>
        <div class="brand-highlights">
          <div class="highlight-item">
            <i class="fas fa-shield-alt"></i>
            <span>Secure Admin Portal</span>
          </div>
          <div class="highlight-item">
            <i class="fas fa-cog"></i>
            <span>System Configuration</span>
          </div>
          <div class="highlight-item">
            <i class="fas fa-chart-pie"></i>
            <span>Advanced Analytics</span>
          </div>
        </div>
      </div>
    </div>
    

    <!-- Right Section - Auth Forms -->
    <div class="auth-section">
      <div class="auth-card">
        <!-- Tab Navigation -->
        <ul class="auth-tabs">
          <li class="auth-tab active" data-target="login">
            <i class="fas fa-sign-in-alt"></i>
            <span>Admin Login</span>
          </li>
        </ul>

        <!-- Login Form -->
        <div id="login" class="auth-form active">
          <form id="loginForm">
            <div class="form-group floating-input">
              <input
                type="email"
                id="loginEmail"
                name="email"
                placeholder=" "
                required
              />
              <label for="loginEmail">Email</label>
              <i class="fas fa-envelope input-icon"></i>
            </div>

            <div class="form-group floating-input">
              <input
                type="password"
                id="loginPassword"
                name="password"
                placeholder=" "
                required
              />
              <label for="loginPassword">Password</label>
              <i class="fas fa-lock input-icon"></i>
              <span class="toggle-password">
                <i class="fas fa-eye"></i>
              </span>
            </div>

            <div class="form-options">
              <div class="form-check">
                <input
                  type="checkbox"
                  id="rememberMe"
                  name="remember"
                />
                <label for="rememberMe">Remember me</label>
              </div>
            </div>

            <button type="submit" class="auth-btn primary">
              <span class="btn-text">Admin Login</span>
              <i class="fas fa-arrow-right btn-icon"></i>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Floating decorative elements -->
  <div class="floating-elements">
    <div class="floating-circle circle-1"></div>
    <div class="floating-circle circle-2"></div>
    <div class="floating-circle circle-3"></div>
  </div>

  <!-- Bootstrap Bundle JS -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>

  <!-- Admin Login Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Floating label effect
      document.querySelectorAll('.floating-input input').forEach((input) => {
        const parent = input.parentNode;
        function update() {
          if (input.value) parent.classList.add('focused');
          else parent.classList.remove('focused');
        }
        input.addEventListener('focus', () => parent.classList.add('focused'));
        input.addEventListener('blur', update);
        update();
      });

      // Toggle password visibility
      document.querySelectorAll('.toggle-password').forEach((btn) => {
        btn.addEventListener('click', function () {
          const input = this.closest('.form-group').querySelector('input');
          const icon = this.querySelector('i');
          if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
          } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
          }
        });
      });

      // ADMIN LOGIN form submission
      document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type=submit]');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Authenticating...';
        btn.disabled = true;

        try {
          const res = await fetch('/api/admin/login', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              email: e.target.email.value.trim(),
              password: e.target.password.value
            })
          });
          
          const data = await res.json();
          btn.innerHTML = orig;
          btn.disabled = false;

          if (data.status === 'success') {
            // Store the admin token securely
            sessionStorage.setItem('token', data.access_token);
            
            Swal.fire({
              icon: 'success',
              title: 'Admin Access Granted',
              text: 'Redirecting to dashboard...',
              timer: 1500,
              showConfirmButton: false
            }).then(() => {
              // Redirect to admin dashboard
              window.location.href = '/admin/dashboard';
            });
          } else {
            Swal.fire({ 
              icon: 'error', 
              title: 'Admin Login Failed', 
              text: data.message || 'Invalid credentials'
            });
          }
        } catch (err) {
          btn.innerHTML = orig;
          btn.disabled = false;
          Swal.fire({ 
            icon: 'error', 
            title: 'Connection Error', 
            text: 'Please try again later.' 
          });
          console.error('Admin login error:', err);
        }
      });
    });
  </script>
</body>
</html>