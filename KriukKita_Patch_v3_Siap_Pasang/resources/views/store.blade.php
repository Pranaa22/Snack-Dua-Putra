<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#faf7f1"><title>KriukKita — Keripik Lokal, Rasa Juara</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Fraunces:ital,opsz,wght@1,9..144,700;1,9..144,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/kriuk.css') }}"></head><body>
<!-- STOREFRONT WRAPPER -->
<div id="storefront-view">

<!-- TOP RIBBON (Dynamically Editable from Admin CMS) -->
<div class="ribbon">
  <span class="ribbon-pill">UMKM LOKAL</span>
  <span id="dynamic-ribbon-text">{{ $settings->ribbon ?? 'Pilihan keripik untuk hari-hari yang lebih seru' }}</span>
</div>

<!-- STICKY HEADER -->
<header class="head" id="atas">
  <nav class="container nav">
    <a class="logo" href="#atas" aria-label="KriukKita beranda">
      <span class="logo-mark">
        <svg width="24" height="24" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="14" cy="15" rx="9.5" ry="7" transform="rotate(-14 14 15)" fill="#FFF3DF" stroke="#242019" stroke-width="2"/>
          <path d="M9.5 14C12.5 12.5 15.5 12.5 18.5 14" stroke="#CF491E" stroke-width="2" stroke-linecap="round"/>
          <path d="M11 17C13.2 16 15.5 16 17.5 17" stroke="#242019" stroke-width="1.8" stroke-linecap="round"/>
          <circle cx="20" cy="7" r="2" fill="#FFF3DF"/>
        </svg>
      </span>
      <span id="dynamic-brand-logo">Kriuk<span class="orange">Kita.</span></span>
    </a>

    <div class="navlinks">
      <a href="#atas">Beranda</a>
      <a href="#produk">Katalog</a>
      <a href="#promo-kombo">Paket Kombo</a>
      <a href="#cara">Cara Pesan</a>
      <a href="#tentang">Tentang Kami</a>
    </div>

    <div class="navright">

      <!-- Cart Trigger -->
      <button class="cart-btn" id="open-cart" aria-label="Buka keranjang">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <span class="cart-count" id="cart-count">0</span>
      </button>

      <a class="btn btn-dark desktop-shop" href="#produk">
        <span>Belanja</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M7 7h10v10"/></svg>
      </a>

      <button class="menu-btn" aria-label="Ke katalog" onclick="document.getElementById('produk').scrollIntoView({behavior:'smooth'})">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
      </button>
    </div>
  </nav>
</header>

<!-- HERO SECTION -->
<section class="hero">
  <div class="container">
    
    <div class="hero-content">
      <div class="hero-left">
        <span class="hero-pill">
          <span class="dot"></span> Dibuat untuk pencinta ngemil
        </span>
        <h1>Sekali gigit,<br><span class="accent">auto kriuk!</span></h1>
        <p>Dari singkong pilihan sampai bumbu rasa nusantara. Cemilan lokal yang bikin ngobrol, nonton, dan nugas jadi makin seru.</p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="#produk">
            <span>Lihat Varian Rasa</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M7 7h10v10"/></svg>
          </a>
          <a href="#tentang" style="font-size:13px;font-weight:800;text-decoration:underline;text-underline-offset:4px">Kenali cerita kami</a>
        </div>

        <!-- Microproof with 100% Custom SVG Icons (No Emoji) -->
        <div class="microproof">
          <span class="avatarstack">
            <span class="av-ico" title="Bumbu Rempah Asli">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M12 2c4 4 6 8 4 14-2 4-6 6-10 4 4-2 5-6 3-10-1-3 0-6 3-8z"/></svg>
            </span>
            <span class="av-ico" title="Bahan Singkong & Umbi Lokal">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><ellipse cx="12" cy="12" rx="8" ry="6" transform="rotate(-20 12 12)"/><circle cx="10" cy="11" r="1"/><circle cx="14" cy="13" r="1"/></svg>
            </span>
            <span class="av-ico" title="Digoreng Higienis Setiap Hari">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M12 3l2.4 5.6L20 11l-5.6 2.4L12 19l-2.4-5.6L4 11l5.6-2.4L12 3z"/></svg>
            </span>
          </span>
          <span id="hero-min-price"><b>Mulai Rp12.000</b> • Pilih ukuran 100g, 250g, hingga 500g</span>
        </div>
      </div>

      <div class="hero-art">
        <div class="orbit-text">Renyanhnya bikin senang</div>
        <div class="chip-float chip-a"></div>
        <div class="chip-float chip-b"></div>
        <div class="chip-float chip-c"></div>
        <div class="hero-pouch one" id="hero-bag-1" title="Lihat detail produk"></div>
        <div class="hero-pouch two" id="hero-bag-2" title="Lihat detail produk"></div>
        <div class="hero-sticker">
          <strong>Pilihan rasa beragam</strong>
          <small>Temukan favoritmu di katalog</small>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES BAR (100% Custom SVG Icons) -->
<section class="features">
  <div class="container features-inner">
    <div class="feature">
      <div class="ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 2l2.8 6.2L21 11l-6.2 2.8L12 20l-2.8-6.2L3 11l6.2-2.8L12 2z"/></svg>
      </div>
      <div>
        <strong>Rasa Pilihan Nusantara</strong>
        <small>Gurih klasik, pedas daun jeruk, hingga manis</small>
      </div>
    </div>
    <div class="feature">
      <div class="ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div>
        <strong>Produk UMKM Lokal</strong>
        <small>Dibuat dengan perhatian pada rasa dan kemasan</small>
      </div>
    </div>
    <div class="feature">
      <div class="ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>
      </div>
      <div>
        <strong>Belanja Super Simpel</strong>
        <small>Pilih berat kemasan, masuk keranjang & bayar</small>
      </div>
    </div>
  </div>
</section>

<!-- MAIN CONTENT -->
<main>
  <!-- PRODUCT CATALOG SECTION -->
  <section class="section" id="produk">
    <div class="container">
      

      <div class="section-head">
        <div>
          <div class="smallcaps">Koleksi Cemilan</div>
          <h2 class="section-title">Temukan <em>kriuk</em> favoritmu.</h2>
        </div>
        <div class="section-sub">
          Pilih varian rasa dan ukuran kemasan (<b>100g, 250g, atau 500g</b>). Klik gambar kemasan untuk melihat tingkat kerenyahan & komposisi.
        </div>
      </div>

      <div class="toolbar">
        <div class="filters" id="filters">
          <button class="filter active" data-filter="semua" id="filter-all-btn">Semua Rasa</button>
          <button class="filter" data-filter="gurih">Gurih</button>
          <button class="filter" data-filter="pedas">Pedas</button>
          <button class="filter" data-filter="manis">Manis</button>
        </div>

        <label class="search">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#978775" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
          <input type="search" id="search" placeholder="Cari singkong, balado, pisang..." aria-label="Cari produk">
        </label>
      </div>

      <div class="products" id="product-grid"></div>
    </div>
  </section>

  <!-- INTERACTIVE PROMO & BUNDLE BUILDER SECTION -->
  <section class="promo" id="promo-kombo">
    <div class="container">
      

      <div class="promo-body">
        <div class="promo-text">
          <div class="smallcaps">Paket Kombo Lebih Hemat</div>
          <h2>Bebas pilih 3 rasa,<br>diskon langsung Rp6.000!</h2>
          <p>Temani istirahat, kerja kelompok, atau maraton film. Klik varian di bawah untuk mengisi 3 slot kotak kombo ukuran 100g kamu:</p>

          <div class="bundle-picker-label">Klik untuk ganti isi slot kombo:</div>
          <div class="bundle-MiniGrid" id="bundle-selector-btns"></div>

          <div class="promo-actions">
            <button type="button" class="btn btn-primary" id="add-bundle-btn">
              <span>+ Masukkan Paket 3 Rasa</span>
            </button>
            <div class="bundle-price-tag" id="bundle-price-display">
              <del>Rp45.000</del> <strong>Rp39.000</strong>
            </div>
          </div>
        </div>

        <div class="promo-pic">
          <div class="bundle-slots" id="bundle-slots"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- 3 STEPS HOW TO ORDER -->
  <section class="how" id="cara">
    <div class="container">
      <div class="smallcaps">Mudah dan Praktis</div>
      <h2 class="section-title">Tinggal tiga langkah.</h2>
      <div class="steps">
        <div class="step">
          <span class="step-num">01</span>
          <h3>Pilih rasa & ukuran kemasan</h3>
          <p>Tentukan varian keripik dan pilih ukuran 100g, 250g, atau 500g sesuai kebutuhan ngemilmu, lalu tambahkan ke keranjang.</p>
        </div>
        <div class="step">
          <span class="step-num">02</span>
          <h3>Isi alamat pengiriman singkat</h3>
          <p>Masukkan nama penerima, nomor WhatsApp aktif, serta alamat pengiriman dan pilih kurir Reguler atau Express.</p>
        </div>
        <div class="step">
          <span class="step-num">03</span>
          <h3>Pembayaran Digital Terintegrasi</h3>
          <p>Pilih metode pembayaran yang tersedia melalui Midtrans. Status transaksi diperbarui setelah pembayaran berhasil diverifikasi.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ABOUT US SECTION (0% Emoji, Custom Vector Icons) -->
  <section class="about" id="tentang">
    <div class="container">
      
      <div class="about-inner">
        <div class="about-art" id="about-bag"></div>
        <div class="about-copy">
          <div class="smallcaps">Cerita Kami</div>
          <h2 id="dynamic-about-heading">{{ $settings->about_title ?? 'Cerita Kami' }}</h2>
          <p id="dynamic-about-p1">{{ $settings->about_description ?? 'Setiap rasa punya cerita, dan setiap gigitan punya kenangan.' }}</p>
          <p>Kami percaya camilan sederhana bisa membuat momen bersama terasa lebih hangat. Pilih rasa favoritmu, dan nikmati sensasi renyah dalam setiap gigitan.</p>
          <div class="about-pills">
            <span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#cf491e" stroke-width="2.2"><path d="M12 2c4 4 6 8 4 14-2 4-6 6-10 4 4-2 5-6 3-10-1-3 0-6 3-8z"/></svg>
              Bahan Baku Lokal
            </span>
            <span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#cf491e" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
              Pilihan Rasa Lengkap
            </span>
            <span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#cf491e" stroke-width="2.2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.3l7.8-7.8 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
              Dukung UMKM Indonesia
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- FOOTER (With Subtle Non-Intrusive Portal Mitra / Admin Trigger) -->
<footer>
  <div class="container">
    <div class="footer-wrap">
      <div>
        <a class="logo" href="#atas">
          <span class="logo-mark">
            <svg width="22" height="22" viewBox="0 0 28 28" fill="none"><ellipse cx="14" cy="15" rx="9.5" ry="7" transform="rotate(-14 14 15)" fill="#FFF3DF" stroke="#242019" stroke-width="2"/><path d="M9.5 14C12.5 12.5 15.5 12.5 18.5 14" stroke="#CF491E" stroke-width="2" stroke-linecap="round"/></svg>
          </span>
          <span>Kriuk<span class="orange">Kita.</span></span>
        </a>
        <p>Keripik pilihan dengan rasa khas Nusantara, untuk dinikmati kapan saja. Pilih varian kesukaanmu, kami bantu siapkan pesanan dengan mudah.</p>
      </div>
      <div class="footer-links">
        <a href="#produk">Katalog</a>
        <a href="#promo-kombo">Paket Kombo</a>
        <a href="#cara">Cara Pesan</a>
        <a href="#tentang">Tentang</a>
        
      </div>
    </div>
    <div class="footer-bottom">
      <span>© {{ date('Y') }} KriukKita • Keripik Lokal, Rasa Juara</span>
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        
        <a href="{{ route('admin.login') }}" style="color:#fff;font-weight:700">Masuk Penjual</a>
      </div>
    </div>
  </div>
</footer>

</div><!-- END #storefront-view --><!-- TOAST NOTIFICATION -->
<div class="toast-msg" id="toast-msg" role="status" aria-live="polite">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f36b32" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
  <span id="toast-text">Perubahan berhasil disimpan</span>
</div>

<!-- SHARED OVERLAY -->
<div class="overlay" id="overlay"></div>

<!-- SLIDE-OVER CART DRAWER -->
<aside class="drawer" id="drawer" aria-label="Keranjang belanja">
  <div class="drawer-head">
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f36b32" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      <span>Keranjang Kamu</span>
    </h2>
    <button class="x-btn" id="close-cart" aria-label="Tutup keranjang">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>

  <!-- Free Shipping Progress Bar -->
  <div class="ship-bar-wrap">
    <div class="ship-bar-text">
      <span id="ship-status-msg">Belanja Rp50.000 untuk Gratis Ongkir Reguler!</span>
      <span id="ship-percent">0%</span>
    </div>
    <div class="ship-track">
      <div class="ship-fill" id="ship-fill"></div>
    </div>
  </div>

  <div class="drawer-items" id="cart-items"></div>

  <div class="drawer-foot">
    <div class="voucher-bar" style="flex-direction:column;align-items:stretch;gap:9px">
      <label for="promo-input" style="font-weight:800">Punya Kode Promo?</label>
      <div style="display:flex;gap:8px;align-items:center">
        <input type="text" id="promo-input" maxlength="24" autocomplete="off" placeholder="Masukkan kode promo" aria-label="Kode promo" style="min-width:0;flex:1;padding:10px;border:1px solid var(--line);border-radius:9px;text-transform:uppercase">
        <button type="button" class="voucher-btn" id="apply-promo" style="white-space:nowrap;padding:10px 12px">Terapkan</button>
      </div>
      <span id="promo-message" style="color:var(--soft);font-size:11px" role="status">Masukkan kode promo yang tersedia.</span>
    </div>
    <div class="sum">
      <span>Potongan Promo</span>
      <span id="cart-discount">Rp0</span>
    </div>
    <div class="sum total-row">
      <span>Subtotal</span>
      <strong id="cart-total">Rp0</strong>
    </div>
    <button class="btn btn-primary full" id="checkout-open">
      <span>Lanjut ke Pengiriman & Pembayaran</span>
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </button>
    <p class="note">Ongkir akhir ditampilkan sebelum pembayaran</p>
  </div>
</aside>

<!-- PRODUCT QUICK-VIEW MODAL -->
<section class="modal" id="quickview-modal" style="max-width:700px" aria-label="Detail Produk">
  <div class="modal-head">
    <h2 id="qv-title">Detail Varian Keripik</h2>
    <button class="x-btn" id="close-qv" aria-label="Tutup detail">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>
  <div class="qv-grid" id="qv-content"></div>
</section>

<script src="{{ asset('assets/js/store.js') }}" defer></script></body></html>