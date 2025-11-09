<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Attendance Enhanced Monitoring - Tagoloan Community College</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        :root {
            --tcc-bg-image: url("{{ asset('images/TCC Image.png') }}");
        }
    </style>
</head>

<body>
    <section class="login-hero">
        <div class="header animate-fade-in">
            <h2>TAGOLOAN COMMUNITY COLLEGE</h2>
            <p>Modern Attendance Enhanced Monitoring</p>
        </div>

        <div class="main-container">
            <div class="content-box animate-scale">
                <div class="login-section animate-slide-left">
                    <div class="login-title animate-on-scroll">
                        <h2>LOGIN</h2>
                        <p>Welcome User</p>
                    </div>

                    @if ($errors->has('login_error'))
                        <div class="error-message animate-on-scroll">
                            {{ $errors->first('login_error') }}
                        </div>
                    @endif

               <form action="{{ route('login.post') }}" method="POST" novalidate>
                    @csrf
                    <div class="form-group animate-on-scroll">
                        <div class="input-wrapper @error('username') error @enderror">
                            <div class="input-icon">👤</div>
                            <input type="text" name="username" class="form-input" placeholder="Username" value="{{ old('username') }}" autocomplete="off" pattern="[a-zA-Z0-9_-]+" title="Username can only contain letters, numbers, underscores, and hyphens" oninput="this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '')">
                        </div>
                        @error('username')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group animate-on-scroll">
                        <div class="input-wrapper @error('user_password') error @enderror">
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 11H5C3.89543 11 3 11.8954 3 13V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V13C21 11.8954 20.1046 11 19 11Z" stroke="#666" stroke-width="2" fill="none"/>
                                    <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#666" stroke-width="2" fill="none"/>
                                </svg>
                            </div>
                            <input type="password" name="user_password" class="form-input" placeholder="Password" autocomplete="off">
                        </div>
                        @error('user_password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="login-btn animate-on-scroll">Login</button>
                </form>

                <div class="login-footer animate-on-scroll">
                    <p><strong>Tagoloan Community College</strong></p>
                    <p>M.H del Pilar St. Baluarte, Tagoloan, Misamis Oriental</p>
                </div>
                </div>

                <div class="logo-section animate-slide-right">
                    <div class="logo-container">
                        <img src="{{ asset('images/tcc-logo.png') }}" alt="Tagoloan Community College Logo" class="logo-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section">
        <div class="about-container">
            <h3 class="about-title animate-on-scroll">About Us</h3>
            <div class="about-box animate-on-scroll">
                <p class="about-text">
                            Modern Attendance Enhanced Monitoring (MAEM) is an innovative system developed exclusively for Tagoloan Community College to revolutionize the way faculty attendance and activity are managed. With features like real-time monitoring, and automated reporting, MAEM aims to enhance transparency, accuracy, and efficiency in academic operations. This system empowers administrators with clear insights while simplifying the attendance process for faculty. At its core, MAEM reflects the college's commitment to modernization, accountability, and continuous improvement in helping create a more organized and responsive academic environment.
                </p>
            </div>
        </div>
    </section>

    <script>
        // Scroll Animation Handler - triggers multiple times
        function handleScrollAnimations() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll, .animate-slide-left, .animate-slide-right, .animate-scale, .animate-fade-in');
            
            animatedElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 100;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    // Force reflow to ensure animation retriggers
                    element.style.animation = 'none';
                    element.offsetHeight; // Trigger reflow
                    element.style.animation = null;
                    
                    // Remove and re-add class to retrigger animation
                    element.classList.remove('animate-in');
                    requestAnimationFrame(() => {
                        element.classList.add('animate-in');
                    });
                }
            });
        }

        // Initial load animation for hero section
        function initHeroAnimations() {
            const heroElements = document.querySelectorAll('.animate-fade-in, .animate-scale, .animate-slide-left, .animate-slide-right');
            
            // Stagger the animations for a more polished effect
            heroElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('animate-in');
                }, index * 200);
            });
        }

        // Enhanced scroll handler with better retriggering
        let scrollTimeout;
        let lastScrollY = 0;
        
        function throttledScrollHandler() {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(() => {
                const currentScrollY = window.scrollY;
                
                // Only trigger animations if user has scrolled significantly
                if (Math.abs(currentScrollY - lastScrollY) > 50) {
                    handleScrollAnimations();
                    lastScrollY = currentScrollY;
                }
            }, 10);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize hero animations after a short delay
            setTimeout(initHeroAnimations, 100);
            
            // Handle scroll animations
            window.addEventListener('scroll', throttledScrollHandler);
            
            // Initial check for elements already in view
            handleScrollAnimations();
        });

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add floating animation to logo
            const logoImage = document.querySelector('.logo-image');
            if (logoImage) {
                setInterval(() => {
                    logoImage.style.transform = 'translateY(-5px)';
                    setTimeout(() => {
                        logoImage.style.transform = 'translateY(0)';
                    }, 2000);
                }, 4000);
            }
        });
    </script>
</body>
</html>
