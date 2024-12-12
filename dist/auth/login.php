<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../server/reg.php'); ?>
    <title>Grading System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            100: '#1E293B',
                            200: '#0F172A'
                        }
                    },
                    animation: {
                        'bounce-slow': 'bounce 3s infinite'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gradient-to-br from-dark-200 via-dark-100 to-dark-200 text-gray-100 min-h-screen flex items-center justify-center p-4">

    <div id="toast" class="fixed bottom-5 left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg opacity-0 pointer-events-none transition-all duration-300">
        <span id="toast-message"></span>
    </div>

    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
    </div>

    <div class="container max-w-6xl mx-auto">
        <div class="flex flex-wrap items-center justify-center -mx-4">
            <div class="w-full lg:w-1/2 px-4 mb-8 lg:mb-0">
                <div class="space-y-8 slide-in">
                    <div class="space-y-4">
                        <h1 class="text-5xl md:text-6xl font-bold bg-gradient-to-r from-blue-500 to-purple-600 bg-clip-text text-transparent">
                            Welcome to
                        </h1>
                        <div class="space-y-2">
                            <h2 class="text-4xl md:text-5xl font-bold text-gray-100 slide-in">Grading</h2>
                            <h2 class="text-4xl md:text-5xl font-bold text-blue-500 slide-in">Management</h2>
                            <h2 class="text-4xl md:text-5xl font-bold text-purple-500 slide-in">System</h2>
                        </div>
                    </div>

                    <p class="text-gray-400 text-xl">
                        Streamline your clinical education experience with our comprehensive management system.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-6 glass-effect rounded-2xl hover:scale-105 transition-all duration-300 cursor-pointer slide-in">
                            <i class="fas fa-user-md text-blue-500 text-3xl mb-4"></i>
                            <h3 class="font-semibold text-lg">Student Management</h3>
                        </div>
                        <div class="p-6 glass-effect rounded-2xl hover:scale-105 transition-all duration-300 cursor-pointer slide-in">
                            <i class="fas fa-calendar-alt text-purple-500 text-3xl mb-4"></i>
                            <h3 class="font-semibold text-lg">Schedule Planning</h3>
                        </div>
                        <div class="p-6 glass-effect rounded-2xl hover:scale-105 transition-all duration-300 cursor-pointer slide-in">
                            <i class="fas fa-chart-line text-green-500 text-3xl mb-4"></i>
                            <h3 class="font-semibold text-lg">Performance Tracking</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/2 px-4">
                <div class="glass-effect rounded-3xl p-8 shadow-2xl slide-in">
                    <div class="flex justify-center mb-8">
                        <div class="bg-dark-200/50 rounded-full p-2">
                            <div class="flex space-x-2">
                                <h1>Login Form</h1>
                            </div>
                        </div>
                    </div>

                    <!-- Login Form -->
                    <form id="loginForm" class="space-y-6" method="POST" action="../server/login.php">
                        <div class="relative group">
                            <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                            <input type="text" class="w-full bg-dark-200/50 rounded-lg px-10 py-4 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" placeholder="Email" name="email">
                        </div>
                        <div class="relative group">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                            <input type="password" class="w-full bg-dark-200/50 rounded-lg px-10 py-4 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" placeholder="Password" name="password">
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" class="form-checkbox rounded bg-dark-200/50 border-gray-600 text-blue-500 focus:ring-blue-500">
                                <span>Remember me</span>
                            </label>
                            <a href="#" class="text-blue-500 hover:text-blue-400 transition-colors">Forgot Password?</a>
                        </div>
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-4 rounded-lg hover:opacity-90 transition-all duration-300 transform hover:scale-[1.02]">
                            Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="./toast.js"></script>

</body>

</html>