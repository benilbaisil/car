<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Database configuration
require_once __DIR__ . '/../config.php';

/**
 * User class - represents a user entity
 */
class User {
    private int $id;
    private string $name;
    private string $email;
    private string $createdAt;
    
    public function __construct(int $id, string $name, string $email, string $createdAt) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = $createdAt;
    }
    
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getCreatedAt(): string { return $this->createdAt; }
}

/**
 * UserRepository - handles database operations for users
 */
class UserRepository {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all users
     */
    public function getAllUsers(): array {
        $stmt = $this->pdo->query('SELECT id, name, email, created_at FROM users ORDER BY created_at DESC');
        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = new User(
                (int)$row['id'],
                $row['name'],
                $row['email'],
                $row['created_at']
            );
        }
        return $users;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User {
        $stmt = $this->pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }
        
        return new User(
            (int)$row['id'],
            $row['name'],
            $row['email'],
            $row['created_at']
        );
    }
    
    /**
     * Update user details
     */
    public function updateUser(int $id, string $name, string $email): bool {
        $stmt = $this->pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        return $stmt->execute([$name, $email, $id]);
    }
    
    /**
     * Delete user by ID
     */
    public function deleteUser(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }
    
    /**
     * Get total order count for a user
     */
    public function getUserOrderCount(int $userId): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}

// Initialize repository
$repo = new UserRepository(Database::getConnection());

// Handle actions
$message = '';
$messageType = 'success';

// Handle Delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    try {
        if ($repo->deleteUser($userId)) {
            $message = 'User deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete user';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: User has related orders and cannot be deleted';
        $messageType = 'error';
    }
}

// Handle Edit action
$editUser = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editUser = $repo->getUserById((int)$_GET['id']);
}

// Handle Update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = (int)$_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (!empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($repo->updateUser($userId, $name, $email)) {
            $message = 'User updated successfully';
            $messageType = 'success';
            $editUser = null; // Close edit form
        } else {
            $message = 'Failed to update user';
            $messageType = 'error';
        }
    } else {
        $message = 'Please provide valid name and email';
        $messageType = 'error';
    }
}

// Get all users
$users = $repo->getAllUsers();
$adminName = $_SESSION['admin']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Elite Diecast Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-black/40 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white">Elite Diecast Admin</h1>
                </div>
                
                <div class="hidden md:flex space-x-4">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Dashboard</a>
                    <a href="users.php" class="bg-red-600 text-white px-4 py-2 rounded-lg">Users</a>
                    <a href="products.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Products</a>
                    <a href="orders.php" class="text-gray-300 hover:text-white hover:bg-white/10 px-4 py-2 rounded-lg transition">Orders</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-300 text-sm">Welcome, <?php echo htmlspecialchars($adminName); ?></span>
                    <a href="logout.php" class="bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white px-4 py-2 rounded-lg transition text-sm">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-white mb-2">User Management</h2>
            <p class="text-gray-400">View and manage registered users</p>
        </div>
        
        <!-- Success/Error Message -->
        <?php if ($message): ?>
            <div class="mb-6 px-4 py-3 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-600/20 border border-green-600/50 text-green-300' : 'bg-red-600/20 border border-red-600/50 text-red-300'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Edit User Form (shown when editing) -->
        <?php if ($editUser): ?>
            <div class="mb-6 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                <h3 class="text-xl font-bold text-white mb-4">Edit User</h3>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="user_id" value="<?php echo $editUser->getId(); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-300 mb-2">Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($editUser->getName()); ?>" 
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($editUser->getEmail()); ?>" 
                                class="w-full px-4 py-2 bg-black/30 border border-white/20 rounded-lg text-white focus:outline-none focus:border-red-500" required>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" name="update_user" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                            Save Changes
                        </button>
                        <a href="users.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition inline-block">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Users Table -->
        <div class="bg-white/5 backdrop-blur-sm rounded-xl overflow-hidden border border-white/10">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Orders</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-white/5 transition">
                                    <td class="px-6 py-4 text-white"><?php echo $user->getId(); ?></td>
                                    <td class="px-6 py-4 text-white"><?php echo htmlspecialchars($user->getName()); ?></td>
                                    <td class="px-6 py-4 text-gray-300"><?php echo htmlspecialchars($user->getEmail()); ?></td>
                                    <td class="px-6 py-4 text-gray-300"><?php echo $repo->getUserOrderCount($user->getId()); ?></td>
                                    <td class="px-6 py-4 text-gray-300"><?php echo date('M d, Y', strtotime($user->getCreatedAt())); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <a href="?action=edit&id=<?php echo $user->getId(); ?>" 
                                                class="bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white px-3 py-1 rounded transition text-sm">
                                                Edit
                                            </a>
                                            <a href="?action=delete&id=<?php echo $user->getId(); ?>" 
                                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"
                                                class="bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white px-3 py-1 rounded transition text-sm">
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="mt-6 bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
            <p class="text-gray-300">
                <span class="font-semibold text-white"><?php echo count($users); ?></span> total registered users
            </p>
        </div>
    </main>
</body>
</html>


