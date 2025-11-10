<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Horizon Alienz | Login</title>

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
        {{-- <img
          src="{{ asset('assets/media/web_assets/sendEmail.png') }}"
          alt="Horizon Alienz"
          class="brand-logo img-fluid"
        /> --}}
        <h1 class="brand-title">Run Stunning Email Campaigns in Minutes</h1>
        <p class="brand-description">
          Horizon Alienz Emailer gives you everything you need to design, send, and track your email campaigns—from beautiful templates and subscriber segmentation to real‑time performance analytics—all in one place.
        </p>
        <div class="brand-highlights">
          <div class="highlight-item">
            <i class="fas fa-magic"></i>
            <span>Drag &amp; Drop Builder</span>
          </div>
          <div class="highlight-item">
            <i class="fas fa-users"></i>
            <span>Smart List Management</span>
          </div>
          <div class="highlight-item">
            <i class="fas fa-chart-line"></i>
            <span>Real‑Time Analytics</span>
          </div>
        </div>
      </div>
    </div>
    

    <!-- Right Section - Auth Forms -->
    <div class="auth-section">
      
      <div class="auth-card">
        <div class="d-flex justify-content-end mb-3">
          <a href="/admin/login" class="text-decoration-none small text-muted">
            <i class="fas fa-user-shield me-1"></i> Admin Login
          </a>
        </div>        <!-- Tab Navigation -->
        <ul class="auth-tabs">
          <li class="auth-tab active" data-target="login">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login</span>
          </li>
          <li class="auth-tab" data-target="signup">
            <i class="fas fa-user-plus"></i>
            <span>Sign Up</span>
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
              <a href="#" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="auth-btn primary">
              <span class="btn-text">Log In</span>
              <i class="fas fa-arrow-right btn-icon"></i>
            </button>

            {{-- <div class="auth-divider">
              <span>or continue with</span>
            </div> --}}

            {{-- <div class="social-auth">
              <a href="#" class="social-btn google">
                <img
                  src="https://demo.acellemail.com/images/google-login.svg"
                  alt="Google"
                  class="social-icon"
                />
                <span>Google</span>
              </a>
              <a href="#" class="social-btn facebook">
                <img
                  src="https://demo.acellemail.com/images/icons/facebook-logo.svg"
                  alt="Facebook"
                  class="social-icon"
                />
                <span>Facebook</span>
              </a>
            </div> --}}
          </form>
        </div>

        <!-- Signup Form -->
        <div id="signup" class="auth-form">
          <form id="signupForm">
            <div class="form-group floating-input">
              <input
                type="text"
                id="signupName"
                name="name"
                placeholder=" "
                required
              />
              <label for="signupName">Full Name</label>
              <i class="fas fa-user input-icon"></i>
            </div>

            <div class="form-group floating-input">
              <input
                type="email"
                id="signupEmail"
                name="email"
                placeholder=" "
                required
              />
              <label for="signupEmail">Email</label>
              <i class="fas fa-envelope input-icon"></i>
            </div>

            <div class="form-group floating-input">
              <input
                type="tel"
                id="signupPhone"
                name="phone"
                placeholder=" "
              />
              <label for="signupPhone">Phone</label>
              <i class="fas fa-phone input-icon"></i>
            </div>

            <div class="form-group floating-input">
              <input
                type="password"
                id="signupPassword"
                name="password"
                placeholder=" "
                required
              />
              <label for="signupPassword">Password</label>
              <i class="fas fa-lock input-icon"></i>
              <span class="toggle-password">
                <i class="fas fa-eye"></i>
              </span>
            </div>

            <div class="form-group floating-input">
              <input
                type="password"
                id="signupPasswordConfirmation"
                name="password_confirmation"
                placeholder=" "
                required
              />
              <label for="signupPasswordConfirmation"
                >Confirm Password</label
              >
              <i class="fas fa-lock input-icon"></i>
              <span class="toggle-password">
                <i class="fas fa-eye"></i>
              </span>
            </div>

            <button type="submit" class="auth-btn success">
              <span class="btn-text">Create Account</span>
              <i class="fas fa-user-plus btn-icon"></i>
            </button>

            {{-- <div class="auth-divider">
              <span>or sign up with</span>
            </div> --}}

            {{-- <div class="social-auth">
              <a href="#" class="social-btn google">
                <img
                  src="https://demo.acellemail.com/images/google-login.svg"
                  alt="Google"
                  class="social-icon"
                />
                <span>Google</span>
              </a>
              <a href="#" class="social-btn facebook">
                <img
                  src="https://demo.acellemail.com/images/icons/facebook-logo.svg"
                  alt="Facebook"
                  class="social-icon"
                />
                <span>Facebook</span>
              </a>
            </div> --}}
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

  <!-- Full Page Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Tab switching
      document.querySelectorAll('.auth-tab').forEach((tab) => {
        tab.addEventListener('click', function () {
          document.querySelectorAll('.auth-tab').forEach((t) =>
            t.classList.remove('active')
          );
          document
            .querySelectorAll('.auth-form')
            .forEach((f) => f.classList.remove('active'));
          this.classList.add('active');
          document.getElementById(this.dataset.target).classList.add('active');
        });
      });

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

      // LOGIN form submission
      document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type=submit]');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing in...';
        btn.disabled = true;

        try {
          const res = await fetch('/api/login', {
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
            sessionStorage.setItem('token', data.access_token);
            Swal.fire({
              icon: 'success',
              title: `Welcome ${data.user.name}!`,
              text: data.message,
              timer: 1500,
              showConfirmButton: false
            }).then(() => location.href = '/user/dashboard');
          } else {
            Swal.fire({ icon: 'error', title: 'Login Failed', text: data.message });
          }
        } catch {
          btn.innerHTML = orig;
          btn.disabled = false;
          Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Please try again later.' });
        }
      });

      // SIGNUP form submission
      document.getElementById('signupForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type=submit]');
        const orig = btn.innerHTML;
        const pwd = e.target.password;
        const cf = e.target.password_confirmation;

        if (pwd.value !== cf.value) {
          return Swal.fire({ icon: 'warning', title: 'Passwords do not match' });
        }
        if (pwd.value.length < 6) {
          return Swal.fire({ icon: 'warning', title: 'Use at least 6 characters' });
        }

        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        btn.disabled = true;

        try {
          const res = await fetch('/api/register', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              name: e.target.name.value.trim(),
              email: e.target.email.value.trim(),
              phone: e.target.phone.value.trim() || null,
              password: pwd.value,
              password_confirmation: cf.value
            })
          });
          const body = await res.json();
          btn.innerHTML = orig; btn.disabled = false;

          if (res.status === 201 && body.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Account Created', text: body.message })
              .then(() => {
                e.target.reset();
                document.querySelector('[data-target="login"]').click();
              });
          } else {
            let msg = body.message || 'Registration failed';
            if (body.errors) msg = Object.values(body.errors).flat().join('<br>');
            Swal.fire({ icon: 'error', title: 'Registration Failed', html: msg });
          }
        } catch {
          btn.innerHTML = orig; btn.disabled = false;
          Swal.fire({ icon: 'error', title: 'Connection Error' });
        }
      });
    });
  </script>
</body>

</html>
