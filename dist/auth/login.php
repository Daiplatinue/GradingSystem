<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../server/reg.php'); ?>
    <title>Academic Grading System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(8px);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-5 left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg opacity-0 pointer-events-none transition-all duration-300">
        <span id="toast-message"></span>
    </div>

    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-5xl">
            <!-- Main Container -->
            <div class="glass-effect rounded-3xl p-8 md:p-12">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <!-- Left Side - Branding -->
                    <div class="text-center md:text-left space-y-8">
                        <div>
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mb-6">
                                <i class="fas fa-graduation-cap text-red-600 text-xl"></i>
                            </div>
                            <h1 class="text-4xl font-bold text-gray-900 mb-4">Academic Grading System</h1>
                            <p class="text-gray-600 text-lg">Empowering educators with smart grading solutions</p>
                        </div>

                        <!-- Features Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-white rounded-xl shadow-sm">
                                <i class="fas fa-chart-line text-red-600 mb-2"></i>
                                <h3 class="font-medium text-gray-900">Grade Analytics</h3>
                            </div>
                            <div class="p-4 bg-white rounded-xl shadow-sm">
                                <i class="fas fa-users text-red-600 mb-2"></i>
                                <h3 class="font-medium text-gray-900">Student Management</h3>
                            </div>
                            <div class="p-4 bg-white rounded-xl shadow-sm">
                                <i class="fas fa-file-alt text-red-600 mb-2"></i>
                                <h3 class="font-medium text-gray-900">Report Generation</h3>
                            </div>
                            <div class="p-4 bg-white rounded-xl shadow-sm">
                                <i class="fas fa-clock text-red-600 mb-2"></i>
                                <h3 class="font-medium text-gray-900">Real-time Updates</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Login Form -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
                            <p class="text-gray-500 mt-2">Sign in to continue to your account</p>
                        </div>

                        <form id="loginForm" class="space-y-6" method="POST" action="../server/login.php">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" 
                                        name="email"
                                        class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all duration-200"
                                        placeholder="Enter your email">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                    <input type="password" 
                                        name="password"
                                        class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all duration-200"
                                        placeholder="Enter your password">
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex items-center">
                                    <input type="checkbox" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                                </label>
                                <a href="#" class="text-sm text-red-600 hover:text-red-700 font-medium">Forgot password?</a>
                            </div>

                            <button type="submit" 
                                class="w-full bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 transition-all duration-200 font-medium">
                                Sign In
                            </button>

                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-200"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Or continue with</span>
                                </div>
                            </div>

                            <button type="button" 
                                class="w-full bg-white border border-gray-200 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-50 transition-all duration-200 flex items-center justify-center space-x-2">
                                <img src="https://www.google.com/favicon.ico" alt="Google" class="w-5 h-5">
                                <span>Sign in with Google</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-500 text-sm">
                <p>© 2024 Academic Grading System. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="./toast.js"></script>
</body>
</html>