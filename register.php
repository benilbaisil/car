<?php
session_start();
require_once __DIR__ . '/config.php';

class RegisterPage {
    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->process();
            return;
        }
        $this->render();
    }

    private function process(): void {
        $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
        $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
        $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $_SESSION['error'] = 'Please fill all fields correctly (password 6+ characters).';
            header('Location: register.php');
            exit;
        }

        try {
            $pdo = Database::getConnection();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $_SESSION['error'] = 'Email is already registered. Please login.';
                header('Location: register.php');
                exit;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, $hash]);

            $_SESSION['user'] = [ 'id' => (int)$pdo->lastInsertId(), 'name' => $name, 'email' => $email ];
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Unexpected error. Please try again.';
            header('Location: register.php');
            exit;
        }
    }

    private function render(): void {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Register - Car Showroom</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-900">
            <nav class="w-full bg-black/95 border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <a href="index.php" class="text-white font-bold">Elite Motors</a>
                    <a href="login.php" class="text-white hover:text-red-600">Login</a>
                </div>
            </nav>
            <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4">
                <div class="w-full max-w-md bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
                    <h1 class="text-2xl font-bold text-white mb-6">Create an Account</h1>
                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="mb-4 bg-red-600/20 text-red-300 border border-red-600/40 px-4 py-2 rounded">
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="register.php" class="space-y-4" id="registerForm" novalidate>
                        <div>
                            <label class="block text-gray-300 mb-1">Name</label>
                            <input type="text" name="name" id="name" required class="w-full px-4 py-2 rounded-lg bg-black/40 border border-white/10 text-white focus:outline-none focus:border-red-600" />
                            <p class="mt-1 text-sm text-red-400 hidden" id="nameError">Please enter your name.</p>
                        </div>
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
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-semibold">Create Account</button>
                    </form>
                    <p class="text-gray-400 mt-4 text-sm">Already have an account? <a href="login.php" class="text-red-500 hover:text-red-400">Login</a></p>
                </div>
            </div>
            <script>
                (function(){
                    const form = document.getElementById('registerForm');
                    const nameField = document.getElementById('name');
                    const email = document.getElementById('email');
                    const password = document.getElementById('password');
                    const nameError = document.getElementById('nameError');
                    const emailError = document.getElementById('emailError');
                    const passwordError = document.getElementById('passwordError');

                    function validateName(){
                        const valid = nameField.value.trim() !== '';
                        nameError.classList.toggle('hidden', valid);
                        return valid;
                    }
                    function validateEmail(){
                        const value = email.value.trim();
                        const valid = value !== '' && /[^@\s]+@[^@\s]+\.[^@\s]+/.test(value);
                        emailError.classList.toggle('hidden', valid);
                        return valid;
                    }
                    function validatePassword(){
                        const valid = password.value.length >= 6;
                        passwordError.classList.toggle('hidden', valid);
                        return valid;
                    }

                    nameField.addEventListener('input', validateName);
                    email.addEventListener('input', validateEmail);
                    password.addEventListener('input', validatePassword);
                    form.addEventListener('submit', function(e){
                        const ok = validateName() && validateEmail() && validatePassword();
                        if (!ok) e.preventDefault();
                    });
                })();
            </script>
        </body>
        </html>
        <?php
    }
}

(new RegisterPage())->handle();

?>


