<?php
session_start();

// Eski menüyü zorla silip YEPYENİ TÜM ÜRÜNLERİ getiren V8 Sıfırlayıcı
if (!isset($_SESSION['sistem_guncellendi_v8'])) {
    unset($_SESSION['menu']);
    unset($_SESSION['dolap']);
    $_SESSION['sistem_guncellendi_v8'] = true;
}

if (!isset($_SESSION['onay_bekleyenler'])) { $_SESSION['onay_bekleyenler'] = []; }

// 1. YENİ İSTENEN TAM MENÜ
if (!isset($_SESSION['menu'])) {
    $_SESSION['menu'] = [
        'Sushi & Deniz Ürünleri' => [
            ["ad" => "Somon (Sashimi)", "fiyat" => 180, "malzemeler" => "Taze Somon Dilimleri", "foto" => "https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=150&h=150&fit=crop", "gizli" => false],
            ["ad" => "Yılan Balığı (Unagi)", "fiyat" => 220, "malzemeler" => "Tatlı Soya Soslu Izgara Yılan Balığı", "foto" => "https://images.unsplash.com/photo-1553621042-f6e147245754?w=150&h=150&fit=crop", "gizli" => false]
        ],
        'Aparatifler' => [
            ["ad" => "Sade Pilav", "fiyat" => 50, "malzemeler" => "Buharda Pişmiş Pirinç", "foto" => "https://images.unsplash.com/photo-1536521642388-441263f88a61?w=150&h=150&fit=crop", "gizli" => false],
            ["ad" => "Yosun Salatası", "fiyat" => 75, "malzemeler" => "Deniz Yosunu (Wakame)", "foto" => "https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=150&h=150&fit=crop", "gizli" => false]
        ],
        'İçecekler' => [
            ["ad" => "Cola", "fiyat" => 40, "malzemeler" => "Kutu Cola 330ml", "foto" => "https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=150&h=150&fit=crop", "gizli" => false],
            ["ad" => "Fanta", "fiyat" => 40, "malzemeler" => "Kutu Fanta 330ml", "foto" => "https://images.unsplash.com/photo-1624517452488-04869289c4ca?w=150&h=150&fit=crop", "gizli" => false],
            ["ad" => "Yeşil Çay", "fiyat" => 35, "malzemeler" => "Demleme Japon Yeşil Çayı", "foto" => "https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=150&h=150&fit=crop", "gizli" => false]
        ]
    ];
}

// 2. YENİ DOLAP (STOK) SİSTEMİ
if (!isset($_SESSION['dolap'])) {
    $_SESSION['dolap'] = [
        "Somon Balığı" => ["urun" => "Somon Balığı", "stok" => 25, "birim" => "Porsiyon", "uyari_siniri" => 5],
        "Yılan Balığı" => ["urun" => "Yılan Balığı", "stok" => 15, "birim" => "Porsiyon", "uyari_siniri" => 5],
        "Pirinç" => ["urun" => "Pirinç", "stok" => 40, "birim" => "Porsiyon", "uyari_siniri" => 10],
        "Yosun (Nori)" => ["urun" => "Yosun (Nori)", "stok" => 50, "birim" => "Adet", "uyari_siniri" => 15],
        "Kola" => ["urun" => "Kola", "stok" => 30, "birim" => "Kutu", "uyari_siniri" => 10],
        "Fanta" => ["urun" => "Fanta", "stok" => 30, "birim" => "Kutu", "uyari_siniri" => 10],
        "Yeşil Çay" => ["urun" => "Yeşil Çay", "stok" => 20, "birim" => "Fincan", "uyari_siniri" => 5]
    ];
}

// MASALAR
if (!isset($_SESSION['masalar'])) {
    $_SESSION['masalar'] = [];
    for ($i = 1; $i <= 13; $i++) { $_SESSION['masalar'][$i] = ["id" => $i, "masa_no" => "Salon " . $i, "bolum" => "Salon", "durum" => "Bos", "kisi" => 0, "tutar" => 0, "siparisler" => []]; }
    for ($i = 14; $i <= 25; $i++) { 
        $teras_no = $i - 13; 
        $_SESSION['masalar'][$i] = ["id" => $i, "masa_no" => "Teras " . $teras_no, "bolum" => "Teras", "durum" => "Bos", "kisi" => 0, "tutar" => 0, "siparisler" => []]; 
    }
}
if (!isset($_SESSION['gecmis'])) { $_SESSION['gecmis'] = []; }

// -------------------------------------------------------------
// ARKA PLAN İŞLEMLERİ 
// -------------------------------------------------------------

// Müşteri Masa Seçimi (Session'a Kaydetme)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['masa_secimi_yapildi'])) {
    $_SESSION['aktif_musteri_masasi'] = (int)$_POST['masa_id'];
    header("Location: index.php?sayfa=musteri_siparis"); exit;
}
if (isset($_GET['islem']) && $_GET['islem'] == 'masa_degistir') {
    unset($_SESSION['aktif_musteri_masasi']);
    header("Location: index.php?sayfa=musteri_siparis"); exit;
}

// A. Müşteri Siparişi Gönderirse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['musteri_siparis_gonder'])) {
    $m_id = (int)$_POST['masa_id']; $kisi = (int)$_POST['kisi']; $odeme = $_POST['odeme'];
    $alinan_urunler = []; $toplam_tutar = 0;
    foreach ($_POST['adet'] as $urun_adi => $adet) {
        if ($adet > 0) {
            $birim_fiyat = (float)$_POST['fiyat'][$urun_adi];
            for ($i=0; $i<$adet; $i++) { $alinan_urunler[] = ["ad" => $urun_adi, "fiyat" => $birim_fiyat]; $toplam_tutar += $birim_fiyat; }
        }
    }
    if ($toplam_tutar > 0) {
        $_SESSION['onay_bekleyenler'][] = [ "masa_id" => $m_id, "kisi" => $kisi, "odeme" => $odeme, "urunler" => $alinan_urunler, "tutar" => $toplam_tutar, "zaman" => date('H:i') ];
    }
    unset($_SESSION['aktif_musteri_masasi']); 
    header("Location: index.php?sayfa=musteri_basarili"); exit;
}

// B. ADMİN SİPARİŞİ ONAYLADIĞINDA
if (isset($_GET['islem']) && isset($_GET['onay_id'])) {
    $o_id = $_GET['onay_id'];
    if ($_GET['islem'] == 'onayla' && isset($_SESSION['onay_bekleyenler'][$o_id])) {
        $sip = $_SESSION['onay_bekleyenler'][$o_id]; $m_id = $sip['masa_id'];
        
        if ($_SESSION['masalar'][$m_id]['durum'] == 'Bos') {
            $_SESSION['masalar'][$m_id]['durum'] = 'Dolu'; $_SESSION['masalar'][$m_id]['kisi'] = $sip['kisi'];
        }
        $_SESSION['masalar'][$m_id]['tutar'] += $sip['tutar'];
        
        $recete = [ "Somon (Sashimi)" => "Somon Balığı", "Yılan Balığı (Unagi)" => "Yılan Balığı", "Sade Pilav" => "Pirinç", "Yosun Salatası" => "Yosun (Nori)", "Cola" => "Kola", "Fanta" => "Fanta", "Yeşil Çay" => "Yeşil Çay" ];

        foreach ($sip['urunler'] as $u) { 
            $_SESSION['masalar'][$m_id]['siparisler'][] = $u; 
            $satilan_isim = $u['ad'];
            if (isset($recete[$satilan_isim])) {
                $kullanilan_malzeme = $recete[$satilan_isim];
                if (isset($_SESSION['dolap'][$kullanilan_malzeme])) { $_SESSION['dolap'][$kullanilan_malzeme]['stok'] -= 1; }
            }
        }
        unset($_SESSION['onay_bekleyenler'][$o_id]);
    } elseif ($_GET['islem'] == 'reddet') {
        unset($_SESSION['onay_bekleyenler'][$o_id]);
    }
    header("Location: index.php?sayfa=masalar"); exit;
}

// Diğer Admin İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yeni_urun_ekle'])) {
    $kategori = $_POST['kategori'];
    if (!isset($_SESSION['menu'][$kategori])) $_SESSION['menu'][$kategori] = [];
    $_SESSION['menu'][$kategori][] = [ "ad" => trim($_POST['ad']), "fiyat" => (float)$_POST['fiyat'], "malzemeler" => trim($_POST['malzemeler']), "foto" => trim($_POST['foto']), "gizli" => false ];
    header("Location: index.php?sayfa=urunler"); exit;
}
if (isset($_GET['urun_islem']) && isset($_GET['kat']) && isset($_GET['id'])) {
    $kat = $_GET['kat']; $id = (int)$_GET['id'];
    if (isset($_SESSION['menu'][$kat][$id])) {
        if ($_GET['urun_islem'] == 'sil') { unset($_SESSION['menu'][$kat][$id]); $_SESSION['menu'][$kat] = array_values($_SESSION['menu'][$kat]); }
        elseif ($_GET['urun_islem'] == 'gizle') { $_SESSION['menu'][$kat][$id]['gizli'] = !$_SESSION['menu'][$kat][$id]['gizli']; }
    }
    header("Location: index.php?sayfa=urunler"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yeni_stok_ekle'])) {
    $malzeme_adi = trim($_POST['urun']);
    $_SESSION['dolap'][$malzeme_adi] = [ "urun" => $malzeme_adi, "stok" => (int)$_POST['stok'], "birim" => trim($_POST['birim']), "uyari_siniri" => (int)$_POST['uyari_siniri'] ];
    header("Location: index.php?sayfa=dolabim"); exit;
}
if (isset($_GET['stok_islem']) && isset($_GET['stok_id'])) {
    $s_id = $_GET['stok_id'];
    if (isset($_SESSION['dolap'][$s_id])) {
        if ($_GET['stok_islem'] == 'sil') { unset($_SESSION['dolap'][$s_id]); } 
        elseif ($_GET['stok_islem'] == 'arttir') { $_SESSION['dolap'][$s_id]['stok'] += 1; } 
        elseif ($_GET['stok_islem'] == 'azalt') { $_SESSION['dolap'][$s_id]['stok'] -= 1; }
    }
    header("Location: index.php?sayfa=dolabim"); exit;
}

$sayfa = isset($_GET['sayfa']) ? $_GET['sayfa'] : 'secim';

function gunlukToplamMusteri() {
    $toplam = 0; foreach ($_SESSION['masalar'] as $m) { $toplam += $m['kisi']; }
    $toplam += count($_SESSION['gecmis']) * 2; return $toplam;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rin POS & Müşteri Portalı</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ==========================================
           KESİN KAYDIRMA (SCROLL) DÜZELTMESİ !!!
           ========================================== */
        html, body {
            overflow-y: auto !important; 
            height: 100% !important; 
            min-height: 100vh !important;
            touch-action: pan-y !important;
        }
        .customer-portal {
            overflow-y: auto !important;
            height: 100% !important;
            min-height: 100vh !important;
            -webkit-overflow-scrolling: touch !important;
        }

        /* ==========================================
           MÜŞTERİ EKRANI ÖZEL TASARIMLARI
           ========================================== */
        .masa-secim-ekrani {
            background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 400px; margin: 50px auto; text-align: center;
        }
        .masa-secim-ekrani select {
            width: 100%; padding: 15px; font-size: 16px; border: 2px solid #00a884; border-radius: 8px; margin-bottom: 20px; outline: none;
        }
        
        .aktif-masa-bar {
            background: #111b21; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .degistir-btn {
            background: #dc2626; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold;
        }
        
        .c-cat-title { color: #047857; border-bottom: 2px solid #00a884; margin: 25px 0 15px; padding-bottom: 5px; font-size: 18px; }
        .c-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .c-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .c-card img { width: 100%; height: 130px; object-fit: cover; }
        .c-card-info { padding: 12px; display: flex; flex-direction: column; flex: 1; }
        .c-card-info h4 { margin: 0 0 5px 0; font-size: 14px; color: #111b21; }
        .c-ingredients { font-size: 11px; color: #6b7280; margin-bottom: 10px; flex: 1; line-height: 1.4; }
        .c-price-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .c-price { font-weight: bold; color: #047857; font-size: 16px; }

        /* Dev Miktar Butonları */
        .miktar-kontrol {
            display: flex; align-items: center; justify-content: space-between; background: #f3f4f6; border-radius: 8px; padding: 5px; margin-top: auto;
        }
        .miktar-btn {
            width: 35px; height: 35px; border: none; background: #00a884; color: white; font-size: 20px; font-weight: bold; border-radius: 6px; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: 0.2s;
        }
        .miktar-btn:active { transform: scale(0.9); }
        .miktar-btn.eksi { background: #dc2626; }
        .miktar-gosterge {
            font-size: 18px; font-weight: bold; width: 40px; text-align: center; border: none; background: transparent; pointer-events: none; color: #111b21;
        }
        
        /* Dinamik Sipariş Gönder Butonu */
        .siparis-gonder-dev {
            width: 100%; padding: 20px; font-size: 20px; font-weight: bold; border: none; border-radius: 12px;
            cursor: pointer; transition: 0.3s; position: sticky; bottom: 20px; box-shadow: 0 -5px 20px rgba(0,0,0,0.15); z-index: 100;
        }
        .siparis-pasif { background: #9ca3af !important; color: #f3f4f6 !important; cursor: not-allowed; }
        .siparis-aktif { background: #dc2626 !important; color: white !important; cursor: pointer; animation: pulse 1.5s infinite; }
        
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }
        
        .live-clock { background: rgba(255,255,255,0.8); padding: 6px 16px; border-radius: 6px; font-size: 16px; font-weight: bold; color: #111b21; border: 1px solid #d1d5db; display: inline-flex; align-items: center; gap: 8px; }
    </style>
</head>
<body class="soluk-arkaplan">

<!-- 1. GİRİŞ PORTALI -->
<?php if ($sayfa == 'secim'): ?>
    <div class="landing-container">
        <div class="landing-box">
            <h1 style="text-align: center; margin-bottom: 30px; color:#111b21;">Hoş Geldiniz</h1>
            <p style="text-align: center; margin-bottom: 40px; color:#4b5563;">Giriş yapmak istediğiniz portali seçin.</p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="index.php?sayfa=masalar" class="portal-btn admin-btn">
                    <div style="font-size: 50px; margin-bottom:10px;">👑</div><h3>Admin Paneli</h3><p style="font-size: 12px; color:#6b7280;">Kasa & Yönetim</p>
                </a>
                <a href="index.php?sayfa=musteri_siparis" class="portal-btn musteri-btn">
                    <div style="font-size: 50px; margin-bottom:10px;">📱</div><h3>Müşteri Menüsü</h3><p style="font-size: 12px; color:#6b7280;">Sipariş Ver</p>
                </a>
            </div>
        </div>
    </div>

<!-- 2. MÜŞTERİ MENÜSÜ -->
<?php elseif ($sayfa == 'musteri_siparis'): ?>
    
    <!-- MASA SEÇİLMEDİYSE MASA SEÇİM EKRANINI GÖSTER -->
    <?php if(!isset($_SESSION['aktif_musteri_masasi'])): ?>
        <div class="masa-secim-ekrani">
            <div style="font-size: 60px; margin-bottom: 10px;">🍽️</div>
            <h2 style="color: #111b21; margin-bottom: 5px;">Masanızı Seçin</h2>
            <p style="color: #4b5563; font-size: 14px; margin-bottom: 25px;">Sipariş verebilmek için lütfen oturduğunuz masayı seçin.</p>
            
            <form action="index.php" method="POST">
                <input type="hidden" name="masa_secimi_yapildi" value="1">
                <select name="masa_id" required>
                    <option value="" disabled selected>Masa Numarasını Seçiniz...</option>
                    <?php foreach ($_SESSION['masalar'] as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo $m['masa_no']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="c-submit-btn" style="width: 100%; font-size: 18px; padding: 15px;">Masayı Onayla ve Menüye Geç</button>
            </form>
            <div style="margin-top: 20px;"><a href="index.php?sayfa=secim" style="color:#6b7280; text-decoration:none; font-size:14px;">⬅ Ana Ekrana Dön</a></div>
        </div>
    
    <!-- MASA SEÇİLDİYSE MENÜYÜ GÖSTER -->
    <?php else: ?>
        <div class="customer-portal">
            <div class="customer-header">
                <h2>🍣 Rin Sushi Menü</h2>
                <a href="index.php?sayfa=secim" style="color:white; font-size:14px; text-decoration:none;">Çıkış Yap</a>
            </div>
            
            <div style="padding: 0 20px;">
                <div class="aktif-masa-bar">
                    <div>
                        <span style="font-size: 12px; color: #9ca3af; display:block;">Sipariş Verilen Masa:</span>
                        <strong style="font-size: 20px; color: #34d399;"><?php echo $_SESSION['masalar'][$_SESSION['aktif_musteri_masasi']]['masa_no']; ?></strong>
                    </div>
                    <a href="index.php?islem=masa_degistir" class="degistir-btn">🔄 Masayı Değiştir</a>
                </div>
            </div>

            <form action="index.php" method="POST" class="customer-form" id="siparisFormu" style="padding: 0 20px 100px 20px;">
                <input type="hidden" name="musteri_siparis_gonder" value="1">
                <input type="hidden" name="masa_id" value="<?php echo $_SESSION['aktif_musteri_masasi']; ?>">
                
                <div class="c-info-box" style="margin-bottom: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; gap: 15px;">
                    <div style="flex:1;"><label style="font-size:12px; font-weight:bold; color:#4b5563;">Kişi Sayısı:</label><input type="number" name="kisi" min="1" value="1" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; margin-top:5px;"></div>
                    <div style="flex:2;"><label style="font-size:12px; font-weight:bold; color:#4b5563;">Ödeme Yöntemi:</label><select name="odeme" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; margin-top:5px;"><option value="Kredi Kartı">Kredi Kartı</option><option value="Nakit">Nakit</option></select></div>
                </div>

                <?php foreach ($_SESSION['menu'] as $kategori => $urunler): ?>
                    <?php 
                        $goster = false;
                        foreach($urunler as $u) { if (!isset($u['gizli']) || !$u['gizli']) $goster = true; }
                        if (!$goster) continue;
                    ?>
                    <h3 class="c-cat-title"><?php echo $kategori; ?></h3>
                    <div class="c-grid">
                        <?php foreach ($urunler as $urun): ?>
                            <?php if (isset($urun['gizli']) && $urun['gizli']) continue; ?>
                            <div class="c-card">
                                <img src="<?php echo htmlspecialchars($urun['foto']); ?>" alt="foto">
                                <div class="c-card-info">
                                    <h4><?php echo htmlspecialchars($urun['ad']); ?></h4>
                                    <p class="c-ingredients"><?php echo htmlspecialchars($urun['malzemeler']); ?></p>
                                    <div class="c-price-row">
                                        <span class="c-price"><?php echo $urun['fiyat']; ?> ₺</span>
                                        <input type="hidden" name="fiyat[<?php echo htmlspecialchars($urun['ad']); ?>]" value="<?php echo $urun['fiyat']; ?>">
                                    </div>
                                    
                                    <div class="miktar-kontrol">
                                        <button type="button" class="miktar-btn eksi" onclick="miktarDegistir('<?php echo htmlspecialchars($urun['ad']); ?>', -1)">-</button>
                                        <input type="number" name="adet[<?php echo htmlspecialchars($urun['ad']); ?>]" id="adet_<?php echo htmlspecialchars($urun['ad']); ?>" class="miktar-gosterge sepet-urun" value="0" readonly>
                                        <button type="button" class="miktar-btn arti" onclick="miktarDegistir('<?php echo htmlspecialchars($urun['ad']); ?>', 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- DİNAMİK SİPARİŞ GÖNDER BUTONU -->
                <button type="submit" id="siparisBtn" class="siparis-gonder-dev siparis-pasif" disabled>
                    🛒 Sepet Boş (Sipariş Ver)
                </button>
            </form>
        </div>
        
        <script>
            function miktarDegistir(urunAdi, degisim) {
                let input = document.getElementById('adet_' + urunAdi);
                let mevcutDeger = parseInt(input.value) || 0;
                let yeniDeger = mevcutDeger + degisim;
                if (yeniDeger >= 0) { input.value = yeniDeger; }
                butonKontrol();
            }

            function butonKontrol() {
                let urunler = document.querySelectorAll('.sepet-urun');
                let toplamUrun = 0;
                urunler.forEach(function(input) { toplamUrun += parseInt(input.value) || 0; });

                let btn = document.getElementById('siparisBtn');
                if (toplamUrun > 0) {
                    btn.disabled = false;
                    btn.classList.remove('siparis-pasif');
                    btn.classList.add('siparis-aktif');
                    btn.innerHTML = `🛒 Siparişi Gönder (${toplamUrun} Ürün)`;
                } else {
                    btn.disabled = true;
                    btn.classList.remove('siparis-aktif');
                    btn.classList.add('siparis-pasif');
                    btn.innerHTML = `🛒 Sepet Boş (Sipariş Ver)`;
                }
            }
        </script>
    <?php endif; ?>

<?php elseif ($sayfa == 'musteri_basarili'): ?>
    <div class="landing-container">
        <div class="landing-box" style="text-align: center;">
            <div style="font-size: 80px; margin-bottom: 20px;">✅</div><h2 style="color: #047857;">Siparişiniz Alındı!</h2>
            <p style="color: #4b5563; margin-top:10px;">Siparişiniz mutfağa iletildi. Afiyet olsun!</p><br>
            <a href="index.php?sayfa=secim" class="btn btn-secondary">Ana Ekrana Dön</a>
        </div>
    </div>

<!-- 3. ADMIN PANELİ -->
<?php else: ?>
    <div class="app-container">
        <div class="sidebar">
            <div class="sidebar-header">🍣 Rin Admin <a href="index.php?sayfa=secim" style="float:right; font-size:12px; color:#dc2626; text-decoration:none;">Çıkış</a></div>
            <div class="today-summary">
                <div style="font-size: 12px; margin-bottom:5px;">Günün Müşterisi</div><div style="font-size: 24px; font-weight:bold;"><?php echo gunlukToplamMusteri(); ?> Kişi</div>
            </div>
            <ul class="menu-list">
                <li><a href="index.php?sayfa=masalar" class="menu-item <?php echo $sayfa=='masalar'?'active':''; ?>">🪑 Masalar</a></li>
                <li><a href="index.php?sayfa=urunler" class="menu-item <?php echo $sayfa=='urunler'?'active':''; ?>">📋 Ürün Listesi</a></li>
                <li><a href="index.php?sayfa=dolabim" class="menu-item <?php echo $sayfa=='dolabim'?'active':''; ?>">📦 Dolabım (Stok)</a></li>
            </ul>
        </div>

        <div class="main-content">
            <?php if (!empty($_SESSION['onay_bekleyenler'])): ?>
                <div style="background:#fef9c3; border:2px solid #facc15; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <h3 style="color:#b45309; margin-bottom:10px;">🔔 Onay Bekleyen Siparişler</h3>
                    <?php foreach ($_SESSION['onay_bekleyenler'] as $id => $onay): ?>
                        <div style="background:white; padding:10px; border-radius:6px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong><?php echo $_SESSION['masalar'][$onay['masa_id']]['masa_no']; ?></strong>
                                <?php echo $onay['kisi']; ?> Kişi<br>
                                <span style="font-size:13px; color:#4b5563;"><?php echo implode(", ", array_column($onay['urunler'], 'ad')); ?></span>
                            </div>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <strong style="color:#047857;"><?php echo $onay['tutar']; ?> ₺</strong>
                                <a href="index.php?islem=onayla&onay_id=<?php echo $id; ?>" class="btn btn-primary">Onayla</a>
                                <a href="index.php?islem=reddet&onay_id=<?php echo $id; ?>" class="btn btn-secondary" style="background:#dc2626;">Reddet</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($sayfa == 'masalar'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="page-title" style="margin: 0;">Aktif Masalar</h2>
                    <div class="live-clock">🕒 <span id="current-time"><?php echo date('d.m.Y H:i:s'); ?></span></div>
                </div>
                <h3 style="margin: 20px 0 15px 0; color: #374151; border-bottom: 2px solid #d1d5db; padding-bottom: 5px;">🛋️ İç Mekan (Salon)</h3>
                <div class="tables-grid">
                    <?php foreach ($_SESSION['masalar'] as $masa): if ($masa['bolum'] != 'Salon') continue; $r = 'bg-beyaz'; if ($masa['durum'] == 'Dolu') $r = 'bg-sari'; if ($masa['durum'] == 'Odendi') $r = 'bg-kirmizi'; ?>
                    <a href="ekle.php?masa_id=<?php echo $masa['id']; ?>" class="table-card <?php echo $r; ?>"><div class="table-emoji">🥢</div><div class="table-info"><div class="t-no"><?php echo htmlspecialchars($masa['masa_no']); ?></div><?php if ($masa['durum'] != 'Bos'): ?> <div class="t-price"><?php echo $masa['tutar']; ?> ₺</div><div class="t-people"><?php echo $masa['kisi']; ?> Kişi</div> <?php else: ?> <div class="t-people" style="color:#9ca3af;">Boş Masa</div> <?php endif; ?></div></a>
                    <?php endforeach; ?>
                </div>
                <h3 style="margin: 40px 0 15px 0; color: #374151; border-bottom: 2px solid #d1d5db; padding-bottom: 5px;">🌅 Dış Mekan (Teras)</h3>
                <div class="tables-grid">
                    <?php foreach ($_SESSION['masalar'] as $masa): if ($masa['bolum'] != 'Teras') continue; $r = 'bg-beyaz'; if ($masa['durum'] == 'Dolu') $r = 'bg-sari'; if ($masa['durum'] == 'Odendi') $r = 'bg-kirmizi'; ?>
                    <a href="ekle.php?masa_id=<?php echo $masa['id']; ?>" class="table-card <?php echo $r; ?>"><div class="table-emoji">🌸</div><div class="table-info"><div class="t-no"><?php echo htmlspecialchars($masa['masa_no']); ?></div><?php if ($masa['durum'] != 'Bos'): ?> <div class="t-price"><?php echo $masa['tutar']; ?> ₺</div><div class="t-people"><?php echo $masa['kisi']; ?> Kişi</div> <?php else: ?> <div class="t-people" style="color:#9ca3af;">Boş Masa</div> <?php endif; ?></div></a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($sayfa == 'urunler'): ?>
                <h2 class="page-title">📋 Ürün ve Menü Yönetimi</h2>
                <div style="background: rgba(255,255,255,0.9); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                    <h3 style="color:#047857; margin-bottom: 15px;">➕ Yeni Ürün Ekle</h3>
                    <form method="POST" action="index.php?sayfa=urunler" style="display: flex; flex-wrap: wrap; gap: 15px;">
                        <input type="hidden" name="yeni_urun_ekle" value="1">
                        <div style="flex: 1; min-width: 150px;"><label style="display:block; font-size:12px; font-weight:bold;">Kategori</label><select name="kategori" required style="width:100%; padding:8px; border:1px solid #ccc;"><option value="Sushi & Deniz Ürünleri">Sushi & Deniz Ürünleri</option><option value="Aparatifler">Aparatifler</option><option value="İçecekler">İçecekler</option></select></div>
                        <div style="flex: 2; min-width: 200px;"><label style="display:block; font-size:12px; font-weight:bold;">Ürün Adı</label><input type="text" name="ad" required style="width:100%; padding:8px; border:1px solid #ccc;"></div>
                        <div style="flex: 1; min-width: 100px;"><label style="display:block; font-size:12px; font-weight:bold;">Fiyat (TL)</label><input type="number" step="0.01" name="fiyat" required style="width:100%; padding:8px; border:1px solid #ccc;"></div>
                        <div style="flex: 3; min-width: 250px;"><label style="display:block; font-size:12px; font-weight:bold;">Görsel URL</label><input type="text" name="foto" required style="width:100%; padding:8px; border:1px solid #ccc;"></div>
                        <div style="flex: 100%;"><label style="display:block; font-size:12px; font-weight:bold;">İçindekiler</label><textarea name="malzemeler" rows="2" required style="width:100%; padding:8px; border:1px solid #ccc; font-family:inherit;"></textarea></div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Menüye Kaydet</button>
                    </form>
                </div>
                <table class="data-table" style="background: rgba(255,255,255,0.9);">
                    <thead><tr><th style="width:60px;">Görsel</th><th>Ürün Adı & Kategori</th><th>İçindekiler</th><th>Fiyat</th><th style="text-align:right;">İşlem</th></tr></thead>
                    <tbody>
                        <?php foreach ($_SESSION['menu'] as $kategori => $urunler): foreach ($urunler as $id => $urun): ?>
                            <tr style="<?php echo (isset($urun['gizli']) && $urun['gizli']) ? 'opacity:0.5; background:#f3f4f6;' : ''; ?>">
                                <td><img src="<?php echo htmlspecialchars($urun['foto']); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:6px;"></td>
                                <td><strong><?php echo htmlspecialchars($urun['ad']); ?></strong><br><span style="font-size:11px; color:#6b7280;"><?php echo $kategori; ?></span></td>
                                <td style="font-size:12px; color:#4b5563;"><?php echo htmlspecialchars($urun['malzemeler']); ?></td>
                                <td style="color:#047857; font-weight:bold;"><?php echo $urun['fiyat']; ?> ₺</td>
                                <td style="text-align:right;">
                                    <a href="index.php?urun_islem=gizle&kat=<?php echo urlencode($kategori); ?>&id=<?php echo $id; ?>" class="btn btn-warning btn-sm"><?php echo (isset($urun['gizli']) && $urun['gizli']) ? 'Göster' : 'Gizle'; ?></a>
                                    <a href="index.php?urun_islem=sil&kat=<?php echo urlencode($kategori); ?>&id=<?php echo $id; ?>" class="btn btn-primary btn-sm" style="background:#dc2626;" onclick="return confirm('Silmek istediğine emin misin?');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($sayfa == 'dolabim'): ?>
                <h2 class="page-title">📦 Dolabım (Stok Yönetimi)</h2>
                <div style="background: rgba(255,255,255,0.9); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                    <h3 style="color:#047857; margin-bottom: 15px;">➕ Yeni Malzeme / Stok Ekle</h3>
                    <form method="POST" action="index.php?sayfa=dolabim" style="display: flex; flex-wrap: wrap; gap: 15px;">
                        <input type="hidden" name="yeni_stok_ekle" value="1">
                        <div style="flex: 2; min-width: 200px;"><label style="display:block; font-size:12px; font-weight:bold;">Malzeme Adı</label><input type="text" name="urun" required style="width:100%; padding:8px; border:1px solid #ccc;"></div>
                        <div style="flex: 1; min-width: 100px;"><label style="display:block; font-size:12px; font-weight:bold;">Miktar</label><input type="number" name="stok" required style="width:100%; padding:8px; border:1px solid #ccc;"></div>
                        <div style="flex: 1; min-width: 100px;"><label style="display:block; font-size:12px; font-weight:bold;">Birim</label><input type="text" name="birim" placeholder="Kg, Porsiyon, Şişe..." required style="width:100%; padding:8px; border:1px solid #ccc;"></div>
                        <div style="flex: 1; min-width: 100px;"><label style="display:block; font-size:12px; font-weight:bold;">Uyarı Sınırı</label><input type="number" name="uyari_siniri" placeholder="Örn: 5" required style="width:100%; padding:8px; border:1px solid #ccc;"></div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Dolaba Ekle</button>
                    </form>
                </div>
                <table class="data-table" style="background: rgba(255,255,255,0.9);">
                    <thead><tr><th>Malzeme Adı</th><th>Kalan Stok</th><th>Durum</th><th style="text-align:right;">İşlem</th></tr></thead>
                    <tbody>
                        <?php foreach ($_SESSION['dolap'] as $id => $urun): $azaliyor = $urun['stok'] <= $urun['uyari_siniri']; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($urun['urun']); ?></strong></td>
                            <td class="<?php echo $azaliyor ? 'text-danger' : ''; ?>" style="font-size:16px; font-weight:bold;"><?php echo htmlspecialchars($urun['stok'] . ' ' . $urun['birim']); ?></td>
                            <td><?php if ($azaliyor): ?> <span class="badge badge-danger">Kritik Seviye!</span> <?php else: ?> <span class="badge badge-success">Yeterli</span> <?php endif; ?></td>
                            <td style="text-align:right;">
                                <a href="index.php?stok_islem=arttir&stok_id=<?php echo urlencode($id); ?>" class="btn btn-primary btn-sm" style="background:#059669;">+1 Artır</a>
                                <a href="index.php?stok_islem=azalt&stok_id=<?php echo urlencode($id); ?>" class="btn btn-warning btn-sm" style="background:#d97706;">-1 Azalt</a>
                                <a href="index.php?stok_islem=sil&stok_id=<?php echo urlencode($id); ?>" class="btn btn-primary btn-sm" style="background:#dc2626;" onclick="return confirm('Silmek istiyor musunuz?');">Sil</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    function updateClock() {
        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0'), m = String(now.getMonth() + 1).padStart(2, '0'), y = now.getFullYear();
        const h = String(now.getHours()).padStart(2, '0'), min = String(now.getMinutes()).padStart(2, '0'), s = String(now.getSeconds()).padStart(2, '0');
        let clock = document.getElementById('current-time');
        if(clock) clock.innerText = `${d}.${m}.${y} ${h}:${min}:${s}`;
    }
    setInterval(updateClock, 1000);
</script>
</body>
</html>