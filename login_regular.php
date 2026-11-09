<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if (isLoggedIn() || isRegularLoggedIn()) {
    header("Location: index");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM regular_members WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['regular_id'] = $user['id'];
        setcookie("regular_logged_in", "1", time() + 86400, "/");
        header("Location: index");
        exit;
    } else {
        $error = "Nomor Handphone atau Password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Member Biasa - Gym Goon</title>
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
<body class="bg-black min-h-screen flex text-gray-100 flex-row-reverse">

    <!-- Left Side (Reverse): Branding -->
    <div class="hidden lg:flex lg:w-1/2 relative flex-col justify-between p-12 overflow-hidden bg-dark-900 border-l border-gold-500/10">
        <!-- Background decorative elements -->
        <div class="absolute top-0 left-0 w-full h-full opacity-30" style="background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 40px 40px;"></div>
        <div class="absolute -left-32 -top-32 w-96 h-96 bg-gray-600 rounded-full opacity-10 blur-[100px]"></div>

        <div class="relative z-10 flex justify-end">
            <img src="assets/img/k.png" alt="Gym Goon" class="w-16 grayscale opacity-70">
        </div>
        
        <div class="relative z-10 max-w-md ml-auto text-right">
            <span class="px-3 py-1 text-xs font-bold bg-gray-800 text-gray-400 rounded-full uppercase tracking-widest border border-gray-700 mb-4 inline-block">Regular Member</span>
            <h1 class="text-5xl font-extrabold text-white leading-tight mb-4">Start Your<br><span class="text-transparent bg-clip-text bg-gradient-to-l from-gray-400 to-white">Journey Today</span></h1>
            <p class="text-gray-500 text-lg">Sign in to manage your regular packages and track your fitness progress.</p>
        </div>
    </div>

    <!-- Right Side (Reverse): Auth Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 relative" style="background: radial-gradient(circle at top left, #111 0%, #050505 60%);">
        
        <div class="w-full max-w-md relative z-10">
            
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-800 border border-gray-700 mb-4">
                    <i class="fa-regular fa-user text-2xl text-gray-400"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-white mb-2">Welcome Back</h2>
                <p class="text-gray-500 font-medium">Log in to your regular account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="glass-card border-red-500/30 rounded-2xl p-5 mb-8 text-center flex flex-col items-center">
                    <div class="w-12 h-12 bg-red-500/10 rounded-full flex items-center justify-center text-red-500 mb-3">
                        <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    </div>
                    <h3 class="text-red-400 font-bold mb-1">Akses Ditolak</h3>
                    <p class="text-sm text-gray-400"><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="login_regular" class="space-y-6">
                <div>
                    <label for="phone" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nomor Handphone</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-phone text-gray-500"></i>
                        </div>
                        <input type="tel" name="phone" id="phone" required placeholder="08xxxxxxxxxx"
                               class="input-premium w-full rounded-xl pl-12 pr-4 py-4 text-white focus:border-gray-400 focus:ring-1 focus:ring-gray-400">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-500"></i>
                        </div>
                        <input type="password" name="password" id="password" required placeholder="••••••"
                               class="input-premium w-full rounded-xl pl-12 pr-4 py-4 text-white focus:border-gray-400 focus:ring-1 focus:ring-gray-400">
                    </div>
                </div>

                <button type="submit" class="w-full py-4 mt-2 rounded-xl font-bold uppercase tracking-wider text-sm bg-gray-200 text-black hover:bg-white transition-colors flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(255,255,255,0.1)] hover:shadow-[0_0_25px_rgba(255,255,255,0.2)]">
                    Login <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-between text-sm text-gray-500 border-t border-gray-800 pt-6">
                <p>Belum punya akun? <a href="register" class="text-white font-bold hover:underline">Daftar</a></p>
                <a href="login" class="text-gold-500 hover:underline font-bold mt-2 sm:mt-0 flex items-center gap-2">
                    <i class="fa-solid fa-crown"></i> VIP Login
                </a>
            </div>
        </div>
    </div>

</body>
</html>
