<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

$stmt = $pdo->query("SELECT vr.*, rm.balance as regular_balance FROM vip_requests vr JOIN regular_members rm ON vr.regular_member_id = rm.id WHERE vr.status = 'pending' ORDER BY vr.created_at ASC");
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Requests - Gym Goon</title>
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
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 rounded-xl hover:bg-gray-800 hover:text-white transition-colors group">
                <i class="fa-solid fa-chart-pie w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                <span class="font-bold tracking-wider text-sm uppercase">Dashboard</span>
            </a>
            
            <a href="vip_members.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 rounded-xl hover:bg-gray-800 hover:text-white transition-colors group">
                <i class="fa-solid fa-crown w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                <span class="font-bold tracking-wider text-sm uppercase">VIP Members</span>
            </a>

            <a href="vip_requests.php" class="flex items-center justify-between px-4 py-3 bg-gold-500/10 text-gold-500 rounded-xl transition-colors">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-clipboard-list w-5 text-center"></i>
                    <span class="font-bold tracking-wider text-sm uppercase">Request VIP</span>
                </div>
                <?php if (count($requests) > 0): ?>
                    <span class="bg-gold-500 text-black text-xs font-bold px-2 py-0.5 rounded-full"><?= count($requests) ?></span>
                <?php endif; ?>
            </a>
            
            <a href="regular_members.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 rounded-xl hover:bg-gray-800 hover:text-white transition-colors group">
                <i class="fa-solid fa-users w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                <span class="font-bold tracking-wider text-sm uppercase">Member Reguler</span>
            </a>

            <div class="pt-4 mt-4 border-t border-gray-800">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 rounded-xl hover:bg-red-500/10 transition-colors group">
                    <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center group-hover:text-red-500 transition-colors"></i>
                    <span class="font-bold tracking-wider text-sm uppercase">Logout</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[url('../assets/img/pattern.png')] bg-repeat bg-opacity-5">
        
        <!-- Top Header -->
        <header class="h-20 glass-panel border-b-0 px-8 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-extrabold text-white tracking-widest uppercase">Request VIP</h2>
                    <p class="text-xs text-gray-400">Persetujuan upgrade pelanggan</p>
                </div>
            </div>
        </header>

        <!-- Content Scrollable -->
        <div class="flex-1 overflow-auto p-8">
            <div class="max-w-7xl mx-auto">
                
                <div class="glass-panel rounded-3xl overflow-hidden shadow-2xl">
                    <div class="p-6 border-b border-gray-800 flex justify-between items-center bg-dark-900/50">
                        <h3 class="font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-clipboard-list text-gold-500"></i> Antrean Request (<?= count($requests) ?>)
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-black/40 text-gray-400 text-xs uppercase tracking-wider">
                                    <th class="p-4 font-bold border-b border-gray-800">Tanggal</th>
                                    <th class="p-4 font-bold border-b border-gray-800">Nama Pelanggan</th>
                                    <th class="p-4 font-bold border-b border-gray-800">No. WhatsApp</th>
                                    <th class="p-4 font-bold border-b border-gray-800">Saldo Saat Ini</th>
                                    <th class="p-4 font-bold border-b border-gray-800 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php if (count($requests) === 0): ?>
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-gray-500">Tidak ada pengajuan VIP yang tertunda.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($requests as $req): ?>
                                    <tr class="border-b border-gray-800/50 hover:bg-white/5 transition-colors">
                                        <td class="p-4 text-gray-400"><?= date('d M Y, H:i', strtotime($req['created_at'])) ?></td>
                                        <td class="p-4 font-bold text-white"><?= htmlspecialchars($req['name']) ?></td>
                                        <td class="p-4 text-gray-300"><?= htmlspecialchars($req['phone']) ?></td>
                                        <td class="p-4 text-gold-500 font-bold">Rp <?= number_format($req['regular_balance'], 0, ',', '.') ?></td>
                                        <td class="p-4 text-center">
                                            <a href="add_member.php?request_id=<?= $req['id'] ?>&name=<?= urlencode($req['name']) ?>&phone=<?= urlencode($req['phone']) ?>&balance=<?= urlencode($req['regular_balance']) ?>" 
                                               class="inline-flex items-center gap-2 px-4 py-2 bg-gold-500 hover:bg-gold-600 text-black rounded-xl text-xs font-bold uppercase tracking-wider transition-colors shadow-[0_0_15px_rgba(212,175,55,0.2)]">
                                                <i class="fa-solid fa-check"></i> Proses Upgrade
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>
