<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

$message = '';
$messageType = '';

$request_id = $_GET['request_id'] ?? '';
$req_name = $_GET['name'] ?? '';
$req_phone = $_GET['phone'] ?? '';
$req_balance = $_GET['balance'] ?? '999999.00';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = sanitize($_POST['id']);
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $pin = $_POST['pin'];
    $req_id_post = $_POST['request_id'] ?? '';
    $req_balance_post = $_POST['balance'] ?? '999999.00';
    
    if (empty($id) || empty($name) || empty($phone) || empty($pin)) {
        $message = "Semua kolom harus diisi.";
        $messageType = "error";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            $message = "ID Member / No Kartu sudah terdaftar. Silakan gunakan ID lain.";
            $messageType = "error";
        } elseif (strlen($pin) < 4 || strlen($pin) > 6 || !ctype_digit($pin)) {
            $message = "PIN harus berupa 4-6 angka.";
            $messageType = "error";
        } else {
            $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO members (id, name, phone, pin, points, balance) VALUES (?, ?, ?, ?, 0, ?)");
            if ($stmt->execute([$id, $name, $phone, $hashed_pin, $req_balance_post])) {
                
                if (!empty($req_id_post)) {
                    $stmtReq = $pdo->prepare("SELECT regular_member_id FROM vip_requests WHERE id = ?");
                    $stmtReq->execute([$req_id_post]);
                    $reg_id = $stmtReq->fetchColumn();
                    
                    if ($reg_id) {
                        $pdo->prepare("UPDATE vip_requests SET status = 'approved' WHERE id = ?")->execute([$req_id_post]);
                        $pdo->prepare("DELETE FROM regular_members WHERE id = ?")->execute([$reg_id]);
                    }
                }

                $message = "Member VIP berhasil ditambahkan!";
                $messageType = "success";
                
                $req_name = '';
                $req_phone = '';
                $request_id = '';
            } else {
                $message = "Terjadi kesalahan saat menyimpan data.";
                $messageType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member - Gym Goon</title>
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
        .btn-gold { background: linear-gradient(135deg, #FFDF73 0%, #D4AF37 100%); color: #000; transition: all 0.3s ease; }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(212, 175, 55, 0.4); }
        .input-premium { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease; }
        .input-premium:focus { border-color: #D4AF37; box-shadow: 0 0 0 4px rgba(212,175,55,0.1); outline: none; }
        
        /* Custom Scrollbar */
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
            <a href="vip_members" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gold-500/10 text-gold-500 font-bold border border-gold-500/20 transition-colors">
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

    <!-- Overlay for mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm" style="display: none;"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-black relative">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gold-500 rounded-full opacity-5 blur-[100px] pointer-events-none"></div>

        <!-- Top Header -->
        <header class="bg-dark-900/50 backdrop-blur-md border-b border-gray-800 p-4 lg:px-8 flex justify-between items-center z-10">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-bold text-white uppercase tracking-widest">Add VIP Member</h1>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="vip_members" class="bg-dark-800 hover:bg-dark-700 text-gray-400 hover:text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors border border-gray-700 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <div class="w-8 h-8 rounded-full bg-dark-800 border border-gold-500/30 flex items-center justify-center">
                    <i class="fa-solid fa-crown text-gold-500 text-sm"></i>
                </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-4 lg:p-8 z-10 flex items-center justify-center pt-8 lg:pt-0">
            
            <div class="w-full max-w-md">
                
                <?php if ($message): ?>
                    <div class="glass-panel border-<?= $messageType === 'success' ? 'green-500/30' : 'red-500/30' ?> rounded-2xl p-5 mb-8 text-center flex flex-col items-center">
                        <div class="w-12 h-12 <?= $messageType === 'success' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500' ?> rounded-full flex items-center justify-center mb-3">
                            <i class="fa-solid <?= $messageType === 'success' ? 'fa-check' : 'fa-xmark' ?> text-xl"></i>
                        </div>
                        <h3 class="<?= $messageType === 'success' ? 'text-green-400' : 'text-red-400' ?> font-bold mb-1"><?= $messageType === 'success' ? 'Success' : 'Error' ?></h3>
                        <p class="text-sm text-gray-400"><?= $message ?></p>
                    </div>
                <?php endif; ?>

                <div class="glass-panel rounded-3xl p-8 border border-gray-800 shadow-2xl">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-white mb-2">Create VIP Card</h2>
                        <p class="text-gray-500 text-sm">Generate access for a new VIP member.</p>
                    </div>

                    <form method="POST" action="add_member" class="space-y-5">
                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($request_id) ?>">
                        <input type="hidden" name="balance" value="<?= htmlspecialchars($req_balance) ?>">
                        
                        <div>
                            <label for="id" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">ID / No Kartu</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-id-card text-gray-500"></i>
                                </div>
                                <input type="text" name="id" id="id" required placeholder="Ex: VIP001"
                                       class="input-premium w-full rounded-xl pl-12 pr-4 py-3.5 text-white uppercase font-mono">
                            </div>
                            <p class="text-[10px] text-gray-600 mt-1">*Pastikan ID sesuai dengan kode QR yang dicetak di kartu fisik.</p>
                        </div>
                        
                        <div>
                            <label for="name" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-user text-gray-500"></i>
                                </div>
                                <input type="text" name="name" id="name" required placeholder="Nama Member" value="<?= htmlspecialchars($req_name) ?>"
                                       class="input-premium w-full rounded-xl pl-12 pr-4 py-3.5 text-white">
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">No. Handphone</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-phone text-gray-500"></i>
                                </div>
                                <input type="text" name="phone" id="phone" required placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($req_phone) ?>"
                                       class="input-premium w-full rounded-xl pl-12 pr-4 py-3.5 text-white">
                            </div>
                        </div>
                        
                        <div>
                            <label for="pin" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">PIN Keamanan Sementara</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-key text-gray-500"></i>
                                </div>
                                <input type="password" name="pin" id="pin" required placeholder="Min 4 angka" pattern="[0-9]{4,6}" maxlength="6" inputmode="numeric"
                                       class="input-premium w-full rounded-xl pl-12 pr-4 py-3.5 text-white">
                            </div>
                            <p class="text-[10px] text-gray-600 mt-1">*Member dapat mengubah PIN ini nanti.</p>
                        </div>

                        <button type="submit" class="btn-gold w-full py-4 mt-4 rounded-xl font-bold uppercase tracking-wider text-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-plus"></i> Buat Member VIP
                        </button>
                    </form>
                </div>
            </div>
            
        </div>
    </main>
</body>
</html>
