(()=>{'use strict';
const $=s=>document.querySelector(s),$$=s=>Array.from(document.querySelectorAll(s));const page=window.ADMIN_PAGE;
const money=n=>'Rp'+Math.round(Number(n)||0).toLocaleString('id-ID');
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const date=v=>v?new Date(String(v).replace(' ','T')).toLocaleString('id-ID',{dateStyle:'medium',timeStyle:'short'}):'-';
let products=[],orders=[],settings={},daily={},promotions=[],gatewayReady=false,posCart=[],selectedDate='';
async function api(path,method='GET',payload){const opt={method,credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':$('meta[name="csrf-token"]').content}};if(payload instanceof FormData){opt.body=payload;}else if(payload!==undefined){opt.headers['Content-Type']='application/json';opt.body=JSON.stringify(payload);}const r=await fetch(path,opt);const body=await r.json().catch(()=>({}));if(!r.ok)throw Error(Object.values(body.errors||{}).flat().join(' ')||body.message||'Permintaan gagal');return body}
async function load(){const d=await api('/admin/api/data'+(selectedDate?'?date='+selectedDate:''));products=d.products||[];orders=d.orders||[];settings=d.settings||{};promotions=d.promotions||[];gatewayReady=!!d.midtrans_ready;daily=d.daily||{};selectedDate=d.date||selectedDate;render()}
function showError(ex,id){const el=$(id);if(el){el.textContent=ex.message||String(ex);el.style.display='block'}else alert(ex.message||ex)}
function infoCard(title,n,sub=''){return `<div class="stat-unit"><span>${title}</span><strong>${n}</strong><small class="subheading">${sub}</small></div>`}
function stats(){const lows=products.filter(p=>p.stock<=p.low_stock_threshold);return [infoCard('Pendapatan Hari Ini',money(daily.revenue),'Dari pembayaran berhasil'),infoCard('Jumlah Transaksi',daily.transactions||0,'Penjualan online & kasir'),infoCard('Estimasi Keuntungan',money(daily.profit),'Setelah harga modal produk'),infoCard('Stok Perlu Perhatian',lows.length,'Produk hampir habis')].join('')}
function render(){if(page==='dashboard')return dashboard();if(page==='products')return productTable();if(page==='pos')return posProducts();if(page==='orders')return ordersTable();if(page==='reports')return reports();if(page==='history')return historyTable();if(page==='settings')return settingsForm();if(page==='promos')return promoTable()}
function dashboard(){ $('#stats').innerHTML=stats();const lows=products.filter(p=>p.stock<=p.low_stock_threshold);$('#low-stock').innerHTML=lows.length?lows.map(p=>`<div class="cash-line"><span>${esc(p.name)}</span><span class="chip-warning">Sisa ${p.stock} bungkus</span></div>`).join(''):'<p class="muted">Stok produk dalam kondisi aman.</p>';$('#recent-orders').innerHTML=orders.slice(0,5).map(o=>`<div class="cash-line"><div><strong>${esc(o.order_code)}</strong><div class="muted">${esc(o.buyer_name)} · ${esc(o.channel)}</div></div><b>${money(o.total_amount)}</b></div>`).join('')||'<p>Belum ada transaksi.</p>'}
function productTable(){const term=$('#prod-filter').value.toLowerCase(),group=$('#category-filter').value;$('#product-list').innerHTML=products.filter(p=>(!group||p.group===group)&&p.name.toLowerCase().includes(term)).map(p=>`<tr><td>${p.image_url?`<img class="product-thumb-mini" src="${esc(p.image_url)}" alt="Foto ${esc(p.name)}" loading="lazy">`:''}<strong>${esc(p.name)}</strong><div class="muted">${esc(p.subtitle)}</div></td><td>${esc(p.group)}</td><td>${money(p.base_price)}</td><td>${money(p.base_cost)}</td><td>${p.stock<=p.low_stock_threshold?`<span class="chip-warning">${p.stock} · Stok rendah</span>`:p.stock}</td><td>${p.crunch}/5</td><td>${p.spice}/5</td><td><button class="small-button primary" data-edit="${p.id}">Edit</button> <button class="small-button" data-delete="${p.id}">Nonaktifkan</button></td></tr>`).join('')||'<tr><td colspan="8">Belum ada produk.</td></tr>'}
let savedColors={bg:'#f7e6cb',bag:'#d96b27',label:'#fff3df'};
function colorPreview(){
  const f=$('#product-form');if(!f)return;
  const bg=f.elements.bg.value,bag=f.elements.bag.value,label=f.elements.label.value;
  const name=f.elements.subtitle.value.trim().toUpperCase().slice(0,25)||'KERIPIK LOKAL';
  $('#color-preview-art').style.background=bg;
  $('#color-preview-art').innerHTML=`<svg viewBox="0 0 200 260" role="img" aria-label="Contoh desain kemasan"><path d="M30 20Q30 10 40 10H160Q170 10 170 20L164 245H36Z" fill="${bag}" stroke="#77563d" stroke-width="2"/><path d="M30 20H170L168 35H32Z" fill="#f3dfbb"/><ellipse cx="100" cy="131" rx="66" ry="82" fill="${label}"/><text x="100" y="90" font-size="15" font-weight="900" text-anchor="middle" fill="#242019">KRIUKKITA</text><text x="100" y="119" font-size="10" text-anchor="middle" fill="#242019">${esc(name)}</text><ellipse cx="82" cy="162" rx="25" ry="16" fill="#eac05d" stroke="#b58c42"/><ellipse cx="118" cy="169" rx="25" ry="16" fill="#f6d184" stroke="#b58c42"/><text x="100" y="219" font-size="11" text-anchor="middle" fill="#fff">KERIPIK LOKAL</text></svg>`;
  $('#color-preview-hex').textContent=`Latar ${bg}  |  Kemasan ${bag}  |  Label ${label}`;
  $('#color-preview-title').textContent=f.elements.name.value||'Pratinjau Kemasan';
}
let previewBlob=null;
function setPhotoPreview(url){const el=$('#photo-preview');el.innerHTML=url?`<img src="${esc(url)}" alt="Pratinjau foto produk">`:'<span>Belum ada foto — ilustrasi kemasan tetap digunakan.</span>'}
function openProduct(p){const form=$('#product-form');form.reset();if(previewBlob){URL.revokeObjectURL(previewBlob);previewBlob=null}form.elements.id.value=p?.id??'';const keys=['name','subtitle','group','tag','base_price','base_cost','stock','low_stock_threshold','crunch','spice','taste','ingredients','bg','bag','label'];keys.forEach(k=>{if(p&&form.elements[k])form.elements[k].value=p[k]??''});if(!p){form.elements.group.value='gurih';form.elements.tag.value='Pilihan';form.elements.low_stock_threshold.value=10;form.elements.crunch.value=5;form.elements.spice.value=0;form.elements.bg.value='#f7e6cb';form.elements.bag.value='#d96b27';form.elements.label.value='#fff3df'}savedColors={bg:form.elements.bg.value,bag:form.elements.bag.value,label:form.elements.label.value};colorPreview();$('#color-preview-status').textContent='Pilihan awal ditampilkan. Geser warna untuk melihat perubahan.';setPhotoPreview(p?.image_url||null);$('#photo-remove-row').style.display=p?.image_path?'flex':'none';$('#photo-remove').checked=false;$('#modal-title').textContent=p?'Edit Produk':'Tambah Produk Baru';$('#product-sheet').classList.add('open');$('#product-error').style.display='none'}
function orderStatusSelect(o){const choices=['Sedang Dikemas','Dikirim','Selesai'];if(o.payment_status!=='paid')return esc(o.status);return `<select class="status-select" data-order="${esc(o.order_code)}">${choices.map(x=>`<option ${o.status===x?'selected':''}>${x}</option>`).join('')}</select>`}
function ordersTable(){const list=orders.filter(x=>x.channel==='online');$('#orders-list').innerHTML=list.map(o=>`<tr><td><strong>${esc(o.order_code)}</strong></td><td>${esc(o.buyer_name)}<div class="muted">${esc(o.buyer_city||'')}</div></td><td>${esc(o.payment_method)}</td><td>${money(o.total_amount)}</td><td><span class="pill">${esc(o.payment_status)}</span></td><td>${orderStatusSelect(o)}</td><td>${date(o.created_at)}</td></tr>`).join('')||'<tr><td colspan="7">Belum ada pesanan online.</td></tr>'}
function reports(){$('#report-date').value=selectedDate;$('#stats').innerHTML=stats();const list=orders.filter(o=>o.payment_status==='paid'&&String(o.paid_at||'').slice(0,10)===selectedDate);$('#daily-orders').innerHTML=list.map(o=>`<div class="cash-line"><div><b>${esc(o.order_code)}</b><div class="muted">${esc(o.buyer_name)} · ${esc(o.channel)}</div></div><b>${money(o.subtotal-o.discount)}</b></div>`).join('')||'<p class="muted">Belum ada transaksi berhasil pada tanggal ini.</p>'}
function historyTable(){const from=$('#history-from').value,to=$('#history-to').value,channel=$('#history-channel').value;const found=orders.filter(o=>(!from||o.created_at.slice(0,10)>=from)&&(!to||o.created_at.slice(0,10)<=to)&&(!channel||o.channel===channel));$('#history-list').innerHTML=found.map(o=>`<tr><td><strong>${esc(o.order_code)}</strong></td><td>${date(o.created_at)}</td><td>${o.channel==='kasir'?'Kasir Tunai':'Online'}</td><td>${esc(o.buyer_name)}</td><td>${money(o.total_amount)}</td><td>${esc(o.payment_status)}</td><td>${o.receipt_token&&o.payment_status==='paid'?`<a class="link" href="/struk/${encodeURIComponent(o.receipt_token)}" target="_blank">Buka Struk</a>`:'—'}</td></tr>`).join('')||'<tr><td colspan="7">Tidak ditemukan transaksi pada filter ini.</td></tr>'}
function settingsForm(){const status=$('#gateway-status');if(status)status.textContent=gatewayReady?'Kunci Midtrans sudah terisi. Lakukan pembayaran uji di Sandbox untuk memastikan integrasi berfungsi.':'Payment Gateway belum aktif. Isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY dari Dashboard Midtrans Sandbox ke file .env, lalu jalankan php artisan optimize:clear.';const f=$('#settings-form');['ribbon','about_title','about_description','reg_shipping','exp_shipping'].forEach(k=>f.elements[k].value=settings[k]??'')}
function promoTable(){
  $('#promo-list').innerHTML=promotions.map(x=>`<tr><td><strong>${esc(x.code)}</strong></td><td>${esc(x.description||'—')}</td><td>${x.type==='percent'?x.value+'%':money(x.value)}${x.max_discount?' (maks. '+money(x.max_discount)+')':''}</td><td>${money(x.min_subtotal)}</td><td>${esc(x.starts_on||'Bebas')} s/d ${esc(x.ends_on||'Bebas')}</td><td><span class="pill">${x.is_active?'Aktif':'Nonaktif'}</span></td><td><button class="small-button primary" data-promo-edit="${x.id}">Edit</button> <button class="small-button" data-promo-delete="${x.id}">Hapus</button></td></tr>`).join('')||'<tr><td colspan="7">Belum ada kode promo.</td></tr>';
}
function openPromo(x){
  const form=$('#promo-form');form.reset();form.elements.id.value=x?.id||'';
  for(const key of ['code','description','type','value','min_subtotal','max_discount','starts_on','ends_on'])if(x&&form.elements[key])form.elements[key].value=x[key]??'';
  if(!x){form.elements.type.value='fixed';form.elements.min_subtotal.value=0;}
  form.elements.is_active.checked=x?!!x.is_active:true;
  $('#promo-modal-title').textContent=x?'Edit Kode Promo':'Tambah Kode Promo';$('#promo-form-error').style.display='none';$('#promo-sheet').classList.add('open');
}
function price(base,weight){return Math.round(base*({100:1,250:2.2,500:4}[weight])/500)*500}
function posProducts(){$('#pos-products').innerHTML=products.map(p=>`<button class="pos-card" data-pos-add="${p.id}" ${p.stock<=0?'disabled':''}><div class="pos-pouch" style="background:${p.bg}">${p.image_url?`<img src="${esc(p.image_url)}" alt="Foto ${esc(p.name)}" loading="lazy">`:`<svg viewBox="0 0 90 120" role="img" aria-label="Kemasan keripik"><path d="M13 10Q13 4 21 4h48q8 0 8 6L74 111H16z" fill="${p.bag}"/><rect x="20" y="24" width="50" height="62" rx="20" fill="${p.label}"/><ellipse cx="37" cy="65" rx="14" ry="9" fill="#ebba62"/><ellipse cx="55" cy="67" rx="14" ry="9" fill="#f9d88c"/><text x="45" y="41" font-size="9" text-anchor="middle" font-weight="bold">KRIUKKITA</text></svg>`}</div><strong>${esc(p.name)}</strong><span>${money(price(p.base_price,Number($('#pos-weight').value)))}</span><div class="muted" style="font-size:11px">Stok: ${p.stock}</div></button>`).join('');posRender()}
function posRender(){let total=0;$('#pos-cart').innerHTML=posCart.length?posCart.map((x,i)=>{const p=products.find(p=>p.id===x.id);if(!p)return '';const cost=price(p.base_price,x.weight)*x.qty;total+=cost;return `<div class="cash-line"><div><b>${esc(p.name)}</b><div class="muted">${x.weight}g • ${money(cost)}</div></div><div class="cart-inline"><button data-qty-minus="${i}">−</button><span>${x.qty}</span><button data-qty-plus="${i}">+</button></div></div>`}).join(''):'<p class="muted">Belum ada produk di penjualan ini.</p>';$('#pos-total').textContent=money(total);const paid=Number($('#pos-cash').value||0);$('#pos-change').textContent=paid<total?'Uang kurang '+money(total-paid):money(paid-total);$('#pos-complete').disabled=!posCart.length||paid<total;}
function posAdd(id){const weight=Number($('#pos-weight').value);const p=products.find(p=>p.id===id);const same=posCart.find(x=>x.id===id&&x.weight===weight);const total=posCart.filter(x=>x.id===id).reduce((n,x)=>n+x.qty,0);if(!p||p.stock<total+1)return alert('Stok tidak mencukupi.');if(same)same.qty++;else posCart.push({id,weight,qty:1});posRender()}
if(page==='products'){
$('#prod-filter').oninput=productTable;$('#category-filter').onchange=productTable;$('#new-product').onclick=()=>openProduct(null);$('#modal-close').onclick=()=>$('#product-sheet').classList.remove('open');$('#product-sheet').onclick=e=>{if(e.target.id==='product-sheet')e.target.classList.remove('open')};$('#product-list').onclick=async e=>{const edit=e.target.closest('[data-edit]'),del=e.target.closest('[data-delete]');if(edit){openProduct(products.find(x=>x.id===Number(edit.dataset.edit)));return}if(del&&confirm('Nonaktifkan produk ini? Riwayat penjualan tidak ikut terhapus.')){try{await api('/admin/api/products/'+del.dataset.delete,'DELETE');await load()}catch(x){alert(x.message)}}};
['bg','bag','label'].forEach(key=>$('#product-form').elements[key].addEventListener('input',()=>{colorPreview();$('#color-preview-status').textContent='Pratinjau berubah. Klik Terapkan Warna Ini untuk konfirmasi pilihan.'}));
$('#product-form').elements.subtitle.addEventListener('input',colorPreview);
$('#color-apply').onclick=()=>{colorPreview();$('#color-preview-status').textContent='Warna pilihan diterapkan ke pratinjau. Klik Simpan Produk untuk menyimpan ke toko.'};
$('#color-reset').onclick=()=>{const f=$('#product-form');for(const [key,value] of Object.entries(savedColors))f.elements[key].value=value;colorPreview();$('#color-preview-status').textContent='Warna dikembalikan seperti saat form dibuka.'};
$('#photo-file').onchange=e=>{
  const file=e.target.files?.[0];
  if(previewBlob){URL.revokeObjectURL(previewBlob);previewBlob=null}
  if(file){if(file.size>4*1024*1024){e.target.value='';showError(new Error('Ukuran foto maksimal 4 MB.'),'#product-error');return;}previewBlob=URL.createObjectURL(file);setPhotoPreview(previewBlob);$('#photo-remove').checked=false;}
  else {const id=Number($('#product-form').elements.id.value);const p=products.find(p=>p.id===id);setPhotoPreview(p?.image_url||null)}
};
$('#photo-remove').onchange=e=>{if(e.target.checked){$('#photo-file').value='';if(previewBlob){URL.revokeObjectURL(previewBlob);previewBlob=null}setPhotoPreview(null);}else{const id=Number($('#product-form').elements.id.value);const p=products.find(p=>p.id===id);setPhotoPreview(p?.image_url||null)}};
$('#product-form').onsubmit=async e=>{
  e.preventDefault();const form=e.target;const id=form.elements.id.value;
  const data=new FormData(form);data.delete('id');
  // Browser file uploads need a POST request; Laravel handles PUT via _method.
  if(id)data.append('_method','PUT');
  try{
    await api('/admin/api/products'+(id?'/'+id:''),'POST',data);
    $('#product-sheet').classList.remove('open');
    if(previewBlob){URL.revokeObjectURL(previewBlob);previewBlob=null}
    await load();
  }catch(x){showError(x,'#product-error')}
};}
if(page==='pos'){$('#pos-weight').onchange=posProducts;$('#pos-products').onclick=e=>{const b=e.target.closest('[data-pos-add]');if(b)posAdd(Number(b.dataset.posAdd))};$('#pos-cart').onclick=e=>{const plus=e.target.closest('[data-qty-plus]'),minus=e.target.closest('[data-qty-minus]');const i=Number((plus||minus)?.dataset.qtyPlus??(plus||minus)?.dataset.qtyMinus);if(plus){const x=posCart[i],p=products.find(p=>p.id===x.id);if(x.qty<p.stock)x.qty++}if(minus){posCart[i].qty--;if(posCart[i].qty<=0)posCart.splice(i,1)}posRender()};$('#pos-cash').oninput=posRender;
$('#pos-complete').onclick=async()=>{const btn=$('#pos-complete');btn.disabled=true;$('#pos-error').style.display='none';try{const data=await api('/admin/api/pos','POST',{buyer:$('#pos-buyer').value.trim()||'Pelanggan Toko',cash_received:Number($('#pos-cash').value),items:posCart.map(x=>({key:`${x.id}_${x.weight}`,qty:x.qty}))});posCart=[];$('#pos-cash').value='';$('#pos-buyer').value='Pelanggan Toko';await load();$('#pos-receipt').innerHTML=`<div class="notice"><b>Penjualan tersimpan!</b><p>Nomor ${esc(data.code)} • Kembalian ${money(data.change)}</p><a class="btn btn-primary wide" href="${esc(data.receipt_url)}" target="_blank" rel="noopener">Buka, Bagikan, atau Cetak Struk</a></div>`;posRender()}catch(ex){showError(ex,'#pos-error')}finally{btn.disabled=false}};}
if(page==='orders')$('#orders-list').onchange=async e=>{const b=e.target.closest('[data-order]');if(!b)return;try{await api('/admin/api/orders/'+encodeURIComponent(b.dataset.order),'PATCH',{status:b.value});await load()}catch(ex){alert(ex.message);await load()}};
if(page==='reports')$('#report-date').onchange=e=>{selectedDate=e.target.value;load().catch(ex=>alert(ex.message))};
if(page==='history'){for(const id of ['#history-from','#history-to','#history-channel'])$(id).onchange=historyTable;$('#history-reset').onclick=()=>{for(const id of ['#history-from','#history-to','#history-channel'])$(id).value='';historyTable()}};
if(page==='promos'){
  $('#new-promo').onclick=()=>openPromo(null);
  $('#promo-modal-close').onclick=()=>$('#promo-sheet').classList.remove('open');
  $('#promo-sheet').onclick=e=>{if(e.target.id==='promo-sheet')$('#promo-sheet').classList.remove('open')};
  $('#promo-list').onclick=async e=>{
    const edit=e.target.closest('[data-promo-edit]'),del=e.target.closest('[data-promo-delete]');
    if(edit)return openPromo(promotions.find(x=>x.id===Number(edit.dataset.promoEdit)));
    if(del&&confirm('Hapus kode promo ini? Pesanan lama tetap tersimpan.')){try{await api('/admin/api/promos/'+del.dataset.promoDelete,'DELETE');await load()}catch(ex){alert(ex.message)}}
  };
  $('#promo-form').onsubmit=async e=>{
    e.preventDefault();const f=e.target,id=f.elements.id.value;
    const data=Object.fromEntries(new FormData(f));delete data.id;
    data.code=(data.code||'').trim().toUpperCase();data.is_active=f.elements.is_active.checked;
    for(const field of ['value','min_subtotal'])data[field]=Number(data[field]);
    data.max_discount=data.max_discount?Number(data.max_discount):null;
    data.description=data.description||null;data.starts_on=data.starts_on||null;data.ends_on=data.ends_on||null;
    try{await api('/admin/api/promos'+(id?'/'+id:''),id?'PUT':'POST',data);$('#promo-sheet').classList.remove('open');await load()}catch(ex){showError(ex,'#promo-form-error')}
  };
}
if(page==='settings')$('#settings-form').onsubmit=async e=>{e.preventDefault();const d=Object.fromEntries(new FormData(e.target));d.reg_shipping=Number(d.reg_shipping);d.exp_shipping=Number(d.exp_shipping);try{await api('/admin/api/settings','PUT',d);await load();alert('Pengaturan berhasil disimpan.')}catch(ex){alert(ex.message)}};
load().catch(ex=>{alert('Data admin belum dapat dimuat: '+ex.message)});
})();
