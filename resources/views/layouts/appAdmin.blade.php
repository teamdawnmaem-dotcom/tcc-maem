<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tagoloan Community College')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin/appAdmin.css') }}">
        @yield('styles')
</head>

<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="sidebar">
        <div class="logo-container">
            <img src="{{ asset('images/tcc-logo.png') }}" alt="TCC Logo" class="sidebar-logo">
        </div>
        <div class="nav-menu">
            <div class="nav-item @yield('dashboard-active')" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 10.5L11 4l8 6.5V18a1 1 0 0 1-1 1h-4v-4H8v4H4a1 1 0 0 1-1-1V10.5Z" stroke="#fff"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span> Dashboard
            </div>

            <div class="nav-item @yield('checker-account-active')"
                onclick="window.location.href='{{ route('admin.user.account.management') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 7a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm8 10v-1a4 4 0 0 0-8 0v1" stroke="#fff"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span> User Accounts
            </div>

            <div class="nav-item has-dropdown @yield('monitoring-active') @if (trim($__env->yieldContent('monitoring-active')) == 'active' ||
                    trim($__env->yieldContent('live-camera-active')) == 'active' ||
                    trim($__env->yieldContent('recognition-logs-active')) == 'active') open @endif"
                onclick="toggleDropdown(this, 'monitoring')">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="7" width="16" height="10" rx="2" stroke="#fff"
                            stroke-width="2" />
                        <circle cx="11" cy="12" r="3" stroke="#fff" stroke-width="2" />
                    </svg>
                </span> Monitoring
            </div>
            <div class="sub-nav" id="monitoring-subnav" @if (trim($__env->yieldContent('monitoring-active')) == 'active' ||
                    trim($__env->yieldContent('live-camera-active')) == 'active' ||
                    trim($__env->yieldContent('recognition-logs-active')) == 'active') style="display:flex;" @endif>
                <!-- Cameras - Removed due to missing admin blade file -->
                <!-- Rooms - Removed due to missing admin blade file -->
                <div class="sub-nav-item @yield('live-camera-active')"
                    onclick="window.location.href='{{ route('admin.live.camera.feed') }}'">Live Camera Feed</div>
                <div class="sub-nav-item @yield('recognition-logs-active')"
                    onclick="window.location.href='{{ route('admin.recognition.logs') }}'">Recognition Logs</div>
            </div>

            <div class="nav-item @yield('reports-active')"
                onclick="window.location.href='{{ route('admin.attendance.records') }}'"> <span class="nav-icon"
                    style="display:inline-flex;align-items:center;justify-content:center;"> <svg width="22"
                        height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="5" width="16" height="14" rx="2" stroke="#fff"
                            stroke-width="2" />
                        <path d="M7 9h8M7 13h8M7 17h8" stroke="#fff" stroke-width="2" />
                    </svg> </span> Reports </div>

            <div class="nav-item @yield('teaching-load-active')"
                onclick="window.location.href='{{ url('/admin/teaching-load-management') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="4" width="16" height="14" rx="2" stroke="#fff" stroke-width="2" />
                        <path d="M7 8h10M7 12h10M7 16h6" stroke="#fff" stroke-width="2" />
                    </svg>
                </span>
                Semester Management
            </div>

            <div class="nav-item @yield('archived-attendance-active')"
                onclick="window.location.href='{{ route('admin.attendance.records.archived') }}'">
                <span class="nav-icon" style="display:inline-flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="5" width="16" height="14" rx="2" stroke="#fff" stroke-width="2" />
                        <path d="M7 9h8M7 13h8M7 17h8" stroke="#fff" stroke-width="2" />
                    </svg>
                </span>
                Archived Records
            </div>

        </div>
    </div>
    <div class="header">
        <!-- Mobile Menu Toggle Button (only visible on mobile) -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12h18M3 6h18M3 18h18" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        
        <div style="display: flex; align-items: center; flex: 1;">
            <div class="header-title-block">
                <span class="header-title">TAGOLOAN COMMUNITY COLLEGE</span>
                <span class="header-address">M.H del Pilar St. Baluarte, Tagoloan, Misamis Oriental</span>
            </div>
        </div>
        <div class="header-profile">
            <div class="profile-btn-container">
                <button class="profile-btn">
                    <span class="profile-icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="11" stroke="white" stroke-width="2"
                                fill="none" />
                            <circle cx="12" cy="8" r="3" stroke="white" stroke-width="2"
                                fill="none" />
                            <path d="M5 19c0-3.866 3.134-7 7-7s7 3.134 7 7" stroke="white" stroke-width="2"
                                fill="none" />
                        </svg>
                    </span>
                    <span class="profile-text">
                        <span class="profile-title">{{ auth()->user()->user_fname }}
                            {{ auth()->user()->user_lname }}</span>
                    </span>
                    <span class="profile-chevron">&#9662;</span>
                </button>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-item" onclick="openModal('accountSettingsModal')">Account Settings
                    </div>
                    <a href="#" class="profile-dropdown-item logout"
                        onclick="openModal('logoutModal')">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <div class="main-content">
        @yield('content')
    </div>

    <!-- Global Loader -->
    <div id="globalLoader" class="loader-overlay" aria-hidden="true">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading...</div>
    </div>

    <!-- Account Settings Modal -->
    <div id="accountSettingsModal" class="modal-overlay profile-modal" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">Update Profile</div>

            <form id="accountSettingsForm" class="modal-form" method="POST"
                action="{{ route('admin.account.update') }}">
                @csrf
                @method('PUT')

                <!-- Feedback area -->
                <div id="accountFeedback" class="feedback-message"></div>

                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="form-section-title">Personal Information</div>

                    <!-- Account Role -->
                    <div class="modal-form-group">
                        <label for="accountRole">Account Role</label>
                        <input name="user_role" type="text" id="accountRole"
                            value="{{ auth()->user()->user_role }}" readonly>
                    </div>

                    <!-- Name Fields -->
                    <div class="form-row">
                        <div class="modal-form-group">
                            <label for="fname">First Name</label>
                            <input name="user_fname" type="text" id="fname"
                                value="{{ auth()->user()->user_fname }}" placeholder="Enter first name" required>
                            <div class="field-error" id="fname-error"></div>
                        </div>

                        <div class="modal-form-group">
                            <label for="lname">Last Name</label>
                            <input name="user_lname" type="text" id="lname"
                                value="{{ auth()->user()->user_lname }}" placeholder="Enter last name" required>
                            <div class="field-error" id="lname-error"></div>
                        </div>
                    </div>

                    <!-- Username -->
                    <div class="modal-form-group">
                        <label for="username">Username</label>
                        <input name="username" type="text" id="username" value="{{ auth()->user()->username }}"
                            placeholder="Enter username" required>
                        <div class="field-error" id="username-error"></div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="form-section">
                    <div class="form-section-title">Security Settings</div>

                    <!-- Old Password -->
                    <div class="modal-form-group">
                        <label for="oldPassword">Current Password</label>
                        <input name="current_password" type="password" id="oldPassword"
                            placeholder="Enter current password">
                        <div class="field-error" id="current_password-error"></div>
                    </div>

                    <!-- Password Fields -->
                    <div class="form-row">
                        <div class="modal-form-group">
                            <label for="newPassword">New Password</label>
                            <input name="new_password" type="password" id="newPassword"
                                placeholder="Enter new password">
                            <div class="field-error" id="new_password-error"></div>
                        </div>

                        <div class="modal-form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <input name="new_password_confirmation" type="password" id="confirmPassword"
                                placeholder="Confirm new password">
                            <div class="field-error" id="new_password_confirmation-error"></div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="modal-buttons">
                    <button type="submit" class="modal-btn update">Update Profile</button>
                    <button type="button" class="modal-btn cancel"
                        onclick="closeModal('accountSettingsModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>



    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay logout-modal" style="display:none;">
        <div class="modal-box">
            <div class="modal-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16,17 21,12 16,7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
            </div>
            <div class="modal-header">LOGOUT</div>
            <div class="modal-content">
                Are you sure you want to logout from your account?<br>

            </div>
            <div class="modal-buttons">
                <button class="modal-btn logout" onclick="logout()">Logout</button>
                <button class="modal-btn cancel" onclick="closeModal('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown(element, subnavId) {
            // Close all other dropdowns
            const allDropdowns = document.querySelectorAll('.nav-item.has-dropdown');
            allDropdowns.forEach(dropdown => {
                if (dropdown !== element) {
                    dropdown.classList.remove('open');
                    const subnav = dropdown.nextElementSibling;
                    if (subnav && subnav.classList.contains('sub-nav')) {
                        subnav.style.display = 'none';
                    }
                }
            });

            // Toggle current dropdown
            element.classList.toggle('open');
            const subnav = document.getElementById(subnavId + '-subnav');
            if (subnav) {
                subnav.style.display = subnav.style.display === 'flex' ? 'none' : 'flex';
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function logout() {
            // Create and submit logout form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('logout') }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }

        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Show/hide mobile menu button based on screen size
            function checkMobileMenu() {
                if (window.innerWidth <= 430) {
                    mobileMenuToggle.style.display = 'flex';
                } else {
                    mobileMenuToggle.style.display = 'none';
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                }
            }
            
            // Toggle sidebar
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('mobile-open');
                    sidebarOverlay.classList.toggle('active');
                });
            }
            
            // Close sidebar when overlay is clicked
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 430 && sidebar.classList.contains('mobile-open')) {
                    if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                        sidebar.classList.remove('mobile-open');
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
            
            // Check on load and resize
            checkMobileMenu();
            window.addEventListener('resize', checkMobileMenu);
            
            // Close sidebar when nav item is clicked on mobile (but not dropdown toggles)
            if (window.innerWidth <= 430) {
                const navItems = document.querySelectorAll('.nav-item, .sub-nav-item');
                navItems.forEach(item => {
                    item.addEventListener('click', function(e) {
                        // Don't close sidebar if clicking a dropdown toggle
                        if (item.classList.contains('has-dropdown')) {
                            return;
                        }
                        
                        // Only close sidebar when clicking actual navigation links
                        if (window.innerWidth <= 430) {
                            setTimeout(() => {
                                sidebar.classList.remove('mobile-open');
                                sidebarOverlay.classList.remove('active');
                            }, 100);
                        }
                    });
                });
            }
        });
        
        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Hook global loader to form submits and navigation
            const loader = document.getElementById('globalLoader');
            let suppressLoader = false;

            function showLoader() {
                if (loader && !suppressLoader && !window.suppressLoader) loader.style.display = 'flex';
            }

            function hideLoader() {
                if (loader) loader.style.display = 'none';
            }
            // Show only when form is valid and not prevented
            document.addEventListener('submit', function(e) {
                const form = e.target;
                const isValid = !form || typeof form.checkValidity !== 'function' ? true : form
                    .checkValidity();
                // Defer to allow preventDefault in handlers
                setTimeout(function() {
                    if (isValid && !e.defaultPrevented) showLoader();
                }, 0);
            }, true);
            // Do not show loader for general navigations; keep only for form submits
            window.addEventListener('beforeunload', function() {});

            const profileBtn = document.querySelector('.profile-btn');
            const profileDropdown = document.getElementById('profileDropdown');

            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!profileBtn.contains(e.target)) {
                    profileDropdown.classList.remove('show');
                }
            });
        });



        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        });








        // Enhanced form validation and submission
        document.getElementById('accountSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            const feedback = document.getElementById('accountFeedback');

            // Clear previous errors and feedback
            clearAllErrors();
            hideFeedback();

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;

            fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async res => {
                    const data = await res.json();

                    if (!res.ok) {
                        if (data.errors) {
                            displayFieldErrors(data.errors);
                            showFeedback('Please correct the errors below.', 'error');
                        } else {
                            showFeedback(data.message || 'An error occurred. Please try again.', 'error');
                        }
                        throw new Error('Validation failed');
                    }
                    return data;
                })
                .then(data => {
                    showFeedback(data.success || 'Profile updated successfully!', 'success');

                    // Auto-close modal after 2 seconds
                    setTimeout(() => {
                        closeModal('accountSettingsModal');
                        location.reload();
                    }, 2000);
                })
                .catch(err => {
                    console.error('Error:', err);
                    if (!feedback.classList.contains('error')) {
                        showFeedback('An unexpected error occurred. Please try again.', 'error');
                    }
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Helper functions for form validation
        function clearAllErrors() {
            // Clear field errors
            document.querySelectorAll('.field-error').forEach(error => {
                error.textContent = '';
                error.style.display = 'none';
            });

            // Remove error classes
            document.querySelectorAll('.modal-form-group').forEach(group => {
                group.classList.remove('has-error');
            });
        }

        function displayFieldErrors(errors) {
            Object.keys(errors).forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                const errorElement = document.getElementById(`${fieldName}-error`);
                const formGroup = field?.closest('.modal-form-group');

                if (field && errorElement && formGroup) {
                    errorElement.textContent = errors[fieldName][0];
                    errorElement.style.display = 'block';
                    formGroup.classList.add('has-error');
                }
            });
        }

        function showFeedback(message, type) {
            const feedback = document.getElementById('accountFeedback');
            feedback.textContent = message;
            feedback.className = `feedback-message ${type}`;
            feedback.style.display = 'block';
        }

        function hideFeedback() {
            const feedback = document.getElementById('accountFeedback');
            feedback.style.display = 'none';
            feedback.className = 'feedback-message';
        }

        // Real-time validation
        document.querySelectorAll('#accountSettingsForm input').forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });

        function validateField(field) {
            const formGroup = field.closest('.modal-form-group');
            const errorElement = formGroup.querySelector('.field-error');

            // Clear previous error state
            formGroup.classList.remove('has-error');
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.textContent = '';
            }

            // Basic validation
            if (field.hasAttribute('required') && !field.value.trim()) {
                showFieldError(field, 'This field is required.');
                return false;
            }

            // Email validation for username
            if (field.name === 'username' && field.value) {
                const usernameRegex = /^[a-zA-Z0-9_]{3,}$/;
                if (!usernameRegex.test(field.value)) {
                    showFieldError(field,
                        'Username must be at least 3 characters and contain only letters, numbers, and underscores.');
                    return false;
                }
            }

            // Password confirmation
            if (field.name === 'new_password_confirmation') {
                const newPassword = document.getElementById('newPassword');
                if (newPassword.value && field.value !== newPassword.value) {
                    showFieldError(field, 'Passwords do not match.');
                    return false;
                }
            }

            return true;
        }

        function showFieldError(field, message) {
            const formGroup = field.closest('.modal-form-group');
            const errorElement = formGroup.querySelector('.field-error');

            formGroup.classList.add('has-error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        }
    </script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global SweetAlert2 helpers and confirm handlers -->
    <script>
        (function() {
            window.SwalUtils = {
                error: function(title, text) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: title || 'Error',
                            text: text || '',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                },
                info: function(title, text) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'info',
                            title: title || 'Info',
                            text: text || '',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                },
                success: function(title, text) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: title || 'Success',
                            text: text || '',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                },
                confirmDelete: async function(opts) {
                    if (!window.Swal) return {
                        isConfirmed: true
                    };
                    return await Swal.fire({
                        icon: 'warning',
                        title: (opts && opts.title) || 'Are you sure?',
                        text: (opts && opts.text) || 'This action cannot be undone.',
                        showCancelButton: true,
                        confirmButtonText: (opts && opts.confirmText) || 'Delete',
                        cancelButtonText: (opts && opts.cancelText) || 'Cancel',
                        confirmButtonColor: '#ff3636',
                        cancelButtonColor: '#800000'
                    });
                },
                incompleteFields: function() {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Incomplete fields',
                            text: 'Please fill out Subject Code, Description, and Department.',
                            confirmButtonColor: '#8B0000'
                        });
                    }
                }
            };
            document.addEventListener('submit', async function(e) {
                const form = e.target;
                if (form && form.dataset && form.dataset.swalConfirm === 'delete') {
                    e.preventDefault();
                    const res = await window.SwalUtils.confirmDelete({});
                    if (res && res.isConfirmed) {
                        form.submit();
                    }
                }
            }, true);
        })();
    </script>

    @yield('scripts')
</body>

</html>
