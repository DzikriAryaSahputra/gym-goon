<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

// 1. Statistik Utama
$stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(points), 0) as total_points, COALESCE(SUM(balance), 0) as total_balance FROM members");
$vip_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$total_vip = (int)$vip_stats['count'];
$total_points = (int)$vip_stats['total_points'];

$stmt_regular = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(balance), 0) as total_balance FROM regular_members");
$reg_stats = $stmt_regular->fetch(PDO::FETCH_ASSOC);
$total_regular = (int)$reg_stats['count'];

$total_all_members = $total_vip + $total_regular;

// Request VIP stats
$stmt_req_pending = $pdo->query("SELECT COUNT(*) FROM vip_requests WHERE status = 'pending'");
$pending_requests_count = (int)$stmt_req_pending->fetchColumn();

$stmt_req_approved = $pdo->query("SELECT COUNT(*) FROM vip_requests WHERE status = 'approved'");
$approved_requests_count = (int)$stmt_req_approved->fetchColumn();

$total_requests = $pending_requests_count + $approved_requests_count;

// Pertumbuhan bulan ini
$current_month = date('Y-m');
$stmt_vip_month = $pdo->prepare("SELECT COUNT(*) FROM members WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_vip_month->execute([$current_month]);
$vip_this_month = (int)$stmt_vip_month->fetchColumn();

$stmt_reg_month = $pdo->prepare("SELECT COUNT(*) FROM regular_members WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt_reg_month->execute([$current_month]);
$reg_this_month = (int)$stmt_reg_month->fetchColumn();

$new_members_this_month = $vip_this_month + $reg_this_month;

// Estimasi Nilai Membership (Berdasarkan nilai paket rata-rata / pendaftaran VIP Rp 200.000 & Reguler Rp 250.000)
$estimated_revenue = ($total_vip * 200000) + ($total_regular * 250000);

// 2. Data Grafik Bulanan (Jan - Des Tahun Ini)
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$monthly_vip = array_fill(1, 12, 0);
$monthly_reg = array_fill(1, 12, 0);

$stmt_m_vip = $pdo->prepare("SELECT MONTH(created_at) as m, COUNT(*) as c FROM members WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)");
$stmt_m_vip->execute([$selected_year]);
foreach ($stmt_m_vip->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $monthly_vip[(int)$row['m']] = (int)$row['c'];
}

$stmt_m_reg = $pdo->prepare("SELECT MONTH(created_at) as m, COUNT(*) as c FROM regular_members WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)");
$stmt_m_reg->execute([$selected_year]);
foreach ($stmt_m_reg->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $monthly_reg[(int)$row['m']] = (int)$row['c'];
}

// 3. Data Antrean Request VIP Terbaru
$stmt_pending_list = $pdo->query("
    SELECT vr.*, rm.balance as regular_balance 
    FROM vip_requests vr 
    JOIN regular_members rm ON vr.regular_member_id = rm.id 
    WHERE vr.status = 'pending' 
    ORDER BY vr.created_at DESC 
    LIMIT 5
");
$pending_list = $stmt_pending_list->fetchAll(PDO::FETCH_ASSOC);

// 4. Gabungan Member Terbaru & Aktivitas CRM
$stmt_recent_vip = $pdo->query("SELECT id, name, phone, points, balance, created_at, 'VIP' as member_type FROM members ORDER BY created_at DESC LIMIT 6");
$recent_vips = $stmt_recent_vip->fetchAll(PDO::FETCH_ASSOC);

$stmt_recent_reg = $pdo->query("SELECT id, name, phone, 0 as points, balance, created_at, 'Reguler' as member_type FROM regular_members ORDER BY created_at DESC LIMIT 6");
$recent_regs = $stmt_recent_reg->fetchAll(PDO::FETCH_ASSOC);

// Gabungkan dan urutkan berdasarkan created_at terbaru
$recent_all = array_merge($recent_vips, $recent_regs);
usort($recent_all, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_all = array_slice($recent_all, 0, 6);

// 5. Data Paket Gym
$stmt_pkg = $pdo->query("SELECT * FROM packages ORDER BY price_member ASC");
$packages = $stmt_pkg->fetchAll(PDO::FETCH_ASSOC);

// Format tanggal Indonesia
$hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulan_indo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggal_sekarang = $hari_indo[(int)date('w')] . ', ' . date('d') . ' ' . $bulan_indo[(int)date('n')] . ' ' . date('Y');

// Helper inisial nama
function getInitials($name) {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $initials ?: 'GM';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Command Center - Gym Goon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: { 400: '#FFDF73', 500: '#D4AF37', 600: '#AA8C2C', 700: '#8B7322' },
                        dark: { 900: '#050505', 800: '#111111', 700: '#1a1a1a' }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #050505; color: #f5f5f5; }
        .glass-panel { background: rgba(22, 22, 22, 0.6); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.06); }
        .glass-panel-gold { background: linear-gradient(135deg, rgba(212, 175, 55, 0.08) 0%, rgba(17, 17, 17, 0.7) 100%); border: 1px solid rgba(212, 175, 55, 0.2); }
        
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
<body class="bg-black text-gray-100 h-screen overflow-hidden flex" x-data="{ sidebarOpen: false, notifOpen: false, quickSearch: '' }">
    
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
                    <a href="index" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-gold-500/20 to-transparent text-gold-400 font-bold border-l-4 border-gold-500 transition-all">
                        <i class="fa-solid fa-chart-pie w-5 text-center text-gold-500"></i>
                        <span>Dashboard CRM</span>
                    </a>
                    <a href="vip_members" class="flex items-center justify-between px-4 py-3 rounded-xl text-gray-400 hover:bg-dark-800 hover:text-white font-semibold transition-colors group">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-crown w-5 text-center group-hover:text-gold-500 transition-colors"></i>
                            <span>VIP Members</span>
                        </div>
                        <span class="text-xs bg-dark-800 border border-gray-700 text-gray-300 px-2 py-0.5 rounded-full"><?= $total_vip ?></span>
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

    <!-- Mobile Overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm" style="display: none;"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-black relative">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-gold-500 rounded-full opacity-[0.04] blur-[130px] pointer-events-none"></div>

        <!-- Top Navbar CRM (Seperti Referensi) -->
        <header class="bg-dark-900/80 backdrop-blur-md border-b border-gray-800/80 px-4 lg:px-8 py-3.5 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4 flex-1 max-w-xl">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                
                <!-- Global Search Input -->
                <form action="vip_members" method="GET" class="relative w-full max-w-md hidden sm:block">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input type="text" name="search" placeholder="Cari member VIP, nama, ID kartu, atau no. telepon..." 
                           class="w-full bg-dark-800/90 border border-gray-800 focus:border-gold-500/50 rounded-xl pl-10 pr-4 py-2 text-sm text-gray-200 placeholder-gray-500 focus:outline-none transition-all">
                </form>
            </div>
            
            <!-- Right Actions: Notification & Profile -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell Dropdown -->
                <div class="relative" @click.away="notifOpen = false">
                    <button @click="notifOpen = !notifOpen" class="relative w-10 h-10 rounded-xl bg-dark-800 border border-gray-800 hover:border-gold-500/40 flex items-center justify-center text-gray-300 hover:text-gold-400 transition-colors">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($pending_requests_count > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-extrabold flex items-center justify-center border-2 border-dark-900">
                                <?= $pending_requests_count ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown Notifikasi -->
                    <div x-show="notifOpen" x-transition style="display: none;" class="absolute right-0 mt-2 w-80 glass-panel bg-dark-900 border border-gray-800 rounded-2xl shadow-2xl overflow-hidden z-50">
                        <div class="p-4 border-b border-gray-800 flex justify-between items-center">
                            <span class="font-bold text-sm text-white">Notifikasi Request VIP</span>
                            <span class="text-xs bg-gold-500/20 text-gold-400 px-2 py-0.5 rounded-full font-bold"><?= $pending_requests_count ?> Baru</span>
                        </div>
                        <div class="max-h-64 overflow-y-auto divide-y divide-gray-800/60">
                            <?php if (empty($pending_list)): ?>
                                <div class="p-6 text-center text-gray-500 text-xs">
                                    <i class="fa-regular fa-bell-slash text-2xl mb-2 block opacity-40"></i>
                                    Belum ada pengajuan upgrade VIP baru.
                                </div>
                            <?php else: ?>
                                <?php foreach ($pending_list as $req): ?>
                                    <a href="add_member?request_id=<?= $req['id'] ?>&name=<?= urlencode($req['name']) ?>&phone=<?= urlencode($req['phone']) ?>&balance=<?= urlencode($req['regular_balance']) ?>" class="block p-3.5 hover:bg-dark-800/80 transition-colors">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gold-500/20 text-gold-400 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                                <?= htmlspecialchars(getInitials($req['name'])) ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($req['name']) ?></p>
                                                <p class="text-[11px] text-gray-400">Mengajukan upgrade ke Member VIP</p>
                                                <p class="text-[10px] text-gold-500 mt-1"><?= date('d M Y, H:i', strtotime($req['created_at'])) ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="vip_requests" class="block text-center py-2.5 bg-dark-800 hover:bg-dark-700 text-xs font-bold text-gold-400 border-t border-gray-800 transition-colors">
                            Lihat Semua Request
                        </a>
                    </div>
                </div>

                <div class="h-6 w-[1px] bg-gray-800"></div>

                <!-- Admin Profile Info -->
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 text-black font-extrabold flex items-center justify-center shadow-[0_0_15px_rgba(212,175,55,0.3)]">
                        <i class="fa-solid fa-user-shield text-sm"></i>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-xs font-bold text-white leading-tight">Administrator</p>
                        <p class="text-[10px] text-gray-400">Gym Manager</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable CRM Dashboard Body -->
        <div class="flex-1 overflow-y-auto p-4 lg:p-8 z-10 space-y-6">
            
            <!-- Welcome Banner & Date (Seperti Referensi) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">
                        Selamat Datang, <span class="text-transparent bg-clip-text bg-gradient-to-r from-gold-400 to-gold-600">Admin!</span>
                    </h2>
                    <p class="text-gray-400 text-sm mt-1">Kelola hubungan member, pantau konversi VIP, dan kembangkan bisnis Gym Goon lebih mudah.</p>
                </div>
                <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl glass-panel border border-gray-800 text-xs font-semibold text-gray-300 shrink-0 self-start sm:self-auto">
                    <i class="fa-regular fa-calendar text-gold-500"></i>
                    <span><?= $tanggal_sekarang ?></span>
                </div>
            </div>

            <!-- 4 KPI Stat Cards (Mirip Referensi Plus CRM dengan Tema Gym) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                
                <!-- Card 1: Total Semua Member -->
                <div class="glass-panel rounded-2xl p-5 border border-gray-800/90 hover:border-gold-500/30 transition-all relative overflow-hidden group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-address-book"></i>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-arrow-trend-up"></i> +<?= $new_members_this_month ?> bulan ini
                        </span>
                    </div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Total Semua Member</p>
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-3xl font-extrabold text-white"><?= number_format($total_all_members) ?></h3>
                        <span class="text-xs text-gray-500">Kontak Terdaftar</span>
                    </div>
                </div>

                <!-- Card 2: Total Member VIP -->
                <div class="glass-panel-gold rounded-2xl p-5 hover:border-gold-500/50 transition-all relative overflow-hidden group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gold-500/20 border border-gold-500/30 text-gold-400 flex items-center justify-center text-xl shadow-[0_0_15px_rgba(212,175,55,0.2)]">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                        <?php 
                        $vip_ratio = $total_all_members > 0 ? round(($total_vip / $total_all_members) * 100) : 0;
                        ?>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gold-400 bg-gold-500/15 border border-gold-500/30 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-star text-[9px]"></i> <?= $vip_ratio ?>% Rasio VIP
                        </span>
                    </div>
                    <p class="text-gold-400/80 text-xs font-bold uppercase tracking-wider mb-1">Member VIP Aktif</p>
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-3xl font-extrabold text-gold-400"><?= number_format($total_vip) ?></h3>
                        <span class="text-xs text-gray-400"><?= number_format($total_points) ?> Total Poin</span>
                    </div>
                </div>

                <!-- Card 3: Member Reguler & Deals Upgrade -->
                <div class="glass-panel rounded-2xl p-5 border border-gray-800/90 hover:border-gold-500/30 transition-all relative overflow-hidden group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-user-Check"></i>
                        </div>
                        <?php if ($pending_requests_count > 0): ?>
                            <a href="vip_requests" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-2.5 py-1 rounded-full hover:bg-amber-500/20">
                                <i class="fa-solid fa-bolt"></i> <?= $pending_requests_count ?> Menunggu Upgrade
                            </a>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-400 bg-gray-800 px-2.5 py-1 rounded-full">
                                <?= $approved_requests_count ?> Upgrade Selesai
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Member Reguler (Prospek)</p>
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-3xl font-extrabold text-white"><?= number_format($total_regular) ?></h3>
                        <span class="text-xs text-gray-500"><?= $pending_requests_count ?> Request VIP</span>
                    </div>
                </div>

                <!-- Card 4: Estimasi Nilai Membership -->
                <div class="glass-panel rounded-2xl p-5 border border-gray-800/90 hover:border-gold-500/30 transition-all relative overflow-hidden group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-check-circle"></i> Valuasi Aktif
                        </span>
                    </div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Estimasi Nilai Member</p>
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-2xl lg:text-3xl font-extrabold text-white">Rp <?= number_format($estimated_revenue, 0, ',', '.') ?></h3>
                    </div>
                </div>

            </div>

            <!-- Charts Row: Grafik Pertumbuhan Bulanan & Donut Status Member -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Bar Chart: Pertumbuhan Member Bulanan (2/3 width) -->
                <div class="lg:col-span-2 glass-panel rounded-2xl p-6 border border-gray-800/90 flex flex-col justify-between">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <div>
                            <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-column text-gold-500"></i> Pertumbuhan Member Bulanan
                            </h3>
                            <p class="text-xs text-gray-400">Statistik pendaftaran Member VIP & Reguler sepanjang tahun <?= $selected_year ?></p>
                        </div>
                        <form method="GET" class="self-start sm:self-auto">
                            <select name="year" onchange="this.form.submit()" class="bg-dark-800 border border-gray-700 text-gray-200 text-xs font-bold rounded-xl px-3 py-2 focus:outline-none focus:border-gold-500">
                                <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 2; $y--): ?>
                                    <option value="<?= $y ?>" <?= $selected_year === $y ? 'selected' : '' ?>>Tahun <?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>

                    <div class="relative h-64 w-full">
                        <canvas id="monthlyGrowthChart"></canvas>
                    </div>
                </div>

                <!-- Donut Chart: Komposisi & Status Member (1/3 width) -->
                <div class="glass-panel rounded-2xl p-6 border border-gray-800/90 flex flex-col justify-between">
                    <div class="mb-4">
                        <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-gold-500"></i> Komposisi Member
                        </h3>
                        <p class="text-xs text-gray-400">Distribusi kategori & pipeline upgrade</p>
                    </div>

                    <div class="relative h-48 flex items-center justify-center my-2">
                        <canvas id="memberCompositionChart"></canvas>
                        <!-- Center Label inside Donut -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-3xl font-extrabold text-white"><?= $total_all_members ?></span>
                            <span class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold">Total Member</span>
                        </div>
                    </div>

                    <!-- Custom Legend Seperti Gambar Referensi -->
                    <?php
                    $denom = max($total_all_members, 1);
                    $pct_vip = round(($total_vip / $denom) * 100);
                    $reg_non_pending = max($total_regular - $pending_requests_count, 0);
                    $pct_reg = round(($reg_non_pending / $denom) * 100);
                    $pct_req = round(($pending_requests_count / $denom) * 100);
                    ?>
                    <div class="space-y-2.5 mt-4 pt-4 border-t border-gray-800/80 text-xs">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-sm bg-gold-500 inline-block"></span>
                                <span class="text-gray-300 font-medium">Member VIP Aktif</span>
                            </div>
                            <span class="font-bold text-white"><?= $total_vip ?> <span class="text-gray-500 font-normal">(<?= $pct_vip ?>%)</span></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span>
                                <span class="text-gray-300 font-medium">Member Reguler</span>
                            </div>
                            <span class="font-bold text-white"><?= $reg_non_pending ?> <span class="text-gray-500 font-normal">(<?= $pct_reg ?>%)</span></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-sm bg-amber-400 inline-block"></span>
                                <span class="text-gray-300 font-medium">Menunggu Upgrade VIP</span>
                            </div>
                            <span class="font-bold text-white"><?= $pending_requests_count ?> <span class="text-gray-500 font-normal">(<?= $pct_req ?>%)</span></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pipeline & Direktori Kontak Terbaru + Aktivitas Paket -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri (2/3): Daftar Kontak Member Terbaru & Antrean Upgrade -->
                <div class="lg:col-span-2 glass-panel rounded-2xl border border-gray-800/90 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                                <i class="fa-solid fa-users-viewfinder text-gold-500"></i> Kontak & Member Terbaru
                            </h3>
                            <p class="text-xs text-gray-400">Daftar pelanggan VIP dan Reguler yang baru bergabung</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="vip_members" class="px-3.5 py-2 rounded-xl bg-dark-800 hover:bg-dark-700 text-xs font-bold text-gold-400 border border-gray-700 transition-colors">
                                Semua VIP
                            </a>
                            <a href="regular_members" class="px-3.5 py-2 rounded-xl bg-dark-800 hover:bg-dark-700 text-xs font-bold text-gray-300 border border-gray-700 transition-colors">
                                Semua Reguler
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead class="bg-dark-900/70 text-gray-400 text-[11px] uppercase tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-3.5 border-b border-gray-800">Member</th>
                                    <th class="px-6 py-3.5 border-b border-gray-800">Telepon</th>
                                    <th class="px-6 py-3.5 border-b border-gray-800">Saldo / Poin</th>
                                    <th class="px-6 py-3.5 border-b border-gray-800">Status</th>
                                    <th class="px-6 py-3.5 border-b border-gray-800 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50 text-sm">
                                <?php foreach ($recent_all as $item): ?>
                                    <tr class="hover:bg-dark-800/40 transition-colors">
                                        <td class="px-6 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full <?= $item['member_type'] === 'VIP' ? 'bg-gold-500/20 text-gold-400 border border-gold-500/40' : 'bg-blue-500/15 text-blue-400 border border-blue-500/30' ?> flex items-center justify-center font-bold text-xs shrink-0">
                                                    <?= htmlspecialchars(getInitials($item['name'])) ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-white leading-tight"><?= htmlspecialchars($item['name']) ?></p>
                                                    <p class="text-[11px] text-gray-500 font-mono">ID: <?= htmlspecialchars($item['id']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3.5 text-gray-300 text-xs">
                                            <i class="fa-brands fa-whatsapp text-emerald-400 mr-1"></i> <?= htmlspecialchars($item['phone']) ?>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <p class="text-xs font-bold text-gray-200">Rp <?= number_format($item['balance'], 0, ',', '.') ?></p>
                                            <?php if ($item['member_type'] === 'VIP'): ?>
                                                <p class="text-[10px] text-gold-500 font-semibold"><?= number_format($item['points']) ?> Poin</p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <?php if ($item['member_type'] === 'VIP'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-gold-500/15 text-gold-400 border border-gold-500/30">
                                                    <i class="fa-solid fa-crown text-[9px]"></i> VIP Member
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30">
                                                    Reguler
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-3.5 text-right">
                                            <?php if ($item['member_type'] === 'VIP'): ?>
                                                <a href="print_card.php?id=<?= urlencode($item['id']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-dark-800 hover:bg-gold-500 text-gray-300 hover:text-black border border-gray-700 text-xs font-bold transition-colors">
                                                    <i class="fa-solid fa-print"></i> Kartu
                                                </a>
                                            <?php else: ?>
                                                <a href="regular_members" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-dark-800 hover:bg-gray-700 text-gray-300 border border-gray-700 text-xs font-bold transition-colors">
                                                    Detail
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($recent_all)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                            Belum ada data member yang terdaftar.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kolom Kanan (1/3): Pipeline Upgrade VIP & Paket Aktif -->
                <div class="space-y-6">
                    
                    <!-- Antrean Upgrade VIP (Deals CRM) -->
                    <div class="glass-panel rounded-2xl p-6 border border-gray-800/90">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-bolt text-gold-500"></i> Pipeline Upgrade VIP
                                </h3>
                                <p class="text-xs text-gray-400">Permintaan upgrade member reguler</p>
                            </div>
                            <span class="bg-gold-500/20 text-gold-400 border border-gold-500/30 text-xs font-extrabold px-2.5 py-1 rounded-full">
                                <?= $pending_requests_count ?> Antrean
                            </span>
                        </div>

                        <?php if (empty($pending_list)): ?>
                            <div class="p-6 rounded-xl bg-dark-900/60 border border-gray-800/80 text-center">
                                <i class="fa-solid fa-circle-check text-2xl text-emerald-500/60 mb-2 block"></i>
                                <p class="text-xs text-gray-400 font-medium">Semua pengajuan upgrade VIP telah diproses.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($pending_list as $req): ?>
                                    <div class="p-3.5 rounded-xl bg-dark-900/80 border border-gray-800 hover:border-gold-500/40 transition-all flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold text-xs shrink-0">
                                                <?= htmlspecialchars(getInitials($req['name'])) ?>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($req['name']) ?></p>
                                                <p class="text-[11px] text-gray-400"><?= htmlspecialchars($req['phone']) ?></p>
                                            </div>
                                        </div>
                                        <a href="add_member?request_id=<?= $req['id'] ?>&name=<?= urlencode($req['name']) ?>&phone=<?= urlencode($req['phone']) ?>&balance=<?= urlencode($req['regular_balance']) ?>" 
                                           class="px-3 py-1.5 rounded-lg bg-gold-500 hover:bg-gold-400 text-black font-extrabold text-xs shrink-0 transition-colors">
                                            Proses
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Daftar Paket Membership Gym -->
                    <div class="glass-panel rounded-2xl p-6 border border-gray-800/90">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-dumbbell text-gold-500"></i> Katalog Paket Gym
                                </h3>
                                <p class="text-xs text-gray-400">Perbandingan harga Reguler vs VIP</p>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <?php foreach ($packages as $pkg): ?>
                                <div class="p-3 rounded-xl bg-dark-900/60 border border-gray-800/80 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-white"><?= htmlspecialchars($pkg['name']) ?></p>
                                        <p class="text-[10px] text-gray-500">Normal: <span class="line-through">Rp <?= number_format($pkg['price_normal'], 0, ',', '.') ?></span></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-extrabold text-gold-400">Rp <?= number_format($pkg['price_member'], 0, ',', '.') ?></p>
                                        <p class="text-[10px] text-emerald-400 font-semibold">+<?= (int)$pkg['points_reward'] ?> Poin VIP</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

            </div>
            
            <footer class="pt-6 pb-8 text-center text-xs text-gray-600">
                &copy; <?= date('Y') ?> Gym Goon CRM & Management System.
            </footer>
        </div>
    </main>

    <!-- Chart.js Configuration -->
    <script>
        // 1. Bar Chart Pertumbuhan Bulanan
        const ctxBar = document.getElementById('monthlyGrowthChart').getContext('2d');
        const monthlyVipData = <?= json_encode(array_values($monthly_vip)) ?>;
        const monthlyRegData = <?= json_encode(array_values($monthly_reg)) ?>;

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [
                    {
                        label: 'Member VIP',
                        data: monthlyVipData,
                        backgroundColor: '#D4AF37',
                        borderRadius: 6,
                        barPercentage: 0.65
                    },
                    {
                        label: 'Member Reguler',
                        data: monthlyRegData,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        barPercentage: 0.65
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { color: '#9ca3af', usePointStyle: true, boxWidth: 8, font: { family: 'Outfit', size: 11 } }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af', font: { family: 'Outfit', size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#6b7280', font: { family: 'Outfit', size: 11 } },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    }
                }
            }
        });

        // 2. Donut Chart Komposisi Member
        const ctxDonut = document.getElementById('memberCompositionChart').getContext('2d');
        const vipCount = <?= $total_vip ?>;
        const regCount = <?= $reg_non_pending ?>;
        const reqCount = <?= $pending_requests_count ?>;
        const hasData = (vipCount + regCount + reqCount) > 0;

        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['Member VIP', 'Member Reguler', 'Menunggu Upgrade'],
                datasets: [{
                    data: hasData ? [vipCount, regCount, reqCount] : [1, 1, 0],
                    backgroundColor: ['#D4AF37', '#3b82f6', '#fbbf24'],
                    borderColor: '#111111',
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>
