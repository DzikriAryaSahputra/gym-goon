<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

$stmt_regular = $pdo->query("SELECT * FROM regular_members ORDER BY created_at DESC");
$regular_members = $stmt_regular->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Reguler - Gym Goon</title>
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
            <a href="index" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors">
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
            <a href="regular_members" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gold-500/10 text-gold-500 font-bold border border-gold-500/20 transition-colors">
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
                <h1 class="text-xl font-bold text-white uppercase tracking-widest">Member Reguler</h1>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-dark-800 border border-gold-500/30 flex items-center justify-center">
                    <i class="fa-solid fa-users text-gold-500 text-sm"></i>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 z-10">
            
            <div class="mb-6">
                <h2 class="text-2xl font-extrabold text-white mb-1">Data Member Reguler</h2>
                <p class="text-gray-400 text-sm">Kelola seluruh data member biasa yang mendaftar secara online.</p>
            </div>

            <!-- Table Card for Regular Members -->
            <div class="glass-panel rounded-2xl border border-gray-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead class="bg-dark-900/80 text-gray-400 text-xs uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-4 border-b border-gray-800">ID</th>
                                <th class="px-6 py-4 border-b border-gray-800">Nama</th>
                                <th class="px-6 py-4 border-b border-gray-800">No. HP</th>
                                <th class="px-6 py-4 border-b border-gray-800">Username</th>
                                <th class="px-6 py-4 border-b border-gray-800">Saldo Dummy</th>
                                <th class="px-6 py-4 border-b border-gray-800">Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            <?php foreach ($regular_members as $m): ?>
                                <tr class="hover:bg-gray-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="bg-dark-900 border border-gray-700 text-gray-400 font-mono text-xs px-2 py-1 rounded">
                                            <?= htmlspecialchars($m['id']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($m['name']) ?></td>
                                    <td class="px-6 py-4 text-gray-400 text-sm"><?= htmlspecialchars($m['phone']) ?></td>
                                    <td class="px-6 py-4 text-gray-400 text-sm"><?= htmlspecialchars($m['username']) ?></td>
                                    <td class="px-6 py-4 text-gray-300 text-sm">Rp <?= number_format($m['balance'], 0, ',', '.') ?></td>
                                    <td class="px-6 py-4 text-gray-500 text-xs"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($regular_members)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fa-solid fa-users-slash text-4xl mb-3 opacity-20"></i>
                                        <p>Belum ada data member biasa.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <footer class="mt-12 text-center text-xs text-gray-600 pb-8">
                &copy; <?= date('Y') ?> Gym Goon Admin System.
            </footer>
        </div>
    </main>
</body>
</html>
