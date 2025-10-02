<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk Plus - Smart Care for Every Doctor</title>
    <meta name="description" content="Professional SaaS platform for doctors to manage appointments, patients, medical history, and reports. Perfect for Allopathy, Homeopathy, and Ayurvedic practitioners.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .hero-pattern {
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234f46e5' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="antialiased">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="text-2xl font-bold">
                        <span class="text-blue-600">HealthDesk</span><span class="text-green-500">Plus</span>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition">Features</a>
                    <a href="#benefits" class="text-gray-700 hover:text-blue-600 transition">Benefits</a>
                    <a href="#api-docs" class="text-gray-700 hover:text-blue-600 transition">API Docs</a>
                    <a href="#pricing" class="text-gray-700 hover:text-blue-600 transition">Pricing</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/api/auth/login" class="text-gray-700 hover:text-blue-600 transition font-medium">Login</a>
                    <a href="/api/auth/register" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow-md">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-pattern py-20 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <div class="inline-block mb-4">
                        <span class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold">
                            Healthcare Management SaaS
                        </span>
                    </div>
                    <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                        HealthDesk<span class="text-green-500">Plus</span>
                    </h1>
                    <p class="text-2xl lg:text-3xl text-gray-600 mb-8 font-medium">
                        Smart Care for Every Doctor
                    </p>
                    <p class="text-lg text-gray-600 mb-10 leading-relaxed">
                        Streamline your practice with our comprehensive platform designed for Allopathy, Homeopathy, and Ayurvedic practitioners. Manage appointments, patient records, medical history, and reports with ease.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="/api/auth/register" class="bg-blue-600 text-white px-8 py-4 rounded-lg hover:bg-blue-700 transition shadow-lg text-lg font-semibold">
                            Start Free Trial
                        </a>
                        <a href="#api-docs" class="bg-white text-blue-600 px-8 py-4 rounded-lg hover:bg-gray-50 transition shadow-lg border-2 border-blue-600 text-lg font-semibold">
                            API Documentation
                        </a>
                    </div>
                    <div class="mt-10 flex items-center gap-8 justify-center lg:justify-start text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>No credit card required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>14-day free trial</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-green-400 rounded-3xl transform rotate-3 opacity-20"></div>
                        <div class="relative bg-white p-8 rounded-3xl shadow-2xl">
                            <div class="w-full max-w-md mx-auto text-center">
                                <div class="text-6xl mb-4">🏥</div>
                                <h3 class="text-2xl font-bold text-gray-800 mb-2">HealthDesk Plus</h3>
                                <p class="text-gray-600">Smart Healthcare Management</p>
                            </div>
                            <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                                <div class="bg-blue-50 p-4 rounded-xl">
                                    <div class="text-3xl font-bold text-blue-600">1000+</div>
                                    <div class="text-sm text-gray-600 mt-1">Doctors</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-xl">
                                    <div class="text-3xl font-bold text-green-600">50K+</div>
                                    <div class="text-sm text-gray-600 mt-1">Patients</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-xl">
                                    <div class="text-3xl font-bold text-purple-600">99.9%</div>
                                    <div class="text-sm text-gray-600 mt-1">Uptime</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- API Documentation Section -->
    <section id="api-docs" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Developer-Friendly APIs</h2>
                <p class="text-xl text-gray-600">Comprehensive REST APIs with interactive documentation</p>
            </div>
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-code text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">RESTful APIs</h3>
                                <p class="text-gray-600">Well-documented REST APIs following industry standards for easy integration.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-green-100 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Secure Authentication</h3>
                                <p class="text-gray-600">JWT-based authentication with role-based access control for maximum security.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-purple-100 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-book text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Interactive Documentation</h3>
                                <p class="text-gray-600">Swagger/OpenAPI documentation with live testing capabilities.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-900 rounded-2xl p-8 text-white">
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="ml-4 text-gray-400 text-sm">API Example</span>
                        </div>
                        <pre class="text-sm text-green-400 overflow-x-auto"><code>POST /api/auth/register
{
  "name": "Dr. John Doe",
  "email": "john@example.com",
  "password": "password123",
  "practice_name": "City Medical",
  "practice_type": "allopathy",
  "first_name": "John",
  "last_name": "Doe",
  "qualification": "MBBS, MD",
  "phone": "+91-9876543210"
}</code></pre>
                    </div>
                    <a href="/api/documentation" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        View API Documentation
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Powerful Features for Modern Practices</h2>
                <p class="text-xl text-gray-600">Everything you need to run your practice efficiently</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                    <div class="bg-blue-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-calendar-check text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Appointment Management</h3>
                    <p class="text-gray-600">Schedule, reschedule, and manage appointments effortlessly with our intuitive calendar system and automated reminders.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                    <div class="bg-green-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-user-md text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Patient Records</h3>
                    <p class="text-gray-600">Maintain comprehensive digital patient records with complete medical history, treatments, and progress tracking.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                    <div class="bg-purple-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-file-medical text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Medical Reports</h3>
                    <p class="text-gray-600">Generate, store, and share medical reports securely. Access patient reports anytime, anywhere.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                    <div class="bg-yellow-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-prescription text-3xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Prescription Management</h3>
                    <p class="text-gray-600">Create digital prescriptions with customizable templates for Allopathy, Homeopathy, and Ayurvedic medicines.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                    <div class="bg-red-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-chart-line text-3xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Analytics & Insights</h3>
                    <p class="text-gray-600">Track practice performance with detailed analytics, reports, and insights to make data-driven decisions.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                    <div class="bg-indigo-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-lock text-3xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Secure & Compliant</h3>
                    <p class="text-gray-600">Bank-level security with encrypted data storage. Fully compliant with healthcare regulations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="py-20 bg-gradient-to-br from-blue-50 to-green-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Perfect for Every Practice</h2>
                <p class="text-xl text-gray-600">Trusted by healthcare professionals across specialties</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-lg text-center">
                    <div class="bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-pills text-4xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Allopathy</h3>
                    <p class="text-gray-600 mb-4">Comprehensive tools for modern medical practitioners with advanced diagnostic features and prescription management.</p>
                    <ul class="text-left text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Lab integration</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Drug database</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Imaging reports</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-lg text-center">
                    <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-leaf text-4xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Homeopathy</h3>
                    <p class="text-gray-600 mb-4">Specialized features for homeopathic practitioners with remedy databases and case management tools.</p>
                    <ul class="text-left text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Remedy repertory</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Case analysis</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Follow-up tracking</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-lg text-center">
                    <div class="bg-yellow-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-spa text-4xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Ayurvedic</h3>
                    <p class="text-gray-600 mb-4">Traditional wisdom meets modern technology with specialized Ayurvedic practice management features.</p>
                    <ul class="text-left text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Prakriti analysis</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Herbal database</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Panchakarma records</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Ready to Transform Your Practice?</h2>
            <p class="text-xl text-white mb-10 opacity-90">Join thousands of doctors who trust HealthDesk Plus for their daily practice management</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/api/auth/register" class="bg-white text-blue-600 px-8 py-4 rounded-lg hover:bg-gray-100 transition shadow-lg text-lg font-semibold">
                    Start Free Trial
                </a>
                <a href="/api/documentation" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg hover:bg-white hover:text-blue-600 transition shadow-lg text-lg font-semibold">
                    View API Docs
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-2xl font-bold mb-4">
                        <span class="text-blue-400">HealthDesk</span><span class="text-green-400">Plus</span>
                    </h3>
                    <p class="text-gray-400 mb-4">Smart Care for Every Doctor</p>
                    <div class="flex gap-4">
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-facebook text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-twitter text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-linkedin text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-instagram text-2xl"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Product</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="#api-docs" class="hover:text-white transition">API Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition">Security</a></li>
                        <li><a href="#" class="hover:text-white transition">Roadmap</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                        <li><a href="/api/documentation" class="hover:text-white transition">API Reference</a></li>
                        <li><a href="#" class="hover:text-white transition">Community</a></li>
                        <li><a href="#" class="hover:text-white transition">Status</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400">&copy; 2025 HealthDesk Plus. All rights reserved.</p>
                <div class="flex gap-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white transition">Privacy Policy</a>
                    <a href="#" class="text-gray-400 hover:text-white transition">Terms of Service</a>
                    <a href="#" class="text-gray-400 hover:text-white transition">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
