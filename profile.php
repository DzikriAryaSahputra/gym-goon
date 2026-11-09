<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$member_id = $_SESSION['member_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $new_pin = $_POST['new_pin'];
    
    if (empty($name) || empty($phone)) {
        $message = "Nama dan Nomor HP tidak boleh kosong.";
        $messageType = "error";
    } else {
        if (!empty($new_pin)) {
            if (strlen($new_pin) < 4 || strlen($new_pin) > 6 || !ctype_digit($new_pin)) {
                $message = "PIN harus berupa 4-6 angka.";
                $messageType = "error";
            } else {
                $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE members SET name = ?, phone = ?, pin = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $hashed_pin, $member_id]);
                $message = "Profil dan PIN berhasil diperbarui.";
                $messageType = "success";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE members SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $member_id]);
            $message = "Profil berhasil diperbarui.";
            $messageType = "success";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profile - Gym Goon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { gold: { 400: '#FFDF73', 500: '#D4AF37', 600: '#AA8C2C', 700: '#8B7322' }, dark: { 900: '#050505', 800: '#111111', 700: '#1a1a1a' } } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #050505; color: #f5f5f5; padding-bottom: 80px; }
        
        .glass-card { background: rgba(26, 26, 26, 0.4); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-gold { background: linear-gradient(135deg, #FFDF73 0%, #D4AF37 100%); color: #000; transition: all 0.3s ease; }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(212, 175, 55, 0.4); }
        .input-premium { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease; }
        .input-premium:focus { border-color: #D4AF37; box-shadow: 0 0 0 4px rgba(212,175,55,0.1); outline: none; }
        
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
                    Settings
                </p>
                <h1 class="text-2xl font-extrabold text-white">Profile</h1>
            </div>
            <img src="assets/img/k.png" alt="Gym Goon" class="w-10 opacity-80">
        </div>

        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'bg-green-900/30 text-green-400 border-green-500/30' : 'bg-red-900/30 text-red-400 border-red-500/30' ?> border rounded-2xl p-4 mb-6 text-sm font-semibold flex items-start gap-3 shadow-lg">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check text-green-500' : 'fa-triangle-exclamation text-red-500' ?> mt-0.5 text-lg"></i>
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <!-- Profile Picture & ID -->
        <div class="glass-card rounded-3xl p-6 mb-8 text-center flex flex-col items-center shadow-lg relative overflow-hidden">
            <div class="w-24 h-24 bg-dark-800 rounded-full border-2 border-gold-500 flex items-center justify-center mb-4 shadow-[0_0_20px_rgba(212,175,55,0.2)]">
                <i class="fa-regular fa-user text-4xl text-gold-500"></i>
            </div>
            <h2 class="text-xl font-bold text-white mb-1"><?= htmlspecialchars($member['name']) ?></h2>
            <p class="text-xs text-gray-500 font-mono tracking-widest bg-dark-900 px-3 py-1 rounded border border-gray-800">VIP ID: <?= htmlspecialchars($member['id']) ?></p>
        </div>

        <!-- Edit Form -->
        <div class="glass-card rounded-3xl p-6 mb-8 shadow-lg">
            <h2 class="text-lg font-bold text-white uppercase tracking-wider mb-6 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-gold-500"></i> Edit Informasi
            </h2>

            <form method="POST" action="profile" class="space-y-5">
                <div>
                    <label for="name" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-id-badge text-gray-500"></i>
                        </div>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($member['name']) ?>" required
                               class="input-premium w-full rounded-xl pl-12 pr-4 py-3.5 text-white">
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nomor Handphone</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-phone text-gray-500"></i>
                        </div>
                        <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($member['phone']) ?>" required
                               class="input-premium w-full rounded-xl pl-12 pr-4 py-3.5 text-white">
                    </div>
                </div>
                
                <hr class="border-gray-800 my-6">

                <div>
                    <label for="new_pin" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Ganti PIN (Opsional)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-500"></i>
                        </div>
                        <input type="password" name="new_pin" id="new_pin" placeholder="Biarkan kosong jika tidak diganti" pattern="[0-9]{4,6}" maxlength="6" inputmode="numeric"
                               class="input-premium w-full rounded-xl pl-12 pr-4 py-3.5 text-white">
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full py-4 mt-4 rounded-xl font-bold uppercase tracking-wider text-sm shadow-[0_0_20px_rgba(212,175,55,0.2)] flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Bottom App Bar -->
    <div class="bottom-nav fixed bottom-0 left-0 w-full z-50">
        <div class="max-w-lg mx-auto flex justify-between items-center px-8 py-3">
            <a href="index" class="nav-item flex flex-col items-center gap-1 hover:text-white">
                <i class="fa-solid fa-house text-xl mb-0.5"></i>
                <span class="text-[10px] font-bold uppercase tracking-widest">Home</span>
            </a>
            
            <a href="profile" class="nav-item active flex flex-col items-center gap-1">
                <i class="fa-regular fa-user text-xl mb-0.5"></i>
                <span class="text-[10px] font-bold uppercase tracking-widest">Profile</span>
            </a>

            <a href="logout" class="nav-item flex flex-col items-center gap-1 hover:text-red-400">
                <i class="fa-solid fa-arrow-right-from-bracket text-xl mb-0.5"></i>
                <span class="text-[10px] font-bold uppercase tracking-widest">Logout</span>
            </a>
        </div>
    </div>

</body>
</html>
