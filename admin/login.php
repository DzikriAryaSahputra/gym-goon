<?php
require_once '../config/database.php';
require_once '../config/auth.php';

if (isAdminLoggedIn()) {
    header("Location: index");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        setcookie("admin_logged_in", "1", time() + 86400, "/");
        header("Location: index");
        exit;
    } else {
        $error = "Username atau Password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Gym Goon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { gold: { 400: '#FFDF73', 500: '#D4AF37', 600: '#AA8C2C', 700: '#8B7322' }, dark: { 900: '#050505', 800: '#111111', 700: '#1a1a1a' } } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #050505; color: #f5f5f5; }
        .glass-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(212, 175, 55, 0.1); }
        .btn-gold { background: linear-gradient(135deg, #FFDF73 0%, #D4AF37 100%); color: #000; transition: all 0.3s ease; }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(212, 175, 55, 0.4); }
        .input-premium { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease; }
        .input-premium:focus { border-color: #D4AF37; box-shadow: 0 0 0 4px rgba(212,175,55,0.1); outline: none; }
    </style>
    <script>
        window.addEventListener("pageshow", function (event) {
            if (event.persisted) { window.location.reload(); }
        });
    </script>
</head>
<body class="bg-black min-h-screen flex text-gray-100">

    <!-- Left Side: Branding (Hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 relative flex-col justify-between p-12 overflow-hidden bg-dark-900 border-r border-gold-500/10">
        <!-- Background decorative elements -->
        <div class="absolute top-0 left-0 w-full h-full opacity-30" style="background-image: radial-gradient(#D4AF37 1px, transparent 1px); background-size: 40px 40px;"></div>
        <div class="absolute -left-32 -top-32 w-96 h-96 bg-gold-500 rounded-full opacity-10 blur-[100px]"></div>
        <div class="absolute -right-32 -bottom-32 w-96 h-96 bg-gold-500 rounded-full opacity-10 blur-[100px]"></div>

        <div class="relative z-10">
            <img src="../assets/img/k.png" alt="Gym Goon" class="w-16 drop-shadow-[0_0_15px_rgba(212,175,55,0.4)]">
        </div>
        
        <div class="relative z-10 max-w-md">
            <span class="px-3 py-1 text-xs font-bold bg-gold-500/20 text-gold-400 rounded-full uppercase tracking-widest border border-gold-500/30 mb-4 inline-block">Management System</span>
            <h1 class="text-5xl font-extrabold text-white leading-tight mb-4">Command<br><span class="text-transparent bg-clip-text bg-gradient-to-r from-gold-400 to-gold-600">Center</span></h1>
            <p class="text-gray-400 text-lg">Secure access to manage Gym Goon operations, members, and transactions.</p>
        </div>
    </div>

    <!-- Right Side: Auth Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 relative" style="background: radial-gradient(circle at top right, #111 0%, #050505 60%);">
        
        <div class="w-full max-w-md relative z-10">
            
            <!-- Mobile Logo -->
            <div class="lg:hidden flex justify-center mb-10">
                <img src="../assets/img/k.png" alt="Gym Goon" class="w-20 drop-shadow-[0_0_15px_rgba(212,175,55,0.4)]">
            </div>
            
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gold-500/10 border border-gold-500/30 mb-4">
                    <i class="fa-solid fa-shield-halved text-2xl text-gold-500"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-white mb-2">Admin Portal</h2>
                <p class="text-gray-500 font-medium">Authorized personnel only</p>
            </div>

            <?php if ($error): ?>
                <div class="glass-card border-red-500/30 rounded-2xl p-5 mb-8 text-center flex flex-col items-center">
                    <div class="w-12 h-12 bg-red-500/10 rounded-full flex items-center justify-center text-red-500 mb-3">
                        <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    </div>
                    <h3 class="text-red-400 font-bold mb-1">Login Failed</h3>
                    <p class="text-sm text-gray-400"><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="login" class="space-y-6">
                <div>
                    <label for="username" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-user-shield text-gray-500"></i>
                        </div>
                        <input type="text" name="username" id="username" required 
                               class="input-premium w-full rounded-xl pl-12 pr-4 py-4 text-white">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-500"></i>
                        </div>
                        <input type="password" name="password" id="password" required 
                               class="input-premium w-full rounded-xl pl-12 pr-4 py-4 text-white">
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full py-4 mt-2 rounded-xl font-bold uppercase tracking-wider text-sm flex items-center justify-center gap-2">
                    Log in <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

</body>
</html>
