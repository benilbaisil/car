<?php
session_start();
require_once __DIR__ . '/config.php';

/**
 * Unified Login Page - Handles both Admin and User authentication
 * 
 * Authentication Flow:
 * 1. Check if credentials match admin account (from admins table)
 * 2. If admin: redirect to admin/dashboard.php
 * 3. If not admin: check if credentials match user account (from users table)
 * 4. If user: redirect to dashboard.php (user dashboard)
 * 5. If neither: show error message
 * 
 * Security Note:
 * - Admin credentials are NOT displayed on the login form
 * - Credentials are stored securely in the database with bcrypt hashing
 * - Admin can login with: admin@gmail.com / Admin@1234 (stored in database)
 */
class LoginPage {
    // Admin default credentials stored in database (not displayed on page)
    private const ADMIN_EMAIL = 'admin@gmail.com';
    
    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->process();
            return;
        }
        $this->render();
    }

    /**
     * Process login attempt
     * Checks admin credentials first, then user credentials
     */
    private function process(): void {
        $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
        $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
        
        // Validate required fields
        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Email and password are required.';
            header('Location: login.php');
            exit;
        }

        try {
            // Step 1: Check if credentials match admin account
            if ($this->authenticateAdmin($email, $password)) {
                return; // Already redirected to admin dashboard
            }
            
            // Step 2: Check if credentials match user account
            if ($this->authenticateUser($email, $password)) {
                return; // Already redirected to user dashboard
            }
            
            // Step 3: Neither admin nor user - show error
            $_SESSION['error'] = 'Invalid email or password. Please try again or register.';
        } catch (PDOException $e) {
            // Database connection or query error
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        } catch (Throwable $e) {
            // Other unexpected errors
            $_SESSION['error'] = 'Unexpected error: ' . $e->getMessage();
        }

        header('Location: login.php');
        exit;
    }
    
    /**
     * Authenticate admin credentials
     * Checks against admins table in database
     * 
     * @param string $email User-provided email
     * @param string $password User-provided password (plain text)
     * @return bool True if admin authenticated successfully
     */
    private function authenticateAdmin(string $email, string $password): bool {
        $pdo = Database::getConnection();
        
        // Query admins table
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        // Verify admin exists and password matches
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Create admin session
            $_SESSION['admin'] = [
                'id' => (int)$admin['id'],
                'name' => (string)$admin['name'],
                'email' => (string)$admin['email']
            ];
            
            // Redirect to admin dashboard
            header('Location: admin/dashboard.php');
            exit;
        }
        
        return false;
    }
    
    /**
     * Authenticate user credentials
     * Checks against users table in database
     * 
     * @param string $email User-provided email
     * @param string $password User-provided password (plain text)
     * @return bool True if user authenticated successfully
     */
    private function authenticateUser(string $email, string $password): bool {
        $pdo = Database::getConnection();
        
        // Query users table
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Verify user exists and password matches
        if ($user && password_verify($password, $user['password_hash'])) {
            // Create user session
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => (string)$user['name'],
                'email' => (string)$user['email']
            ];
            
            // Redirect to user dashboard
            header('Location: dashboard.php');
            exit;
        }
        
        return false;
    }

    private function render(): void {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login - Car Showroom</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-900">
            <nav class="w-full bg-black/95 border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <a href="index.php" class="text-white font-bold">Elite Motors</a>
                    <a href="register.php" class="text-white hover:text-red-600">Register</a>
                </div>
            </nav>
            <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4">
                <div class="w-full max-w-md bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
                    <h1 class="text-2xl font-bold text-white mb-2">Login</h1>
                    <p class="text-gray-400 text-sm mb-6">Access your account or admin panel</p>
                    
                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="mb-4 bg-red-600/20 text-red-300 border border-red-600/40 px-4 py-2 rounded">
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="login.php" class="space-y-4" id="loginForm" novalidate>
                        <div>
                            <label class="block text-gray-300 mb-1">Email</label>
                            <input type="email" name="email" id="email" required class="w-full px-4 py-2 rounded-lg bg-black/40 border border-white/10 text-white focus:outline-none focus:border-red-600" />
                            <p class="mt-1 text-sm text-red-400 hidden" id="emailError">Please enter a valid email.</p>
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-1">Password</label>
                            <input type="password" name="password" id="password" minlength="6" required class="w-full px-4 py-2 rounded-lg bg-black/40 border border-white/10 text-white focus:outline-none focus:border-red-600" />
                            <p class="mt-1 text-sm text-red-400 hidden" id="passwordError">Password must be at least 6 characters.</p>
                        </div>
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-semibold">Login</button>
                    </form>
                    
                    <!-- Admin credentials box removed for security -->
                    <!-- Admin can still login with: admin@gmail.com / Admin@1234 -->
                    <!-- Credentials are stored securely in database and verified during authentication -->
                    
                    <p class="text-gray-400 mt-4 text-sm">Don't have an account? <a href="register.php" class="text-red-500 hover:text-red-400">Register</a></p>
                </div>
            </div>
            <script>
                (function(){
                    const form = document.getElementById('loginForm');
                    const email = document.getElementById('email');
                    const password = document.getElementById('password');
                    const emailError = document.getElementById('emailError');
                    const passwordError = document.getElementById('passwordError');

                    function validateEmailField(){
                        const value = email.value.trim();
                        const valid = value !== '' && /[^@\s]+@[^@\s]+\.[^@\s]+/.test(value);
                        emailError.classList.toggle('hidden', valid);
                        return valid;
                    }
                    function validatePasswordField(){
                        const valid = password.value.length >= 6;
                        passwordError.classList.toggle('hidden', valid);
                        return valid;
                    }
                    email.addEventListener('input', validateEmailField);
                    password.addEventListener('input', validatePasswordField);
                    form.addEventListener('submit', function(e){
                        const ok = validateEmailField() && validatePasswordField();
                        if (!ok) e.preventDefault();
                    });
                })();
            </script>
        </body>
        </html>
        <?php
    }
}

(new LoginPage())->handle();

?>


