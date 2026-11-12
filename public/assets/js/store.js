(()=>{'use strict';
const q=s=>document.querySelector(s), money=n=>'Rp'+Math.round(+n||0).toLocaleString('id-ID');
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const mult={100:1,250:2.2,500:4};
const calc=(base,w)=>Math.round(base*mult[w]/500)*500;
let products=[], weights={}, cart=[], filter='semua', promo=null, promoCode='', slots=[null,null,null], activeModal='';
try{cart=JSON.parse(localStorage.getItem('kriukkita_cart')||'[]');if(!Array.isArray(cart))cart=[];promoCode=localStorage.getItem('kriukkita_promo_code')||''}catch(e){cart=[]}
function persist(){localStorage.setItem('kriukkita_cart',JSON.stringify(cart));if(promoCode)localStorage.setItem('kriukkita_promo_code',promoCode);else localStorage.removeItem('kriukkita_promo_code');localStorage.removeItem('kriukkita_voucher')}
function toast(message){const t=q('#toast-msg');if(!t)return;q('#toast-text').textContent=message;t.classList.add('show');clearTimeout(toast.timer);toast.timer=setTimeout(()=>t.classList.remove('show'),3000)}
  function bag(p, customWeight) {
    const wText = (customWeight || weights[p.id] || 100) + ' g';
    if (p.imageUrl) return `<img class="pouch product-photo" src="${esc(p.imageUrl)}" alt="Foto ${esc(p.name)}" loading="lazy">`;
    const uid = (p.id || 'x') + '_' + Math.random().toString(36).slice(2, 6);
    return `<svg class="pouch" viewBox="0 0 210 310" role="img" aria-label="Kemasan ${esc(p.name)}" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="g${uid}" x1="0" y1="0" x2="1" y2="1">
          <stop stop-color="#fff" stop-opacity=".22"/>
          <stop offset=".48" stop-color="#fff" stop-opacity="0"/>
          <stop offset="1" stop-color="#1e120a" stop-opacity=".22"/>
        </linearGradient>
      </defs>
      <path d="M20 22 Q25 12 36 13H174Q185 12 190 22L182 284Q180 296 169 297H41Q30 296 28 284Z" fill="${p.bag}"/>
      <path d="M20 22Q25 12 36 13H174Q185 12 190 22L185 43H25Z" fill="#f6d8a8"/>
      <path d="M21 25H189M25 41H185M27 275H183" stroke="#8a5620" stroke-width="2" opacity=".55"/>
      <path d="M20 22 Q25 12 36 13H174Q185 12 190 22L182 284Q180 296 169 297H41Q30 296 28 284Z" fill="url(#g${uid})"/>
      <ellipse cx="105" cy="136" rx="73" ry="85" fill="${p.label}"/>
      <ellipse cx="105" cy="136" rx="67" ry="79" fill="none" stroke="#9e6d2b" stroke-width="1.5" stroke-dasharray="4 6" opacity=".45"/>
      <text x="105" y="86" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="13" font-weight="900" fill="#242019" letter-spacing="0.5">KRIUKKITA</text>
      <path d="M59 97H151" stroke="#b5834a" stroke-width="1.6"/>
      <text x="105" y="119" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="9.5" font-weight="800" fill="#6b4c32" letter-spacing="1.8">KERIPIK LOKAL</text>
      <text x="105" y="138" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="12" font-weight="900" fill="#242019">${esc(p.subtitle)}</text>
      <ellipse cx="88" cy="180" rx="25" ry="18" transform="rotate(-24 88 180)" fill="#ebb858" stroke="#c48731" stroke-width="2.8"/>
      <ellipse cx="124" cy="180" rx="25" ry="18" transform="rotate(22 124 180)" fill="#f5ca6e" stroke="#c48731" stroke-width="2.8"/>
      <ellipse cx="102" cy="196" rx="25" ry="18" transform="rotate(-3 102 196)" fill="#f8d480" stroke="#cf953f" stroke-width="2.8"/>
      <path d="M88 171q10 6 0 16m36-15q-8 5 1 15m-25 1q12 4 0 14" fill="none" stroke="#b07f34" opacity=".55" stroke-width="1.8"/>
      <text x="105" y="253" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="9.5" font-weight="900" fill="#fff3df" letter-spacing="1.4">ZIPLOCK POUCH</text>
      <rect x="73" y="260" width="64" height="18" rx="9" fill="#242019" opacity=".35"/>
      <text x="105" y="272" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="10.5" font-weight="800" fill="#ffffff">${wText}</text>
    </svg>`;
  }


function openModal(which){activeModal=which;q('#overlay').classList.toggle('open',!!which);q('#drawer').classList.toggle('open',which==='cart');q('#quickview-modal').classList.toggle('open',which==='quick');document.body.style.overflow=which?'hidden':''}
function count(){return cart.reduce((a,x)=>a+x.qty,0)}
function sum(){return cart.reduce((a,x)=>a+x.price*x.qty,0)}
function promoDiscount(amount){if(!promo||amount<(promo.min_subtotal||0))return 0;const value=promo.type==='percent'?Math.floor(amount*promo.value/100):promo.value;const cap=(promo.type==='percent'&&promo.max_discount)?promo.max_discount:value;return Math.max(0,Math.min(amount,value,cap))}
async function verifyPromo(code, silent=false){
  if(!code){promo=null;promoCode='';persist();renderCart();return}
  if(!cart.length){if(!silent)toast('Tambahkan produk ke keranjang terlebih dahulu.');return}
  try{
    const res=await fetch('/api/promo/validasi',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':q('meta[name="csrf-token"]').content},body:JSON.stringify({code,items:cart.map(x=>({key:x.key,qty:x.qty}))})});
    const data=await res.json();
    if(!res.ok)throw Error(Object.values(data.errors||{}).flat().join(' ')||data.message||'Kode promo tidak valid.');
    promo=data;promoCode=data.code;q('#promo-input').value=promoCode;persist();renderCart();
    if(!silent)toast('Kode promo '+data.code+' berhasil diterapkan.');
  }catch(e){promo=null;promoCode='';persist();renderCart();if(!silent)toast(e.message)}
}

function renderProducts(){
q('#filter-all-btn').textContent=`Semua Rasa (${products.length})`;
const term=q('#search').value.toLowerCase().trim();
const list=products.filter(p=>(filter==='semua'||p.group===filter) && (p.name+' '+p.taste+' '+p.subtitle).toLowerCase().includes(term));
q('#product-grid').innerHTML=list.length?list.map(p=>{
const w=weights[p.id]||100,price=calc(p.basePrice,w),out=p.stock<=0;
return `<article class="product ${out?'sold-out':''}"><div class="product-img" style="--bg:${p.bg}" data-detail="${p.id}"><span class="product-tag">${out?'Stok Habis':esc(p.tag)}</span>${bag(p,w)}<span class="quickview-pill">Detail · Stok ${p.stock}</span></div><div class="product-body"><div class="product-meta"><span>${esc(p.group.toUpperCase())} • ${w}G</span><span>Pedas ${p.spice}/5</span></div><h3 class="product-name" data-detail="${p.id}">${esc(p.name)}</h3><div class="product-desc">${esc(p.taste)}</div><div class="weight-selector">${[100,250,500].map(x=>`<button type="button" class="weight-btn ${x===w?'active':''}" data-weight="${p.id}" data-size="${x}">${x}g</button>`).join('')}</div><div class="product-bottom"><div class="price">${money(price)}<small>/${w}g</small></div><button type="button" class="add" data-add="${p.id}" ${out?'disabled':''}>${out?'Habis':'+ Tambah'}</button></div></div></article>`;
}).join(''):'<div class="empty-products">Produk belum ditemukan. Coba kata kunci lain.</div>';
if(products[0]) q('#hero-bag-1').innerHTML=bag(products[0],250);
if(products[1]) q('#hero-bag-2').innerHTML=bag(products[1],250);
if(products.length) q('#about-bag').innerHTML=bag(products.at(-1),250);
if(products.length)q('#hero-min-price').textContent='Mulai '+money(Math.min(...products.map(p=>p.basePrice)))+' • Pilih ukuran 100g, 250g, hingga 500g';
}
function renderBundles(){
if(slots.every(x=>x===null)){slots=products.filter(p=>p.stock>0).slice(0,3).map(p=>p.id);while(slots.length<3)slots.push(null)}
q('#bundle-selector-btns').innerHTML=products.filter(p=>p.stock>0).map(p=>`<button class="bundle-chip-btn" data-pick="${p.id}">+ ${esc(p.subtitle)}</button>`).join('');
q('#bundle-slots').innerHTML=slots.map((id,i)=>{const p=products.find(p=>p.id===id);return p?`<div class="bundle-slot filled"><button class="bundle-slot-remove" data-remove="${i}" aria-label="Hapus">×</button>${bag(p,100)}<div class="bundle-slot-title">${esc(p.name)}</div></div>`:`<div class="bundle-slot">Slot ${i+1} kosong</div>`}).join('');
const ready=slots.length===3 && slots.every(id=>products.some(p=>p.id===id && p.stock>0));
const raw=slots.reduce((v,id)=>v+(products.find(p=>p.id===id)?.basePrice||0),0);
q('#bundle-price-display').innerHTML=ready?`<del>${money(raw)}</del> <strong>${money(Math.max(0,raw-6000))}</strong>`:'Pilih tiga rasa terlebih dahulu';
q('#add-bundle-btn').disabled=!ready;
}
function add(key,qty=1){
const combos=key.startsWith('combo_');const ids=combos?key.split('_').slice(1).map(Number):[Number(key.split('_')[0])];
const prods=ids.map(id=>products.find(p=>p.id===id));if(prods.some(p=>!p || p.stock<=0)){toast('Stok berubah. Muat ulang katalog.');return}
const w=combos?100:Number(key.split('_')[1]);
const item=combos?{key,name:'Paket Kombo 3 Rasa ('+prods.map(p=>p.subtitle).join(' + ')+')',weight:'3×100g',price:Math.max(0,prods.reduce((s,p)=>s+p.basePrice,0)-6000),bg:prods[0].bg,productId:prods[0].id,qty:0}:{key,name:prods[0].name,weight:w+'g',price:calc(prods[0].basePrice,w),bg:prods[0].bg,productId:prods[0].id,qty:0};
let cur=cart.find(x=>x.key===key);if(!cur){cur=item;cart.push(cur)}
cur.qty+=qty;renderCart();if(promoCode)verifyPromo(promoCode,true);toast('Produk ditambahkan ke keranjang');
}
function renderCart(){
// Keep saved cart lines valid against the current catalog; if a product was deleted, remove its entry.
cart=cart.filter(c=>c.key.startsWith('combo_')?c.key.split('_').slice(1).every(id=>products.some(p=>p.id===Number(id))):products.some(p=>p.id===Number(c.key.split('_')[0])));
cart.forEach(c=>{if(c.key.startsWith('combo_')){const ps=c.key.split('_').slice(1).map(id=>products.find(p=>p.id===Number(id)));c.price=Math.max(0,ps.reduce((s,p)=>s+p.basePrice,0)-6000)}else{const p=products.find(p=>p.id===Number(c.key.split('_')[0]));c.price=calc(p.basePrice,Number(c.key.split('_')[1]))}});
persist();q('#cart-count').textContent=count();const applied=promoDiscount(sum());q('#cart-discount').textContent=applied?'-'+money(applied):money(0);q('#cart-total').textContent=money(Math.max(0,sum()-applied));
q('#checkout-open').disabled=!cart.length;
const pct=Math.min(100,Math.round(sum()/50000*100));q('#ship-fill').style.width=pct+'%';q('#ship-percent').textContent=pct+'%';q('#ship-status-msg').textContent=sum()>=50000?'Gratis ongkir reguler sudah aktif':`Tambah ${money(Math.max(0,50000-sum()))} untuk gratis ongkir reguler`;
q('#apply-promo').textContent=promoCode?'Hapus Promo':'Terapkan';q('#apply-promo').classList.toggle('applied',!!promoCode);q('#promo-message').textContent=promoCode?(applied?'Promo '+promoCode+' aktif, hemat '+money(applied):'Promo '+promoCode+' membutuhkan minimal belanja '+money(promo.min_subtotal)):'Masukkan kode promo yang tersedia.';
q('#cart-items').innerHTML=cart.length?cart.map((c,i)=>{const p=products.find(p=>p.id===c.productId)||products[0];return `<div class="cart-item"><div class="thumb" style="--bg:${c.bg}">${bag(p,100)}</div><div><h4>${esc(c.name)}</h4><div class="ct-meta">Kemasan ${esc(c.weight)}</div><div class="ct-price">${money(c.price*c.qty)}</div><div class="qty"><button data-minus="${i}">−</button><span>${c.qty}</span><button data-plus="${i}">+</button></div></div><button class="delete" data-del="${i}" aria-label="Hapus">×</button></div>`}).join(''):'<div class="cart-empty"><h3>Keranjang masih kosong</h3><p>Pilih rasa dan ukuran favoritmu dari katalog.</p><button class="btn btn-primary" id="empty-shop">Lihat Katalog</button></div>';
}
function showDetail(id){const p=products.find(p=>p.id===id);if(!p)return;const w=weights[id]||100;
q('#qv-title').textContent=p.name;q('#qv-content').innerHTML=`<div class="qv-art" style="background:${p.bg}">${bag(p,w)}</div><div><span class="smallcaps">${esc(p.group)} • Sisa ${p.stock} bungkus</span><h2>${esc(p.name)}</h2><p class="muted">${esc(p.taste)}</p><div class="meter-box"><div><div class="meter-label">Kerenyahan (${p.crunch}/5)</div><div class="meter-bars">${[1,2,3,4,5].map(i=>`<span class="meter-bar ${i<=p.crunch?'on':''}"></span>`).join('')}</div></div><div><div class="meter-label">Level Pedas (${p.spice}/5)</div><div class="meter-bars">${[1,2,3,4,5].map(i=>`<span class="meter-bar ${i<=p.spice?'on':''}"></span>`).join('')}</div></div></div><h4>Komposisi</h4><p class="muted">${esc(p.ingredients||'')}</p><p style="font-size:23px;font-weight:900">${money(calc(p.basePrice,w))}</p><button class="btn btn-primary" data-qv-add="${p.id}" ${p.stock<=0?'disabled':''}>+ Tambah ke Keranjang</button></div>`;
openModal('quick')}
async function init(){try{const r=await fetch('/api/katalog',{headers:{Accept:'application/json'}});if(!r.ok)throw new Error('Katalog tidak dapat diakses.');const data=await r.json();products=data.products||[];if(!products.length)toast('Belum ada produk. Silakan tambahkan melalui panel penjual.');renderProducts();renderBundles();renderCart();if(promoCode){q('#promo-input').value=promoCode;verifyPromo(promoCode,true);}}catch(e){q('#product-grid').innerHTML=`<div class="empty-products">${esc(e.message)} Silakan muat ulang halaman atau hubungi pengelola.</div>`;toast(e.message)}}
q('#open-cart').onclick=()=>openModal('cart');q('#close-cart').onclick=()=>openModal('');q('#close-qv').onclick=()=>openModal('');q('#overlay').onclick=()=>openModal('');
q('#checkout-open').onclick=()=>{if(cart.length)location.href='/checkout'};
q('#apply-promo').onclick=async()=>{if(promoCode){promo=null;promoCode='';q('#promo-input').value='';persist();renderCart();toast('Promo dihapus.')}else await verifyPromo(q('#promo-input').value.trim().toUpperCase())};
q('#promo-input').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();q('#apply-promo').click()}});
q('#search').addEventListener('input',renderProducts);
q('#filters').addEventListener('click',e=>{const b=e.target.closest('[data-filter]');if(!b)return;filter=b.dataset.filter;document.querySelectorAll('#filters .filter').forEach(t=>t.classList.toggle('active',t===b));renderProducts()});
q('#product-grid').addEventListener('click',e=>{const b=e.target.closest('[data-weight]');if(b){weights[b.dataset.weight]=Number(b.dataset.size);renderProducts();return}const d=e.target.closest('[data-detail]');if(d){showDetail(Number(d.dataset.detail));return}const a=e.target.closest('[data-add]');if(a)add(`${a.dataset.add}_${weights[a.dataset.add]||100}`)});
q('#qv-content').addEventListener('click',e=>{const b=e.target.closest('[data-qv-add]');if(b){add(`${b.dataset.qvAdd}_${weights[b.dataset.qvAdd]||100}`);openModal('cart')}});
q('#bundle-selector-btns').addEventListener('click',e=>{const b=e.target.closest('[data-pick]');if(!b)return;const empty=slots.indexOf(null);if(empty>=0)slots[empty]=Number(b.dataset.pick);else {slots.shift();slots.push(Number(b.dataset.pick))}renderBundles()});
q('#bundle-slots').addEventListener('click',e=>{const b=e.target.closest('[data-remove]');if(b){slots[Number(b.dataset.remove)]=null;renderBundles()}});
q('#add-bundle-btn').onclick=()=>{if(slots.some(id=>!id))return;add('combo_'+slots.join('_'));openModal('cart')};
q('#cart-items').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;const i=Number(b.dataset.plus??b.dataset.minus??b.dataset.del);if(b.dataset.plus!==undefined)cart[i].qty++;else if(b.dataset.minus!==undefined){cart[i].qty--;if(cart[i].qty<=0)cart.splice(i,1)}else if(b.dataset.del!==undefined)cart.splice(i,1);else if(b.id==='empty-shop'){openModal('');q('#produk').scrollIntoView({behavior:'smooth'})}renderCart();if(promoCode)verifyPromo(promoCode,true)});
document.addEventListener('keydown',e=>{if(e.key==='Escape')openModal('')});
q('#hero-bag-1').onclick=()=>{if(products[0])showDetail(products[0].id)};q('#hero-bag-2').onclick=()=>{if(products[1])showDetail(products[1].id)};
init();
})();
