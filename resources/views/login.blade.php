<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Attendance Enhanced Monitoring - Tagoloan Community College</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: #8B0000;
            color: white;
            text-align: center;
            padding: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #FFD700;
            font-size: 2.5rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .header p {
            color: #FFD700;
            font-size: 1.2rem;
            font-weight: 300;
        }

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .content-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            max-width: 1000px;
            width: 100%;
            min-height: 600px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-section {
            flex: 1;
            background-color: #f8f6f0;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-title h2 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .login-title p {
            font-size: 1.1rem;
            color: #000;
        }

        .form-group {
            margin-bottom: 37px;
            position: relative;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 5px;
            transition: border-color 0.3s ease;
        }

        .input-wrapper:focus-within {
            border-color: #8B0000;
        }

        .input-icon {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #666;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .form-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 12px 0;
            font-size: 1rem;
            background: transparent;
        }

        .form-input::placeholder {
            color: #999;
        }

        .login-btn {
            width: 100%;
            background-color: #8B0000;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .login-btn:hover {
            background-color: #660000;
        }

        .login-footer {
            margin-top: 40px;
            text-align: center;
            color: #000;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .logo-section {
            flex: 1;
            background-color: #8B0000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px;
        }

        .logo-container {
            text-align: center;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .logo-image {
            width: 400px;
            height: 400px;
            object-fit: contain;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
            border-radius: 50%;
            transition: transform 0.3s ease, filter 0.3s ease;
        }

        .logo-text {
            color: #FFD700;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 8px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .motto {
            color: #FFD700;
            font-style: italic;
            font-size: 0.9rem;
            margin-top: 5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        @media (max-width: 768px) {
            .content-box {
                flex-direction: column;
                max-width: 500px;
            }

            .login-section {
                padding: 40px 30px;
            }

            .logo-section {
                padding: 30px 20px;
            }

            .logo-image {
                width: 450px;
                height: 450px;
                margin-bottom: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }
        }

        .login-hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .about-section {
            position: relative;
            background: linear-gradient(rgba(139, 0, 0, 0.35), rgba(139, 0, 0, 0.85)), url("{{ asset('images/TCC Image.png') }}") center/cover no-repeat;
            min-height: 100vh;
            padding: 80px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .about-container {
            max-width: 1000px;
            width: 100%;
        }

        .about-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 18px;
            color: #ffffff;
        }

        .about-box {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 12px;
            padding: 22px 20px;
            max-height: 220px;
            overflow-y: auto;
            line-height: 1.65;
        }

        .about-text {
            color: #f5f5f5;
            font-size: 1rem;
        }

        .about-box::-webkit-scrollbar {
            width: 8px;
        }

        .about-box::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.35);
            border-radius: 10px;
        }

        .about-box::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Error message styles */
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 4px rgba(198, 40, 40, 0.1);
        }


        /* Field validation error styles */
        .input-wrapper.error {
            border-color: #c62828;
            box-shadow: 0 0 0 2px rgba(198, 40, 40, 0.1);
        }

        .field-error {
            color: #c62828;
            font-size: 0.8rem;
            margin-top: 5px;
            margin-left: 5px;
        }

        /* Scroll Animation Styles */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-on-scroll.animate-in {
            opacity: 1;
            transform: translateY(0);
        }

        .animate-slide-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-slide-left.animate-in {
            opacity: 1;
            transform: translateX(0);
        }

        .animate-slide-right {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-slide-right.animate-in {
            opacity: 1;
            transform: translateX(0);
        }

        .animate-scale {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-scale.animate-in {
            opacity: 1;
            transform: scale(1);
        }

        .animate-fade-in {
            opacity: 0;
            transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-fade-in.animate-in {
            opacity: 1;
        }

        /* Enhanced hover effects */
        .content-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }


        .login-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            background-color: #660000;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 0, 0, 0.3);
        }
    </style>
</head>

<body>
    <section class="login-hero">
        <div class="header animate-fade-in">
            <h1>TAGOLOAN COMMUNITY COLLEGE</h1>
            <p>Modern Attendance Enhanced Monitoring</p>
        </div>

        <div class="main-container">
            <div class="content-box animate-scale">
                <div class="login-section animate-slide-left">
                    <div class="login-title animate-on-scroll">
                        <h2>LOGIN latest version - 10</h2>
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
