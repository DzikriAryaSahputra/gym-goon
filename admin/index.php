<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM members");
$total_vip = $stmt->fetch()['count'];

$stmt_regular = $pdo->query("SELECT COUNT(*) as count FROM regular_members");
$total_regular = $stmt_regular->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gym Goon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { gold: { 400: '#FFDF73', 500: '#D4AF37', 600: '#AA8C2C', 700: '#8B7322' }, dark: { 900: '#050505', 800: '#111111', 700: '#1a1a1a' } } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #050505; color: #f5f5f5; }
        .glass-panel { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.05); }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
    </style>
    <script>
        window.addEventListener("pageshow", function (event) {
            if (event.persisted) { window.location.reload(); }
        });
    </script>
</head>
<body class="bg-black text-gray-100 h-screen overflow-hidden flex" x-data="{ sidebarOpen: false }">
    
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 bg-dark-900 w-64 border-r border-gray-800 z-50 transform transition-transform duration-300 lg:translate-x-0 lg:static lg:block"
           :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
        
        <div class="p-6 border-b border-gray-800 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="../assets/img/k.png" alt="Gym Goon" class="w-8 drop-shadow-[0_0_10px_rgba(212,175,55,0.4)]">
                <span class="font-extrabold text-white tracking-widest uppercase">Admin</span>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="p-4 space-y-2">
            <a href="index" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gold-500/10 text-gold-500 font-bold border border-gold-500/20 transition-colors">
                <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                Dashboard
            </a>
            <a href="vip_members" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors">
                <i class="fa-solid fa-crown w-5 text-center"></i>
                VIP Members
            </a>
            <a href="vip_requests" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors">
                <i class="fa-solid fa-clipboard-list w-5 text-center"></i>
                Request VIP
            </a>
            <a href="regular_members" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors">
                <i class="fa-solid fa-users w-5 text-center"></i>
                Member Reguler
            </a>
        </nav>

        <div class="absolute bottom-0 left-0 w-full p-4 border-t border-gray-800">
            <a href="logout" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-red-500/10 hover:text-red-400 font-bold transition-colors">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>
                Logout
            </a>
        </div>
    </aside>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm" style="display: none;"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-black relative">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gold-500 rounded-full opacity-5 blur-[100px] pointer-events-none"></div>

        <header class="bg-dark-900/50 backdrop-blur-md border-b border-gray-800 p-4 lg:px-8 flex justify-between items-center z-10">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-bold text-white uppercase tracking-widest">Overview</h1>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-dark-800 border border-gold-500/30 flex items-center justify-center">
                    <i class="fa-solid fa-shield-halved text-gold-500 text-sm"></i>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 z-10">
            
            <!-- Welcome Header -->
            <div class="mb-8">
                <h2 class="text-2xl font-extrabold text-white mb-2">Welcome to Command Center</h2>
                <p class="text-gray-400">Here's a quick overview of Gym Goon's current statistics.</p>
            </div>

            <!-- Stats overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="glass-panel rounded-2xl p-6 border border-gray-800 relative overflow-hidden transition-all hover:bg-dark-800/50">
                    <div class="absolute right-4 top-4 text-gold-500 text-4xl opacity-20"><i class="fa-solid fa-crown"></i></div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Total VIP Members</p>
                    <p class="text-4xl font-extrabold text-gold-500"><?= $total_vip ?></p>
                </div>
                
                <div class="glass-panel rounded-2xl p-6 border border-gray-800 relative overflow-hidden transition-all hover:bg-dark-800/50">
                    <div class="absolute right-4 top-4 text-gray-500 text-4xl opacity-20"><i class="fa-solid fa-users"></i></div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Total Member Reguler</p>
                    <p class="text-4xl font-extrabold text-white"><?= $total_regular ?></p>
                </div>
            </div>

            <div class="glass-panel rounded-2xl p-8 border border-gray-800 text-center shadow-lg mt-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-dark-800 border border-gray-700 mb-4 text-gray-500">
                    <i class="fa-solid fa-layer-group text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Manajemen Member</h3>
                <p class="text-gray-400 max-w-md mx-auto mb-6">Pilih menu di sebelah kiri untuk melihat daftar detail member, menambah member VIP baru, atau mengelola data akun reguler.</p>
                <div class="flex justify-center gap-4">
                    <a href="vip_members" class="bg-gold-500 text-black px-6 py-2.5 rounded-lg font-bold text-sm uppercase tracking-wider hover:bg-gold-400 transition-colors">
                        Kelola VIP
                    </a>
                    <a href="regular_members" class="bg-dark-800 text-white px-6 py-2.5 rounded-lg font-bold text-sm border border-gray-700 uppercase tracking-wider hover:bg-dark-700 transition-colors">
                        Kelola Reguler
                    </a>
                </div>
            </div>
            
            <footer class="mt-12 text-center text-xs text-gray-600 pb-8">
                &copy; <?= date('Y') ?> Gym Goon Admin System.
            </footer>
        </div>
    </main>
</body>
</html>
