<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$is_guest = isRegularLoggedIn();

if ($is_guest) {
    $regular_id = $_SESSION['regular_id'];
    $stmt = $pdo->prepare("SELECT * FROM regular_members WHERE id = ?");
    $stmt->execute([$regular_id]);
    $member = $stmt->fetch();
    // Supaya UI tetap konsisten, set points = 0 karena regular tidak punya kolom points di DB
    $member['points'] = 0;
} else {
    $member_id = $_SESSION['member_id'];
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
}

$stmt = $pdo->query("SELECT * FROM packages ORDER BY price_normal ASC");
$packages = $stmt->fetchAll();

$message = '';
$messageType = '';

// Proses Request VIP
$has_pending_vip = false;
if ($is_guest) {
    if (isset($_POST['request_vip'])) {
        $req_name = trim($_POST['req_name']);
        $req_phone = trim($_POST['req_phone']);
        
        // Check if already requested
        $stmt = $pdo->prepare("SELECT id FROM vip_requests WHERE regular_member_id = ? AND status = 'pending'");
        $stmt->execute([$regular_id]);
        if ($stmt->fetch()) {
            $message = "Anda sudah mengajukan VIP. Silakan temui Admin di kasir.";
            $messageType = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO vip_requests (regular_member_id, name, phone) VALUES (?, ?, ?)");
            if ($stmt->execute([$regular_id, $req_name, $req_phone])) {
                $message = "Pengajuan VIP berhasil! Silakan temui Admin Gym di kasir untuk verifikasi.";
                $messageType = "success";
            }
        }
    }
    
    // Fetch pending request status
    $stmt = $pdo->prepare("SELECT id FROM vip_requests WHERE regular_member_id = ? AND status = 'pending'");
    $stmt->execute([$regular_id]);
    $has_pending_vip = (bool)$stmt->fetch();
}

// Proses penukaran poin (Hanya untuk VIP)
if (isset($_POST['redeem_package_id']) && !$is_guest) {
    $pkg_id = $_POST['redeem_package_id'];
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$pkg_id]);
    $pkg = $stmt->fetch();
    
    if ($pkg && $member['points'] >= $pkg['points_cost']) {
        $new_points = $member['points'] - $pkg['points_cost'];
        $update = $pdo->prepare("UPDATE members SET points = ? WHERE id = ?");
        $update->execute([$new_points, $member_id]);
        $member['points'] = $new_points; 
        $message = "Berhasil menukar poin dengan " . htmlspecialchars($pkg['name']) . "!";
        $messageType = "success";
    } else {
        $message = "Poin tidak mencukupi untuk menukar paket ini.";
        $messageType = "error";
    }
}

// Proses Pembelian
if (isset($_POST['buy_package_id'])) {
    $pkg_id = $_POST['buy_package_id'];
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$pkg_id]);
    $pkg = $stmt->fetch();
    
    if ($pkg) {
        $price_to_pay = $is_guest ? $pkg['price_normal'] : $pkg['price_member'];
        
        if ($member['balance'] >= $price_to_pay) {
            $new_balance = $member['balance'] - $price_to_pay;
            
            if ($is_guest) {
                // Update database untuk regular
                $update = $pdo->prepare("UPDATE regular_members SET balance = ? WHERE id = ?");
                $update->execute([$new_balance, $regular_id]);
                
                $member['balance'] = $new_balance;
                $message = "Berhasil membeli " . htmlspecialchars($pkg['name']) . " dengan Harga Normal!";
                $messageType = "success";
            } else {
                // Update database untuk VIP
                $new_points = $member['points'] + $pkg['points_reward'];
                $update = $pdo->prepare("UPDATE members SET balance = ?, points = ? WHERE id = ?");
                $update->execute([$new_balance, $new_points, $member_id]);
                
                $member['balance'] = $new_balance; 
                $member['points'] = $new_points; 
                
                $message = "Berhasil membeli " . htmlspecialchars($pkg['name']) . "! Anda mendapatkan +" . $pkg['points_reward'] . " Pts.";
                $messageType = "success";
            }
        } else {
            $message = "Saldo Anda tidak mencukupi.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $is_guest ? 'Dashboard' : 'VIP Dashboard' ?> - Gym Goon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { gold: { 400: '#FFDF73', 500: '#D4AF37', 600: '#AA8C2C', 700: '#8B7322' }, dark: { 900: '#050505', 800: '#111111', 700: '#1a1a1a' } } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #050505; color: #f5f5f5; padding-bottom: 80px; } /* padding for bottom nav */
        
        /* Hide scrollbar for clean look */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
        
        .glass-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .gold-card { background: linear-gradient(135deg, rgba(212,175,55,0.1) 0%, rgba(0,0,0,0) 100%); border: 1px solid rgba(212, 175, 55, 0.2); }
        .btn-gold { background: linear-gradient(135deg, #FFDF73 0%, #D4AF37 100%); color: #000; transition: all 0.3s ease; }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(212, 175, 55, 0.4); }
        .btn-outline-gold { border: 1px solid #D4AF37; color: #D4AF37; transition: all 0.3s ease; background: transparent; }
        .btn-outline-gold:hover { background: rgba(212, 175, 55, 0.1); transform: translateY(-2px); }
        
        /* Bottom Navigation Bar */
        .bottom-nav { background: rgba(5, 5, 5, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-top: 1px solid rgba(255,255,255,0.05); padding-bottom: env(safe-area-inset-bottom); }
        .nav-item { color: #666; transition: all 0.3s; }
        .nav-item.active { color: #D4AF37; }
        
        /* Animations */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.4s ease forwards; }
    </style>
    <script>
        if (document.cookie.indexOf("user_logged_in=1") === -1 && document.cookie.indexOf("regular_logged_in=1") === -1) {
            window.location.replace("login");
        }
        window.addEventListener("pageshow", function (event) {
            if (event.persisted && document.cookie.indexOf("user_logged_in=1") === -1 && document.cookie.indexOf("regular_logged_in=1") === -1) {
                window.location.replace("login");
            }
        });
    </script>
</head>
<body class="bg-black text-gray-100 min-h-screen relative">
    
    <!-- Header Background -->
    <div class="absolute top-0 left-0 w-full h-64 bg-dark-900 z-0 overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gold-500 rounded-full opacity-5 blur-[100px]"></div>
    </div>

    <!-- Main Content container -->
    <div class="relative z-10 max-w-lg mx-auto w-full px-4 pt-8 animate-fade-in">
        
        <!-- Top Bar -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-bold mb-1">
                    <?= $is_guest ? 'Regular Member' : 'VIP Member' ?>
                </p>
                <h1 class="text-2xl font-extrabold text-white">Hi, <?= htmlspecialchars($member['name']) ?>!</h1>
            </div>
            <img src="assets/img/k.png" alt="Gym Goon" class="w-10 opacity-80">
        </div>

        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'bg-green-900/30 text-green-400 border-green-500/30' : 'bg-red-900/30 text-red-400 border-red-500/30' ?> border rounded-2xl p-4 mb-6 text-sm font-semibold flex items-start gap-3 shadow-lg">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check text-green-500' : 'fa-triangle-exclamation text-red-500' ?> mt-0.5 text-lg"></i>
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <!-- Balance Card -->
        <div class="gold-card rounded-3xl p-6 mb-8 relative overflow-hidden shadow-2xl">
            <div class="absolute -right-10 -bottom-10 opacity-10">
                <i class="fa-solid fa-wallet text-9xl"></i>
            </div>
            
            <p class="text-gray-400 text-sm font-bold uppercase tracking-wider mb-2">Total Balance</p>
            <div class="flex items-baseline gap-2 mb-6">
                <span class="text-xl font-bold text-gold-500">Rp</span>
                <span class="text-4xl font-extrabold text-white tracking-tight"><?= number_format($member['balance'], 0, ',', '.') ?></span>
            </div>

            <?php if (!$is_guest): ?>
            <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gold-500/20 flex items-center justify-center">
                        <i class="fa-solid fa-star text-gold-500 text-xs"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-bold">Reward Points</p>
                        <p class="text-lg font-bold text-white"><?= number_format($member['points'], 0, ',', '.') ?> <span class="text-gold-500 text-xs">Pts</span></p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="pt-4 border-t border-white/10">
                <p class="text-xs text-gray-500"><i class="fa-solid fa-info-circle mr-1"></i> Reward Points hanya tersedia untuk VIP.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- VIP UPSELL BANNER (Hanya untuk Regular Member) -->
        <?php if ($is_guest): ?>
        <div x-data="{ showVipModal: false }">
            <div class="glass-card border-gold-500/30 rounded-3xl p-6 mb-8 relative overflow-hidden bg-gradient-to-br from-dark-900 to-black shadow-lg shadow-gold-500/10">
                <div class="absolute -right-8 -top-8 opacity-5">
                    <i class="fa-solid fa-crown text-9xl text-gold-500"></i>
                </div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-crown text-gold-500"></i>
                        <h2 class="text-gold-500 font-extrabold uppercase tracking-widest text-sm">Upgrade to VIP</h2>
                    </div>
                    <p class="text-white font-bold text-lg mb-4 leading-tight">Nikmati pengalaman nge-gym yang jauh lebih eksklusif!</p>
                    
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-start gap-2 text-sm text-gray-300">
                            <i class="fa-solid fa-check text-gold-500 mt-1 text-xs"></i>
                            <span><strong class="text-white">Harga Spesial:</strong> Diskon khusus untuk semua paket gym Hingga 25%!.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm text-gray-300">
                            <i class="fa-solid fa-check text-gold-500 mt-1 text-xs"></i>
                            <span><strong class="text-white">Reward Points:</strong> Kumpulkan poin & tukar paket GRATIS.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm text-gray-300">
                            <i class="fa-solid fa-check text-gold-500 mt-1 text-xs"></i>
                            <span><strong class="text-white">Kartu Eksklusif:</strong> Dapatkan ID Card "The Boss".</span>
                        </li>
                    </ul>

                    <?php if ($has_pending_vip): ?>
                        <div class="bg-gold-500/10 border border-gold-500/30 rounded-xl p-3 text-center">
                            <p class="text-gold-500 text-xs font-bold uppercase tracking-wider"><i class="fa-solid fa-clock mr-1"></i> Menunggu Verifikasi Admin</p>
                        </div>
                    <?php else: ?>
                        <button @click="showVipModal = true" class="w-full btn-gold py-3 rounded-xl font-bold uppercase tracking-wider text-xs transition-colors flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(212,175,55,0.3)]">
                            Ajukan Upgrade VIP <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal Request VIP -->
            <div x-show="showVipModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    
                    <!-- Background overlay -->
                    <div x-show="showVipModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <!-- Modal panel -->
                    <div x-show="showVipModal" @click.away="showVipModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-dark-900 border border-gray-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                        
                        <div class="px-6 pt-6 pb-4">
                            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-gold-500/20 rounded-full mb-4">
                                <i class="fa-solid fa-crown text-gold-500 text-xl"></i>
                            </div>
                            <h3 class="text-xl leading-6 font-bold text-white text-center mb-2" id="modal-title">Form Pengajuan VIP</h3>
                            <p class="text-sm text-gray-400 text-center mb-6">Pastikan data di bawah ini benar. Admin akan menggunakan data ini untuk mencetak kartu VIP Anda.</p>
                            
                            <form method="POST" action="index">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                                        <input type="text" name="req_name" required value="<?= htmlspecialchars($member['name']) ?>" class="w-full bg-black border border-gray-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nomor HP / WhatsApp</label>
                                        <input type="tel" name="req_phone" required value="<?= htmlspecialchars($member['phone']) ?>" class="w-full bg-black border border-gray-800 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500 transition-colors">
                                    </div>
                                </div>
                                
                                <div class="mt-8 flex gap-3">
                                    <button type="button" @click="showVipModal = false" class="flex-1 bg-transparent border border-gray-600 text-gray-300 py-3 rounded-xl font-bold uppercase tracking-wider text-xs hover:bg-gray-800 transition-colors">Batal</button>
                                    <button type="submit" name="request_vip" class="flex-1 btn-gold py-3 rounded-xl font-bold uppercase tracking-wider text-xs transition-colors">Ajukan Sekarang</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Available Packages -->
        <div class="flex justify-between items-end mb-4">
            <h2 class="text-xl font-bold text-white">Membership Packages</h2>
        </div>

        <div class="space-y-4 mb-8">
            <?php foreach ($packages as $pkg): ?>
                <?php
                    $is_discount = !$is_guest && $pkg['price_member'] < $pkg['price_normal'];
                    $price_to_pay = $is_guest ? $pkg['price_normal'] : $pkg['price_member'];
                ?>
                <div class="glass-card rounded-2xl p-5 relative overflow-hidden transition-all hover:bg-dark-800">
                    
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="text-lg font-bold text-white mb-1"><?= htmlspecialchars($pkg['name']) ?></h3>
                            <div class="flex items-center gap-3">
                                <?php if ($is_discount): ?>
                                    <span class="text-gray-500 line-through text-sm">Rp <?= number_format($pkg['price_normal'], 0, ',', '.') ?></span>
                                    <span class="text-gold-400 font-bold text-lg">Rp <?= number_format($price_to_pay, 0, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="text-white font-bold text-lg">Rp <?= number_format($pkg['price_normal'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($is_discount): ?>
                            <span class="bg-gold-500 text-black text-xs font-bold px-2 py-1 rounded-lg uppercase tracking-wider shadow-[0_0_10px_rgba(212,175,55,0.4)]">VIP Price</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$is_guest): ?>
                        <div class="inline-block bg-dark-900 border border-gray-800 rounded-lg px-2 py-1 mb-4">
                            <p class="text-xs text-gray-400"><i class="fa-solid fa-gift text-gold-500 mr-1"></i> Reward: +<span class="font-bold text-white"><?= number_format($pkg['points_reward']) ?> Pts</span></p>
                        </div>
                    <?php else: ?>
                        <div class="mb-4"></div>
                    <?php endif; ?>

                    <div class="flex gap-2">
                        <!-- Buy Button -->
                        <form method="POST" action="index" onsubmit="return confirm('Apakah Anda yakin ingin membeli <?= htmlspecialchars($pkg['name']) ?> seharga Rp <?= number_format($price_to_pay, 0, ',', '.') ?>?');" class="flex-1">
                            <input type="hidden" name="buy_package_id" value="<?= $pkg['id'] ?>">
                            <button type="submit" class="<?= $is_guest ? 'bg-gray-200 text-black hover:bg-white' : 'btn-gold' ?> w-full py-3 rounded-xl font-bold uppercase tracking-wider text-xs transition-colors flex items-center justify-center gap-2">
                                <i class="fa-solid fa-cart-shopping"></i> Purchase
                            </button>
                        </form>

                        <!-- Redeem Button (Only VIP) -->
                        <?php if (!$is_guest): ?>
                            <?php if ($member['points'] >= $pkg['points_cost']): ?>
                                <form method="POST" action="index" onsubmit="return confirm('Tukar <?= $pkg['points_cost'] ?> Poin untuk <?= htmlspecialchars($pkg['name']) ?>?');" class="flex-1">
                                    <input type="hidden" name="redeem_package_id" value="<?= $pkg['id'] ?>">
                                    <button type="submit" class="btn-outline-gold w-full py-3 rounded-xl font-bold uppercase tracking-wider text-xs flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-star"></i> Tukar (<?= $pkg['points_cost'] ?> Pts)
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="flex-1 py-3 rounded-xl font-bold uppercase tracking-wider text-xs border border-gray-700 text-gray-600 cursor-not-allowed flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-lock"></i> <?= $pkg['points_cost'] ?> Pts
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bottom App Bar -->
    <div class="bottom-nav fixed bottom-0 left-0 w-full z-50">
        <div class="max-w-lg mx-auto flex justify-between items-center px-8 py-3">
            <a href="index" class="nav-item active flex flex-col items-center gap-1">
                <i class="fa-solid fa-house text-xl mb-0.5"></i>
                <span class="text-[10px] font-bold uppercase tracking-widest">Home</span>
            </a>
            
            <?php if (!$is_guest): ?>
            <a href="profile" class="nav-item flex flex-col items-center gap-1">
                <i class="fa-regular fa-user text-xl mb-0.5"></i>
                <span class="text-[10px] font-bold uppercase tracking-widest">Profile</span>
            </a>
            <?php endif; ?>

            <a href="logout" class="nav-item flex flex-col items-center gap-1 text-gray-500 hover:text-red-400">
                <i class="fa-solid fa-arrow-right-from-bracket text-xl mb-0.5"></i>
                <span class="text-[10px] font-bold uppercase tracking-widest">Logout</span>
            </a>
        </div>
    </div>

</body>
</html>
