<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

$stmt = $pdo->query("SELECT * FROM members ORDER BY created_at DESC");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_vip = count($members);
$total_regular = (int)$pdo->query("SELECT COUNT(*) FROM regular_members")->fetchColumn();
$pending_requests_count = (int)$pdo->query("SELECT COUNT(*) FROM vip_requests WHERE status = 'pending'")->fetchColumn();

$initial_search = isset($_GET['search']) ? trim($_GET['search']) : '';

function getInitials($name) {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $initials ?: 'VP';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Members CRM - Gym Goon</title>
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
    <script>
        window.addEventListener("pageshow", function (event) {
            if (event.persisted) { window.location.reload(); }
        });
    </script>
</head>
<body class="bg-black text-gray-100 h-screen overflow-hidden flex" 
      x-data="{ 
          sidebarOpen: false, 
          search: '<?= htmlspecialchars(addslashes($initial_search)) ?>',
          filterPoints: 'all'
      }">
    
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
                    <a href="vip_members" class="flex items-center justify-between px-4 py-3 rounded-xl bg-gradient-to-r from-gold-500/20 to-transparent text-gold-400 font-bold border-l-4 border-gold-500 transition-all">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-crown w-5 text-center text-gold-500"></i>
                            <span>VIP Members</span>
                        </div>
                        <span class="text-xs bg-gold-500/20 border border-gold-500/40 text-gold-400 px-2 py-0.5 rounded-full"><?= $total_vip ?></span>
                    </a>
                    <a href="vip_requests" class="flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors group">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-clipboard-list w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                            <span>Request VIP</span>
                        </div>
                        <?php if ($pending_requests_count > 0): ?>
                            <span class="bg-gold-500 text-black text-xs font-extrabold px-2 py-0.5 rounded-full animate-pulse"><?= $pending_requests_count ?></span>
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

        <!-- Top Navbar CRM -->
        <header class="bg-dark-900/80 backdrop-blur-md border-b border-gray-800/80 px-4 lg:px-8 py-3.5 flex justify-between items-center z-10">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h1 class="text-lg font-extrabold text-white uppercase tracking-wider">Kontak VIP Member</h1>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 text-black font-extrabold flex items-center justify-center">
                    <i class="fa-solid fa-crown text-sm"></i>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 z-10 space-y-6">

            <!-- Header & Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-white mb-1">Direktori Member VIP</h2>
                    <p class="text-gray-400 text-sm">Kelola kartu akses QR, poin loyalitas, dan saldo seluruh member eksklusif Gym Goon.</p>
                </div>
                
                <a href="add_member" class="bg-gold-500 text-black px-5 py-2.5 rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-gold-400 transition-colors shadow-[0_0_15px_rgba(212,175,55,0.4)] inline-flex items-center gap-2 self-start sm:self-auto">
                    <i class="fa-solid fa-plus"></i> <span>Tambah VIP</span>
                </a>
            </div>

            <!-- CRM Search & Filter Bar (Seperti Halaman Kontak Referensi) -->
            <div class="glass-panel rounded-2xl p-4 border border-gray-800 flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input type="text" x-model="search" placeholder="Cari nama member VIP, ID kartu, atau nomor telepon..." 
                           class="w-full bg-dark-900 border border-gray-800 focus:border-gold-500 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none transition-colors">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="relative w-full sm:w-48">
                        <i class="fa-solid fa-filter absolute left-3.5 top-1/2 -translate-y-1/2 text-gold-500 text-xs"></i>
                        <select x-model="filterPoints" class="w-full bg-dark-900 border border-gray-800 text-gray-300 text-xs font-bold rounded-xl pl-9 pr-4 py-3 focus:outline-none focus:border-gold-500">
                            <option value="all">Semua Status Poin</option>
                            <option value="has_points">Memiliki Poin (> 0)</option>
                            <option value="redeemable">Bisa Tukar Paket (≥ 100 Pts)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="glass-panel rounded-2xl border border-gray-800 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead class="bg-dark-900/80 text-gray-400 text-xs uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-4 border-b border-gray-800">Member VIP</th>
                                <th class="px-6 py-4 border-b border-gray-800">VIP ID</th>
                                <th class="px-6 py-4 border-b border-gray-800">Telepon</th>
                                <th class="px-6 py-4 border-b border-gray-800">Poin Loyalitas</th>
                                <th class="px-6 py-4 border-b border-gray-800">Saldo</th>
                                <th class="px-6 py-4 border-b border-gray-800">Status</th>
                                <th class="px-6 py-4 border-b border-gray-800">Terdaftar</th>
                                <th class="px-6 py-4 border-b border-gray-800 text-right">Aksi Kartu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            <?php foreach ($members as $m): 
                                $search_str = strtolower($m['id'] . ' ' . $m['name'] . ' ' . $m['phone']);
                                $pts = (int)$m['points'];
                            ?>
                                <tr class="hover:bg-gray-800/30 transition-colors"
                                    x-show="('<?= htmlspecialchars(addslashes($search_str)) ?>'.includes(search.toLowerCase())) && (filterPoints === 'all' || (filterPoints === 'has_points' && <?= $pts ?> > 0) || (filterPoints === 'redeemable' && <?= $pts ?> >= 100))">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-gold-500/20 text-gold-400 border border-gold-500/40 flex items-center justify-center font-bold text-xs shrink-0">
                                                <?= htmlspecialchars(getInitials($m['name'])) ?>
                                            </div>
                                            <span class="font-bold text-white"><?= htmlspecialchars($m['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-dark-900 border border-gold-500/30 text-gold-400 font-mono text-xs px-2.5 py-1 rounded-lg font-bold">
                                            <?= htmlspecialchars($m['id']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300 text-sm">
                                        <i class="fa-brands fa-whatsapp text-emerald-400 mr-1"></i> <?= htmlspecialchars($m['phone']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gold-500 font-bold text-sm"><?= number_format($m['points']) ?> <span class="text-xs text-gray-500 font-normal">Pts</span></td>
                                    <td class="px-6 py-4 text-gray-300 text-sm font-semibold">Rp <?= number_format($m['balance'], 0, ',', '.') ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> VIP Aktif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <?php
                                        $scan_url = "https://gym.tezworks.my.id/login?id=" . urlencode($m['id']);
                                        $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&color=D4AF37&bgcolor=1a1a1a&data=" . urlencode($scan_url);
                                        ?>
                                        <a href="<?= $qr_image_url ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gold-500/50 text-gold-500 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-gold-500 hover:text-black transition-colors">
                                            <i class="fa-solid fa-qrcode"></i> QR
                                        </a>
                                        <a href="print_card.php?id=<?= urlencode($m['id']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-700 bg-dark-800 text-gray-200 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-white hover:text-black transition-colors ml-1.5">
                                            <i class="fa-solid fa-print"></i> Cetak
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($members)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fa-solid fa-users-slash text-4xl mb-3 opacity-20"></i>
                                        <p>Belum ada data member VIP.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <footer class="mt-12 text-center text-xs text-gray-600 pb-8">
                &copy; <?= date('Y') ?> Gym Goon CRM System.
            </footer>
        </div>
    </main>
</body>
</html>
