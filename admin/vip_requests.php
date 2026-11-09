<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

$stmt = $pdo->query("SELECT vr.*, rm.balance as regular_balance FROM vip_requests vr JOIN regular_members rm ON vr.regular_member_id = rm.id WHERE vr.status = 'pending' ORDER BY vr.created_at ASC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending_requests_count = count($requests);
$total_vip = (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$total_regular = (int)$pdo->query("SELECT COUNT(*) FROM regular_members")->fetchColumn();

function getInitials($name) {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $initials ?: 'RQ';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Requests CRM - Gym Goon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { gold: { 400: '#FFDF73', 500: '#D4AF37', 600: '#AA8C2C', 700: '#8B7322' }, dark: { 900: '#050505', 800: '#111111', 700: '#1a1a1a' } } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #050505; color: #f5f5f5; }
        .glass-panel { background: rgba(22, 22, 22, 0.6); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.06); }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #D4AF37; }
    </style>
</head>
<body class="bg-black text-gray-100 h-screen overflow-hidden flex" x-data="{ sidebarOpen: false }">
    
    <!-- Sidebar CRM -->
    <aside class="fixed inset-y-0 left-0 bg-dark-900 w-64 border-r border-gray-800/80 z-50 transform transition-transform duration-300 lg:translate-x-0 lg:static lg:flex lg:flex-col justify-between"
           :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
        
        <div>
            <div class="p-6 border-b border-gray-800/80 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <img src="../assets/img/k.png" alt="Gym Goon" class="w-9 drop-shadow-[0_0_12px_rgba(212,175,55,0.5)]">
                    <div>
                        <span class="font-extrabold text-white tracking-wider uppercase block leading-none">GYM GOON</span>
                        <span class="text-[10px] text-gold-500 font-bold tracking-widest uppercase">CRM System</span>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="px-4 pt-5 pb-2">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest px-3 mb-2">Menu Utama</p>
                <nav class="space-y-1.5">
                    <a href="index" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors group">
                        <i class="fa-solid fa-chart-pie w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                        <span>Dashboard CRM</span>
                    </a>
                    <a href="vip_members" class="flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors group">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-crown w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                            <span>VIP Members</span>
                        </div>
                        <span class="text-xs bg-dark-800 border border-gray-700 text-gray-300 px-2 py-0.5 rounded-full"><?= $total_vip ?></span>
                    </a>
                    <a href="vip_requests" class="flex items-center justify-between px-4 py-3 rounded-xl bg-gradient-to-r from-gold-500/20 to-transparent text-gold-400 font-bold border-l-4 border-gold-500 transition-all">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-clipboard-list w-5 text-center text-gold-500"></i>
                            <span>Request VIP</span>
                        </div>
                        <?php if ($pending_requests_count > 0): ?>
                            <span class="bg-gold-500 text-black text-xs font-extrabold px-2 py-0.5 rounded-full"><?= $pending_requests_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="regular_members" class="flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors group">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-users w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                            <span>Member Reguler</span>
                        </div>
                        <span class="text-xs bg-dark-800 border border-gray-700 text-gray-400 px-2 py-0.5 rounded-full"><?= $total_regular ?></span>
                    </a>
                </nav>
            </div>

            <div class="px-4 pt-4">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest px-3 mb-2">Aksi Cepat</p>
                <a href="add_member" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gold-500/10 hover:bg-gold-500 text-gold-400 hover:text-black font-bold border border-gold-500/30 transition-all text-sm">
                    <i class="fa-solid fa-user-plus w-5 text-center"></i>
                    <span>Tambah Member VIP</span>
                </a>
            </div>
        </div>

        <div class="p-4 border-t border-gray-800/80">
            <a href="logout" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-red-500/10 hover:text-red-400 font-bold transition-colors">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm" style="display: none;"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-black relative">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gold-500 rounded-full opacity-5 blur-[100px] pointer-events-none"></div>
        
        <!-- Top Header -->
        <header class="bg-dark-900/80 backdrop-blur-md border-b border-gray-800/80 px-4 lg:px-8 py-3.5 flex items-center justify-between shrink-0 z-10">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-lg font-extrabold text-white tracking-wider uppercase">Deals & Request Upgrade VIP</h2>
                </div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-dark-800 border border-gold-500/30 flex items-center justify-center">
                <i class="fa-solid fa-clipboard-list text-gold-500 text-sm"></i>
            </div>
        </header>

        <!-- Content Scrollable -->
        <div class="flex-1 overflow-auto p-4 lg:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div>
                    <h2 class="text-2xl font-extrabold text-white mb-1">Antrean Konversi VIP</h2>
                    <p class="text-gray-400 text-sm">Verifikasi pelanggan reguler yang mengajukan upgrade ke kartu fisik VIP.</p>
                </div>

                <div class="glass-panel rounded-2xl overflow-hidden shadow-2xl border border-gray-800">
                    <div class="p-5 border-b border-gray-800 flex justify-between items-center bg-dark-900/50">
                        <h3 class="font-bold text-white uppercase tracking-wider text-sm flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-gold-500"></i> Menunggu Persetujuan (<?= count($requests) ?>)
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-dark-900/80 text-gray-400 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-4 font-bold border-b border-gray-800">Nama Pelanggan</th>
                                    <th class="px-6 py-4 font-bold border-b border-gray-800">No. WhatsApp</th>
                                    <th class="px-6 py-4 font-bold border-b border-gray-800">Saldo Saat Ini</th>
                                    <th class="px-6 py-4 font-bold border-b border-gray-800">Waktu Pengajuan</th>
                                    <th class="px-6 py-4 font-bold border-b border-gray-800 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50 text-sm">
                                <?php if (count($requests) === 0): ?>
                                    <tr>
                                        <td colspan="5" class="p-12 text-center text-gray-500">
                                            <i class="fa-solid fa-circle-check text-3xl text-emerald-500/40 mb-3 block"></i>
                                            Tidak ada pengajuan VIP yang tertunda.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($requests as $req): ?>
                                    <tr class="hover:bg-gray-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <?= htmlspecialchars(getInitials($req['name'])) ?>
                                                </div>
                                                <span class="font-bold text-white"><?= htmlspecialchars($req['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-300">
                                            <i class="fa-brands fa-whatsapp text-emerald-400 mr-1"></i> <?= htmlspecialchars($req['phone']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-gold-400 font-bold">Rp <?= number_format($req['regular_balance'], 0, ',', '.') ?></td>
                                        <td class="px-6 py-4 text-gray-400 text-xs"><?= date('d M Y, H:i', strtotime($req['created_at'])) ?></td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="add_member.php?request_id=<?= $req['id'] ?>&name=<?= urlencode($req['name']) ?>&phone=<?= urlencode($req['phone']) ?>&balance=<?= urlencode($req['regular_balance']) ?>" 
                                               class="inline-flex items-center gap-2 px-4 py-2 bg-gold-500 hover:bg-gold-400 text-black rounded-xl text-xs font-extrabold uppercase tracking-wider transition-colors shadow-[0_0_15px_rgba(212,175,55,0.2)]">
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
