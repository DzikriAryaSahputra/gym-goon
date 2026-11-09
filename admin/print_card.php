<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

if (!isset($_GET['id'])) {
    die("ID Member tidak ditemukan.");
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    die("Data member tidak ditemukan.");
}

// Generate the QR Code URL exactly as the existing system does
$scan_url = "https://gym.tezworks.my.id/login?id=" . urlencode($member['id']);
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&color=D4AF37&bgcolor=1a1a1a&data=" . urlencode($scan_url);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu VIP - <?= htmlspecialchars($member['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Outfit:wght@300;400;600;700&display=swap');
        
        body { 
            background-color: #f0f0f0; 
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: flex-start;
            gap: 20px;
        }

        .serif-text {
            font-family: 'Cinzel', serif;
        }

        /* Standard CR80 ID Card dimensions: 54mm x 86mm */
        .id-card {
            width: 54mm;
            height: 86mm;
            background: linear-gradient(135deg, #050505 0%, #1a1a1a 100%);
            border-radius: 4px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            box-sizing: border-box;
            color: #D4AF37;
            /* To make layout easier, we scale it virtually using CSS */
            /* But CSS mm are absolute, so we just use mm/px ratios */
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Glass / Stripe effect background overlay */
        .card-bg-overlay {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 45%,
                rgba(255, 255, 255, 0.03) 48%,
                rgba(255, 255, 255, 0.05) 50%,
                rgba(255, 255, 255, 0.03) 52%,
                transparent 55%
            );
            transform: rotate(-15deg);
            pointer-events: none;
            z-index: 1;
        }

        /* Ensure content stays above the background */
        .card-content {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        /* Front Card Styles */
        .front-top {
            padding: 4mm 6mm;
            text-align: center;
            position: relative;
        }
        
        .front-top::after {
            content: '';
            position: absolute;
            top: 2mm;
            right: 2mm;
            width: 65%;
            height: 12mm;
            border-top: 1px solid #D4AF37;
            border-right: 1px solid #D4AF37;
        }

        .front-logo {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5mm;
        }
        .front-logo img {
            max-width: 100%;
            max-height: 35mm;
            object-fit: contain;
            margin: 0 auto;
            filter: drop-shadow(0 0 10px rgba(212,175,55,0.4));
        }

        .front-bottom {
            padding: 4mm 6mm;
            position: relative;
        }

        .front-bottom::before {
            content: '';
            position: absolute;
            bottom: 2mm;
            left: 2mm;
            width: 65%;
            height: 12mm;
            border-bottom: 1px solid #D4AF37;
            border-left: 1px solid #D4AF37;
        }

        /* Back Card Styles */
        .back-top {
            padding: 3mm 4mm;
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2mm;
        }
        
        .back-top img {
            height: 8mm;
            filter: drop-shadow(0 0 5px rgba(212,175,55,0.4));
        }

        .back-qr-section {
            text-align: center;
            padding: 0 4mm;
            margin-bottom: 2mm;
        }
        
        .back-qr-section img {
            width: 26mm;
            height: 26mm;
            margin: 0 auto;
            border: 1px solid #D4AF37;
            border-radius: 2px;
            padding: 1mm;
            background: #1a1a1a;
        }

        .back-terms {
            padding: 0 4mm;
            font-size: 1.6mm;
            line-height: 1.2;
            color: #D4AF37;
        }

        .back-footer {
            padding: 2mm 4mm;
            margin-top: auto;
            font-size: 1.7mm;
            color: #D4AF37;
            display: flex;
            flex-direction: column;
            gap: 0.5mm;
        }
        
        .back-footer div {
            display: flex;
            align-items: center;
            gap: 1.5mm;
        }

        /* Action Buttons */
        .print-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 10px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body {
                background: none;
                padding: 10mm; /* padding from edge of A4 */
                gap: 10mm; /* space between cards */
                align-items: flex-start;
                flex-direction: row; /* Print side by side if possible */
                flex-wrap: wrap;
            }
            .print-actions {
                display: none;
            }
            .id-card {
                box-shadow: none; /* Shadow might print weirdly */
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="print-actions">
        <button onclick="window.print()" class="bg-gold-500 hover:bg-gold-600 text-black font-bold py-2 px-6 rounded-lg shadow-lg flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Cetak Kartu
        </button>
        <p class="text-xs text-gray-500 mt-2 text-center">Gunakan browser Chrome/Edge.<br>Aktifkan "Background graphics" saat print.</p>
    </div>

    <!-- FRONT CARD -->
    <div class="id-card">
        <div class="card-bg-overlay"></div>
        <div class="card-content flex flex-col justify-between">
            
            <div class="front-top">
                <p class="serif-text text-[3mm] tracking-widest uppercase mb-1">The Boss</p>
                <h1 class="serif-text text-[4.5mm] tracking-widest uppercase font-bold text-white">VIP Member</h1>
            </div>

            <div class="front-logo">
                <!-- Fallback to k.png -->
                <img src="../assets/img/k.png" alt="Gym Goon Logo">
            </div>

            <div class="front-bottom">
                <h2 class="serif-text text-[4.5mm] font-bold text-gold-500 tracking-wider mb-1 leading-tight uppercase">
                    <?= htmlspecialchars($member['name']) ?>
                </h2>
                <p class="text-[2.5mm] tracking-widest uppercase text-white/80">Membership ID : <?= htmlspecialchars($member['id']) ?></p>
            </div>

        </div>
    </div>

    <!-- BACK CARD -->
    <div class="id-card">
        <div class="card-bg-overlay"></div>
        <div class="card-content flex flex-col">
            
            <div class="back-top">
                <img src="../assets/img/k.png" alt="Gym Goon Logo">
            </div>

            <div class="back-qr-section">
                <h3 class="serif-text text-[3mm] font-bold tracking-widest uppercase text-gold-500 mb-2">Scan For VIP Access</h3>
                <img src="<?= $qr_image_url ?>" alt="QR Code">
            </div>

            <div class="back-terms">
                <p class="text-gold-500 mb-0.5" style="font-size: 2mm;">TERMS & CONDITIONS:</p>
                <ol class="list-decimal pl-[3mm] m-0">
                    <li>KARTU INI ADALAH MILIK GYM GOON DAN EKSKLUSIF UNTUK VIP MEMBER.</li>
                    <li>KARTU WAJIB DIBAWA DAN TIDAK DAPAT DIPINDAHTANGANKAN.</li>
                    <li>MANAJEMEN TIDAK BERTANGGUNG JAWAB ATAS BARANG PRIBADI YANG HILANG.</li>
                    <li>KEHILANGAN KARTU DIKENAKAN BIAYA PENGGANTIAN.</li>
                </ol>
            </div>

            <div class="back-footer mt-2 pb-[2mm]">
                <div>
                    <i class="fa-solid fa-phone"></i>
                    <span>+62 845-976-123</span>
                </div>
                <div>
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Di Atas Tanah Di Bawah Langit</span>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
