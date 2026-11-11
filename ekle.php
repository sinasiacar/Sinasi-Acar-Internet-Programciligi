<?php
session_start();
$masa_id = isset($_GET['masa_id']) ? (int)$_GET['masa_id'] : 0;

if (!isset($_SESSION['masalar'][$masa_id])) {
    header("Location: index.php?sayfa=masalar");
    exit;
}

$menu = isset($_SESSION['menu']) ? $_SESSION['menu'] : [];

// İŞLEMLER
if (isset($_GET['islem'])) {
    
    // 1. Ürün Ekle
    if ($_GET['islem'] == 'urun_ekle' && isset($_GET['urun']) && isset($_GET['fiyat'])) {
        $_SESSION['masalar'][$masa_id]['siparisler'][] = [
            "ad" => $_GET['urun'], 
            "fiyat" => (float)$_GET['fiyat']
        ];
        $_SESSION['masalar'][$masa_id]['tutar'] += (float)$_GET['fiyat'];
        
        if ($_SESSION['masalar'][$masa_id]['durum'] == 'Bos') {
            $_SESSION['masalar'][$masa_id]['durum'] = 'Dolu';
        }
        if ($_SESSION['masalar'][$masa_id]['kisi'] < 1) {
            $_SESSION['masalar'][$masa_id]['kisi'] = 1;
        }
        
        header("Location: ekle.php?masa_id=" . $masa_id);
        exit;
    }
    
    // 2. Kişi Sayısı Güncelleme
    if ($_GET['islem'] == 'admin_guncelle' && isset($_POST['kisi'])) {
        $yeni_kisi = (int)$_POST['kisi'];
        if ($yeni_kisi < 1) { $yeni_kisi = 1; }
        
        $_SESSION['masalar'][$masa_id]['kisi'] = $yeni_kisi;
        
        header("Location: ekle.php?masa_id=" . $masa_id);
        exit;
    }

    // 3. Hesabı Al (Kırmızı - Kapat ve Masalara Dön)
    if ($_GET['islem'] == 'hesap_al') {
        $_SESSION['masalar'][$masa_id]['durum'] = 'Odendi'; 
        
        $siparis_metni = count($_SESSION['masalar'][$masa_id]['siparisler']) . " Çeşit Ürün";
        $_SESSION['gecmis'][] = [
            "masa" => $_SESSION['masalar'][$masa_id]['masa_no'],
            "gelis" => date('H:i'),
            "siparisler" => $siparis_metni,
            "tutar" => $_SESSION['masalar'][$masa_id]['tutar']
        ];
        
        header("Location: index.php?sayfa=masalar");
        exit;
    }

    // 4. Sipariş Ekle / Hesabı Açık Tut (Sarı - Masalara Dön)
    if ($_GET['islem'] == 'acik_tut') {
        if ($_SESSION['masalar'][$masa_id]['durum'] == 'Bos' && $_SESSION['masalar'][$masa_id]['tutar'] > 0) {
            $_SESSION['masalar'][$masa_id]['durum'] = 'Dolu';
        }
        header("Location: index.php?sayfa=masalar");
        exit;
    }
    
    // 5. Masayı Tamamen Boşalt
    if ($_GET['islem'] == 'temizle') {
        $_SESSION['masalar'][$masa_id]['siparisler'] = [];
        $_SESSION['masalar'][$masa_id]['tutar'] = 0;
        $_SESSION['masalar'][$masa_id]['kisi'] = 0;
        $_SESSION['masalar'][$masa_id]['durum'] = 'Bos';
        header("Location: index.php?sayfa=masalar");
        exit;
    }
}

$masa = $_SESSION['masalar'][$masa_id];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sipariş Ekranı</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-sari {
            background-color: #f59e0b !important;
            color: white !important;
        }
        .btn-sari:hover {
            background-color: #d97706 !important;
        }
        
        /* Ürünlerin boyutunu küçülttük ve 2'li düzen sağladık */
        .products-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
        }
        .product-btn {
            flex-direction: row !important;
            align-items: center !important;
            padding: 8px !important;
            gap: 10px !important;
        }
        .product-img {
            width: 55px !important;
            height: 55px !important;
            margin-bottom: 0 !important;
            flex-shrink: 0;
        }
        .product-info {
            text-align: left !important;
        }
        .p-name {
            font-size: 13px !important;
            margin-bottom: 3px !important;
        }
        .p-price {
            font-size: 12px !important;
        }

        /* Adisyon içindeki sipariş listesi (2 sütunlu, daha kompakt) */
        .order-list {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
            align-content: flex-start;
            max-height: 250px;
            overflow-y: auto;
        }
        .order-list li {
            background: #f9fafb;
            border: 1px solid #e5e7eb !important;
            border-radius: 4px;
            padding: 6px 8px !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            gap: 2px;
            font-size: 12px;
        }
    </style>
</head>
<body style="background-image: linear-gradient(rgba(239, 234, 226, 0.88), rgba(239, 234, 226, 0.88)), url('desen.jpg'); background-repeat: repeat; background-size: 300px; padding: 20px; height: auto; min-height: 100vh; overflow-y: auto; display: block;">
    
    <!-- Üst Başlık ve Ortalanmış Masalara Dön Butonu -->
    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; margin-bottom: 20px; gap: 8px;">
        <h2 style="color: #111b21; margin: 0; font-size: 22px;">🍣 <?php echo htmlspecialchars($masa['masa_no']); ?> - Admin Terminali</h2>
        <a href="index.php?sayfa=masalar" class="btn btn-secondary" style="width: auto; padding: 6px 18px; font-size: 13px;">⬅ Masalara Dön</a>
    </div>

    <div class="pos-layout" style="max-width: 1100px; margin: 0 auto; height: auto; gap: 15px;">
        <!-- SOL: Menü (Küçültülmüş 2 Sütunlu Bloklar) -->
        <div class="menu-section" style="padding: 15px;">
            <?php if (empty($menu)): ?>
                <div style="background: #fef2f2; color: #b91c1c; padding: 15px; border-radius: 6px;">Menü yüklenemedi!</div>
            <?php else: ?>
                <?php foreach ($menu as $kategori => $urunler): ?>
                    <?php 
                        $gorunur = false;
                        foreach($urunler as $u) { if (!isset($u['gizli']) || !$u['gizli']) $gorunur = true; }
                        if (!$gorunur) continue; 
                    ?>
                    <h3 style="margin: 10px 0 8px 0; color: #047857; border-bottom: 2px solid #00a884; padding-bottom:4px; font-size: 15px;">
                        <?php echo $kategori; ?>
                    </h3>
                    <div class="products-grid">
                        <?php foreach ($urunler as $urun): ?>
                            <?php if (isset($urun['gizli']) && $urun['gizli']) continue; ?>
                            <a href="ekle.php?masa_id=<?php echo $masa_id; ?>&islem=urun_ekle&urun=<?php echo urlencode($urun['ad']); ?>&fiyat=<?php echo $urun['fiyat']; ?>" class="product-btn">
                                <img src="<?php echo htmlspecialchars($urun['foto']); ?>" alt="foto" class="product-img">
                                <div class="product-info">
                                    <span class="p-name"><?php echo htmlspecialchars($urun['ad']); ?></span>
                                    <span class="p-price"><?php echo number_format($urun['fiyat'], 2); ?> ₺</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- SAĞ: Adisyon -->
        <div class="adisyon-section" style="min-width: 300px;">
            <div class="adisyon-paper" style="padding: 15px;">
                <h3 style="text-align: center; border-bottom: 1px dashed #ccc; padding-bottom: 8px; margin-bottom: 12px; font-size: 16px;">MASA ADİSYONU</h3>
                
                <!-- Kişi Sayısı Güncelleme Formu -->
                <form action="ekle.php?masa_id=<?php echo $masa_id; ?>&islem=admin_guncelle" method="POST" style="background:#f9fafb; padding:8px; border-radius:6px; margin-bottom:12px; border:1px solid #e5e7eb;">
                    <div style="margin-bottom:6px;">
                        <label style="font-size:11px; font-weight:bold; color:#4b5563; display:block; margin-bottom:2px;">Kişi Sayısı:</label>
                        <input type="number" name="kisi" min="1" value="<?php echo $masa['kisi'] == 0 ? 1 : $masa['kisi']; ?>" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px; font-size: 13px;">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="width:100%; padding:5px; font-size:11px;">Kişi Sayısını Güncelle</button>
                </form>

                <ul class="order-list">
                    <?php if (empty($masa['siparisler'])): ?>
                        <li style="grid-column: span 2; text-align: center; color: #888; background: none; border: none !important;">Sipariş yok.</li>
                    <?php else: ?>
                        <?php foreach ($masa['siparisler'] as $siparis): ?>
                            <li>
                                <span style="font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($siparis['ad']); ?></span>
                                <span style="color: #047857; font-weight: bold;"><?php echo number_format($siparis['fiyat'], 2); ?> ₺</span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <div class="total-price" style="font-size: 20px; margin-top: 10px;">TOPLAM: <?php echo number_format($masa['tutar'], 2, ',', '.'); ?> ₺</div>

                <div style="display: flex; flex-direction:column; gap: 8px; margin-top: 15px;">
                    <?php if ($masa['tutar'] > 0): ?>
                        <!-- Sarı Buton -->
                        <a href="ekle.php?masa_id=<?php echo $masa_id; ?>&islem=acik_tut" class="btn btn-sari" style="padding: 8px; font-size: 13px;">Sipariş Ekle / Masayı Açık Tut (Sarı)</a>
                        
                        <!-- Kırmızı Buton -->
                        <a href="ekle.php?masa_id=<?php echo $masa_id; ?>&islem=hesap_al" class="btn btn-primary" style="background:#dc2626; padding: 8px; font-size: 13px;">Hesabı Al (Kırmızı)</a>
                    <?php endif; ?>
                    
                    <!-- Beyaz/Gri Buton -->
                    <a href="ekle.php?masa_id=<?php echo $masa_id; ?>&islem=temizle" class="btn btn-secondary" style="background:#4b5563; padding: 8px; font-size: 13px;">Boşalt (Beyaz)</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>