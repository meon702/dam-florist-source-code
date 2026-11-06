
/** DẶM FLORIST - Bouquet Builder WooCommerce (Code Snippets → PHP)
 * Shortcode: [dam_bouquet_builder product_id="123"]
 * Cần tạo sản phẩm WooCommerce loại "Sản phẩm đơn giản", trạng thái Đã đăng.
 */
<?php
if (!defined('ABSPATH')) exit;

function dam_bb_prices($cfg) {
    $unit = ['rose'=>45000,'tulip'=>55000,'hydrangea'=>80000,'peony'=>95000,'daisy'=>30000,'sunflower'=>50000];
    $base = ['small'=>65000,'medium'=>100000,'large'=>150000];
    $factor = ['small'=>1,'medium'=>1.2,'large'=>1.5];
    if (!is_array($cfg) || !isset($base[$cfg['size']??'']) || !isset($factor[$cfg['size']??'']) || !in_array($cfg['tone']??'', ['lavender','pink','cream','warm'],true) || !in_array($cfg['wrap']??'', ['cream','purple','pink'],true)) return new WP_Error('invalid','Lựa chọn không hợp lệ.');
    $flowers=$cfg['flowers']??null; if (!is_array($flowers)) return new WP_Error('invalid','Thiếu loại hoa.');
    $sum=0;$qty=0;$clean=[];
    foreach ($unit as $key=>$p) {
        $raw=$flowers[$key]??0;
        if (!is_numeric($raw) || (string)(int)$raw!==(string)$raw || $raw<0 || $raw>8) return new WP_Error('invalid','Số lượng hoa không hợp lệ.');
        $n=(int)$raw;$clean[$key]=$n;$qty+=$n;$sum+=$n*$p;
    }
    $max=['small'=>8,'medium'=>12,'large'=>18];
    if ($qty<1 || $qty>$max[$cfg['size']]) return new WP_Error('invalid','Vui lòng chọn số cành phù hợp kích thước.');
    return ['price'=>(float)(round(($base[$cfg['size']]+$sum*$factor[$cfg['size']]+($cfg['wrap']==='purple'?20000:0))/1000)*1000),
      'config'=>['size'=>$cfg['size'],'tone'=>$cfg['tone'],'wrap'=>$cfg['wrap'],'flowers'=>$clean,'recipient'=>sanitize_text_field(substr((string)($cfg['recipient']??''),0,80)),'card'=>sanitize_textarea_field(substr((string)($cfg['card']??''),0,300))]];
}
function dam_bb_labels($cfg) {
    $names=['rose'=>'Hoa hồng','tulip'=>'Tulip','hydrangea'=>'Cẩm tú cầu','peony'=>'Mẫu đơn','daisy'=>'Cúc họa mi','sunflower'=>'Hướng dương'];
    $tone=['lavender'=>'Tím mộng mơ','pink'=>'Hồng ngọt ngào','cream'=>'Trắng tinh khôi','warm'=>'Cam ấm áp'];
    $size=['small'=>'Nhỏ','medium'=>'Vừa','large'=>'Lớn'];$wrap=['cream'=>'Kem sữa','purple'=>'Tím khói','pink'=>'Hồng phấn'];
    $flower=[];foreach($names as $key=>$name)if(!empty($cfg['flowers'][$key]))$flower[]=$name.' × '.(int)$cfg['flowers'][$key];
    return ['Tone màu'=>$tone[$cfg['tone']],'Thành phần'=>implode(', ',$flower),'Kích thước'=>$size[$cfg['size']],'Giấy gói'=>$wrap[$cfg['wrap']],'Người nhận'=>$cfg['recipient'],'Lời nhắn'=>$cfg['card']];
}
add_shortcode('dam_bouquet_builder',function($atts){
    if(!class_exists('WooCommerce'))return '<p>Vui lòng bật WooCommerce.</p>';
    $atts=shortcode_atts(['product_id'=>0],$atts);
    $product=wc_get_product(absint($atts['product_id']));
    if(!$product || !$product->is_type('simple') || !$product->is_purchasable() || !$product->is_in_stock())return '<p>Vui lòng tạo sản phẩm đơn giản đang bán và nhập đúng product_id vào shortcode.</p>';
    ob_start(); ?>
<style>

.dam-builder,.dam-builder *{box-sizing:border-box}.dam-builder{background:#fbf9f7;color:#392c43;font-family:Arial,sans-serif;--purple:#76528c;--edge:#e7dce8;max-width:1240px;margin:auto;padding:42px 22px 85px;font-size:15px}.dam-builder button,.dam-builder input,.dam-builder textarea{font:inherit}.dam-builder button{cursor:pointer}.dam-hero{text-align:center;margin:0 0 35px}.dam-eyebrow{letter-spacing:.22em;font-size:11px;color:var(--purple);font-weight:700}.dam-hero h1{font-family:Georgia,serif;font-size:clamp(33px,4vw,58px);font-weight:400;margin:12px 0;color:#38283f}.dam-hero p{color:#86778d;line-height:1.7}.dam-layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(340px,.95fr);gap:28px;align-items:start}.dam-preview{position:sticky;top:20px}.dam-canvas{position:relative;background:linear-gradient(150deg,#faf3fa,#ece1f0 70%,#f9f2e7);border-radius:22px;overflow:hidden;min-height:520px;display:grid;place-items:center}.dam-canvas svg{width:100%;max-width:490px;height:auto;display:block}.dam-preview-label{position:absolute;top:20px;left:20px;background:#fff;border-radius:40px;padding:10px 14px;font-size:11px;letter-spacing:.09em;color:#76528c}.dam-caption{display:flex;justify-content:space-between;gap:10px;padding:16px 4px;color:#817389;font-size:12px}.dam-panel{background:white;border:1px solid var(--edge);border-radius:18px;padding:27px}.dam-section{padding:0 0 25px;margin-bottom:24px;border-bottom:1px solid #eadfeb}.dam-section:last-of-type{border:0;margin-bottom:0}.dam-section h2{font-family:Georgia,serif;font-size:25px;font-weight:400;margin:0 0 17px}.dam-small{color:#94879c;font-size:12px;margin:-7px 0 15px}.dam-options{display:grid;grid-template-columns:repeat(4,1fr);gap:9px}.dam-tone,.dam-wrap{border:1px solid #e8dfe8;background:#fff;border-radius:12px;padding:13px 4px;color:#685873;font-size:12px;min-height:78px}.dam-tone[aria-pressed=true],.dam-wrap[aria-pressed=true],.dam-size[aria-pressed=true]{border:2px solid var(--purple);background:#faf6fc}.dam-swatch{display:block;width:28px;height:28px;border-radius:50%;margin:0 auto 9px;border:2px solid white;box-shadow:0 0 0 1px #e2d9e5}.dam-flower-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.dam-flower-item{border:1px solid var(--edge);border-radius:12px;padding:12px;display:flex;align-items:center;gap:10px}.dam-flower-icon{width:37px;height:37px;border-radius:10px;background:#f5edf6;display:grid;place-items:center;font-size:24px;flex-shrink:0}.dam-flower-label{flex:1;min-width:0}.dam-flower-label b{display:block;font-size:13px}.dam-flower-label small{display:block;color:#958a9b;margin-top:3px;font-size:11px}.dam-count{display:flex;align-items:center;gap:7px}.dam-count button{width:26px;height:26px;border:1px solid #e2d7e4;background:#fff;border-radius:50%;color:#76528c}.dam-count output{min-width:12px;text-align:center;font-weight:bold}.dam-size-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.dam-size{background:#fff;border:1px solid var(--edge);border-radius:10px;padding:14px 5px;color:#594967}.dam-size b{display:block;font-size:13px}.dam-size small{display:block;color:#97899b;margin-top:5px}.dam-field{display:grid;gap:7px;margin:13px 0}.dam-field label{font-size:13px;font-weight:600}.dam-field textarea,.dam-field input{width:100%;border:1px solid #e7dce8;border-radius:10px;padding:12px;color:#392c43;background:#fff}.dam-summary{background:#f7f0f9;border-radius:13px;padding:19px;margin-top:10px}.dam-summary-line{display:flex;justify-content:space-between;margin:9px 0;gap:12px;font-size:13px}.dam-total{border-top:1px solid #e3d4e7;padding-top:14px;margin-top:15px;font-size:20px;font-weight:bold;color:#68437e}.dam-primary{border:0;background:#735087;color:white;width:100%;border-radius:12px;padding:16px;margin-top:16px;font-weight:700}.dam-primary:disabled{opacity:.5;cursor:wait}.dam-feedback{font-size:13px;margin-top:12px;line-height:1.6}.dam-note{color:#8c7e91;font-size:11px;line-height:1.65;margin-top:12px}.dam-feedback.error{color:#b23445}.dam-feedback.success{color:#356d48}@media(max-width:850px){.dam-layout{grid-template-columns:1fr}.dam-preview{position:static}.dam-canvas{min-height:340px}.dam-canvas svg{max-width:380px}.dam-builder{padding:30px 14px 60px}}@media(max-width:430px){.dam-panel{padding:18px}.dam-options{grid-template-columns:repeat(2,1fr)}.dam-flower-grid{grid-template-columns:1fr}.dam-canvas{min-height:310px}}

</style>

<div class="dam-builder" data-ajax="__AJAX__" data-nonce="__NONCE__" data-product="__PRODUCT__">
<header class="dam-hero"><div class="dam-eyebrow">DESIGNED BY YOU · ARRANGED BY DẶM</div><h1>Một bó hoa, mang dấu ấn của bạn.</h1><p>Chọn từng bông hoa, sắc màu và giấy gói. Bó hoa của bạn sẽ thay đổi ngay khi thiết kế.</p></header>
<div class="dam-layout"><section class="dam-preview"><div class="dam-canvas"><div class="dam-preview-label">✦ BÓ HOA CỦA BẠN</div><svg id="dam-svg" viewBox="0 0 500 560" role="img" aria-label="Bản phác họa bó hoa theo lựa chọn của bạn"></svg></div><div class="dam-caption"><span>Ảnh mô phỏng cách phối, không phải ảnh thành phẩm</span><span>Made with ♡ by Dặm</span></div></section>
<section class="dam-panel"><div class="dam-section"><h2>01. Bảng màu yêu thích</h2><div class="dam-options"><button type="button" class="dam-tone" data-tone="lavender"><i class="dam-swatch" style="background:#aa8abb"></i>Tím mộng mơ</button><button type="button" class="dam-tone" data-tone="pink"><i class="dam-swatch" style="background:#efadc7"></i>Hồng ngọt ngào</button><button type="button" class="dam-tone" data-tone="cream"><i class="dam-swatch" style="background:#eee9d9"></i>Trắng tinh khôi</button><button type="button" class="dam-tone" data-tone="warm"><i class="dam-swatch" style="background:#eeb77a"></i>Cam ấm áp</button></div></div>
<div class="dam-section"><h2>02. Chọn từng bông hoa</h2><p class="dam-small">Nhấn + hoặc − để điều chỉnh số cành. <span id="dam-limit"></span></p><div class="dam-flower-grid">
<div class="dam-flower-item" data-flower="rose"><span class="dam-flower-icon">🌹</span><span class="dam-flower-label"><b>Hoa hồng</b><small>45.000đ/cành</small></span><div class="dam-count"><button type="button" data-step="minus" aria-label="Giảm Hoa hồng">−</button><output>0</output><button type="button" data-step="plus" aria-label="Thêm Hoa hồng">+</button></div></div>
<div class="dam-flower-item" data-flower="tulip"><span class="dam-flower-icon">🌷</span><span class="dam-flower-label"><b>Tulip</b><small>55.000đ/cành</small></span><div class="dam-count"><button type="button" data-step="minus" aria-label="Giảm Tulip">−</button><output>0</output><button type="button" data-step="plus" aria-label="Thêm Tulip">+</button></div></div>
<div class="dam-flower-item" data-flower="hydrangea"><span class="dam-flower-icon">💠</span><span class="dam-flower-label"><b>Cẩm tú cầu</b><small>80.000đ/cành</small></span><div class="dam-count"><button type="button" data-step="minus" aria-label="Giảm Cẩm tú cầu">−</button><output>0</output><button type="button" data-step="plus" aria-label="Thêm Cẩm tú cầu">+</button></div></div>
<div class="dam-flower-item" data-flower="peony"><span class="dam-flower-icon">🌺</span><span class="dam-flower-label"><b>Mẫu đơn</b><small>95.000đ/cành</small></span><div class="dam-count"><button type="button" data-step="minus" aria-label="Giảm Mẫu đơn">−</button><output>0</output><button type="button" data-step="plus" aria-label="Thêm Mẫu đơn">+</button></div></div>
<div class="dam-flower-item" data-flower="daisy"><span class="dam-flower-icon">🌼</span><span class="dam-flower-label"><b>Cúc họa mi</b><small>30.000đ/cành</small></span><div class="dam-count"><button type="button" data-step="minus" aria-label="Giảm Cúc họa mi">−</button><output>0</output><button type="button" data-step="plus" aria-label="Thêm Cúc họa mi">+</button></div></div>
<div class="dam-flower-item" data-flower="sunflower"><span class="dam-flower-icon">🌻</span><span class="dam-flower-label"><b>Hướng dương</b><small>50.000đ/cành</small></span><div class="dam-count"><button type="button" data-step="minus" aria-label="Giảm Hướng dương">−</button><output>0</output><button type="button" data-step="plus" aria-label="Thêm Hướng dương">+</button></div></div>
</div></div>
<div class="dam-section"><h2>03. Kích thước bó hoa</h2><div class="dam-size-grid"><button type="button" class="dam-size" data-size="small"><b>Nhỏ</b><small>≤ 8 cành</small></button><button type="button" class="dam-size" data-size="medium"><b>Vừa</b><small>≤ 12 cành</small></button><button type="button" class="dam-size" data-size="large"><b>Lớn</b><small>≤ 18 cành</small></button></div></div>
<div class="dam-section"><h2>04. Giấy gói</h2><div class="dam-options"><button type="button" class="dam-wrap" data-wrap="cream"><i class="dam-swatch" style="background:#e9d9c4"></i>Kem sữa</button><button type="button" class="dam-wrap" data-wrap="purple"><i class="dam-swatch" style="background:#baa3c7"></i>Tím khói (+20k)</button><button type="button" class="dam-wrap" data-wrap="pink"><i class="dam-swatch" style="background:#e7b7c4"></i>Hồng phấn</button></div></div>
<div class="dam-section"><h2>05. Lời nhắn riêng</h2><div class="dam-field"><label for="dam-recipient">Tên người nhận (không bắt buộc)</label><input id="dam-recipient" maxlength="80" placeholder="Ví dụ: Gửi đến em/anh" /></div><div class="dam-field"><label for="dam-card">Lời nhắn trên thiệp (không bắt buộc)</label><textarea id="dam-card" maxlength="300" rows="3" placeholder="Viết những điều bạn muốn gửi gắm..."></textarea></div></div>
<div class="dam-summary"><strong>Thiết kế của bạn</strong><div class="dam-summary-line"><span>Thành phần</span><span id="dam-summary" style="text-align:right;max-width:65%"></span></div><div class="dam-summary-line dam-total"><span>Tạm tính</span><span id="dam-total"></span></div></div><button type="button" id="dam-add" class="dam-primary">Thêm thiết kế vào giỏ hàng →</button><div id="dam-feedback" class="dam-feedback" role="status" aria-live="polite"></div><p class="dam-note">Giá và ảnh là dự kiến. Dặm sẽ phối thực tế tùy mùa hoa.</p></section></div></div>

<script>

(function(){
 const el=document.querySelector('.dam-builder');if(!el)return;
 const $=s=>el.querySelector(s),$$=s=>Array.from(el.querySelectorAll(s));
 const types=[{id:'rose',name:'Hoa hồng',icon:'🌹',price:45000},{id:'tulip',name:'Tulip',icon:'🌷',price:55000},{id:'hydrangea',name:'Cẩm tú cầu',icon:'💠',price:80000},{id:'peony',name:'Mẫu đơn',icon:'🌺',price:95000},{id:'daisy',name:'Cúc họa mi',icon:'🌼',price:30000},{id:'sunflower',name:'Hướng dương',icon:'🌻',price:50000}];
 const tones={lavender:['#a989c5','#e6bad7','#d6c6ef'],pink:['#eb9db5','#f6c7cc','#e6a0c0'],cream:['#f9f2dc','#fff8ed','#e2ddca'],warm:['#e8a261','#f2ca7b','#e6ad96']};
 const wraps={cream:'#e9d9c4',purple:'#baa3c7',pink:'#e7b7c4'};
 const state={tone:'lavender',wrap:'cream',size:'medium',flowers:{rose:3,tulip:2,hydrangea:0,peony:0,daisy:0,sunflower:0}};
 const base={small:65000,medium:100000,large:150000};const multiplier={small:1,medium:1.2,large:1.5};
 const fmt=n=>new Intl.NumberFormat('vi-VN').format(n)+'₫';
 const count=()=>Object.values(state.flowers).reduce((a,b)=>a+b,0);
 const price=()=>Math.round((base[state.size]+types.reduce((s,t)=>s+state.flowers[t.id]*t.price,0)*multiplier[state.size]+(state.wrap==='purple'?20000:0))/1000)*1000;
 const max=()=>({small:8,medium:12,large:18})[state.size];
 const plants=()=>types.flatMap(t=>Array(state.flowers[t.id]).fill(t.id));
 const flower=(kind,x,y,r,i)=>{
 const c=tones[state.tone][i%3];const d=tones[state.tone][(i+1)%3];
 const petals=(n,rx,ry,col)=>Array.from({length:n},(_,k)=>`<ellipse cx="0" cy="-${ry*.85}" rx="${rx}" ry="${ry}" fill="${col}" stroke="#9b789a" stroke-opacity=".26" stroke-width="1" transform="rotate(${360*k/n})"/>`).join('');
 let bloom='';
 if(kind==='rose')bloom=petals(7,r*.42,r*.65,c)+`<circle r="${r*.43}" fill="${d}"/><path d="M-${r*.3},0 Q0,-${r*.7} ${r*.28},-${r*.08} Q${r*.3},${r*.3} -${r*.07},${r*.19}" fill="none" stroke="#a46886" stroke-width="2"/>`;
 else if(kind==='tulip')bloom=`<path d="M-${r*.7},${r*.6} Q-${r*.85},-${r*.2} -${r*.63},-${r*.85} L-${r*.1},-${r*.3} L${r*.3},-${r} L${r*.72},-${r*.7} Q${r*.9},${r*.1} ${r*.55},${r*.65} Z" fill="${c}" stroke="#aa7f9f" stroke-width="1.5"/><path d="M0,${r*.5} Q${r*.25},-${r*.2} ${r*.28},-${r*.7}" fill="none" stroke="${d}" stroke-width="6"/>`;
 else if(kind==='hydrangea')bloom=Array.from({length:12},(_,j)=>{const a=j*2.4,xx=Math.cos(a)*r*.65,yy=Math.sin(a)*r*.65;return `<g transform="translate(${xx} ${yy})">${petals(4,r*.21,r*.32,j%2?c:d)}<circle r="2" fill="#f8e3a4"/></g>`}).join('');
 else if(kind==='peony')bloom=petals(10,r*.45,r*.7,c)+petals(7,r*.29,r*.42,d)+`<circle r="${r*.17}" fill="#f3d9a5"/>`;
 else if(kind==='daisy')bloom=petals(11,r*.20,r*.74,c)+`<circle r="${r*.3}" fill="#efce77"/>`;
 else bloom=petals(13,r*.23,r*.79,'#ebc259')+`<circle r="${r*.49}" fill="#806046"/><circle r="${r*.32}" fill="#9d7649"/>`;
 return `<g transform="translate(${x} ${y}) rotate(${(i%5-2)*9})">${bloom}</g>`;
 };
 function draw(){const items=plants();let stems='',blooms='';const n=items.length;const positions=[];
 // Fixed deterministic spiral, not random: updates stay visually stable.
 items.forEach((kind,i)=>{const angle=i*2.39996;const radius=Math.sqrt((i+.5)/Math.max(n,1))*112;const x=250+Math.cos(angle)*radius;const y=215+Math.sin(angle)*radius*.77;positions.push({kind,x,y,i})});
 positions.sort((a,b)=>a.y-b.y).forEach(({kind,x,y,i})=>{stems+=`<path d="M${x} ${y+16} Q${x+14} ${y+130} 250 454" stroke="#6d9272" stroke-width="4" fill="none"/><ellipse cx="${x+15}" cy="${y+92}" rx="11" ry="25" fill="#89a68d" transform="rotate(45 ${x+15} ${y+92})"/>`;blooms+=flower(kind,x,y,n>12?25:31,i)});
 const paper=wraps[state.wrap];const empty=n===0?'<text x="250" y="225" text-anchor="middle" font-family="Georgia" font-size="18" fill="#836c87">Chọn hoa để bắt đầu bó hoa</text>':'';
 $('#dam-svg').innerHTML=`<defs><filter id="shadow"><feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#8c6e94" flood-opacity=".18"/></filter></defs><ellipse cx="250" cy="510" rx="150" ry="15" fill="#bba8bd" opacity=".2"/><g filter="url(#shadow)"><path d="M99 270 L250 530 L401 270 Q340 315 250 320 Q150 313 99 270" fill="${paper}" stroke="#ffffff" stroke-opacity=".7" stroke-width="3"/><path d="M125 280 L250 515 L250 330Z" fill="#ffffff" opacity=".24"/><path d="M375 280 L250 515 L250 330Z" fill="#785c79" opacity=".12"/>${stems}${blooms}${empty}<path d="M220 454 Q250 439 280 454 L276 470 Q250 479 223 470 Z" fill="#9172a2"/><path d="M225 466 Q192 460 194 487 Q229 488 250 469 Q265 498 302 483 Q305 463 277 464" fill="#a78eb5"/></g>`;
 }
 function render(){ $$('.dam-tone').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.tone===state.tone)));$$('.dam-wrap').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.wrap===state.wrap)));$$('.dam-size').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.size===state.size)));$$('.dam-flower-item').forEach(card=>{const id=card.dataset.flower;card.querySelector('output').textContent=state.flowers[id];});$('#dam-limit').textContent=count()+'/'+max()+' cành tối đa';$('#dam-total').textContent=fmt(price());$('#dam-summary').textContent=types.filter(t=>state.flowers[t.id]>0).map(t=>t.name+' × '+state.flowers[t.id]).join(', ')||'Chưa chọn hoa';draw();}
 $$('.dam-tone').forEach(b=>b.addEventListener('click',()=>{state.tone=b.dataset.tone;render()}));$$('.dam-wrap').forEach(b=>b.addEventListener('click',()=>{state.wrap=b.dataset.wrap;render()}));$$('.dam-size').forEach(b=>b.addEventListener('click',()=>{state.size=b.dataset.size;if(count()>max()){for(const t of types.slice().reverse()){while(count()>max() && state.flowers[t.id]>0)state.flowers[t.id]--;}}render()}));
 $$('.dam-count button').forEach(b=>b.addEventListener('click',()=>{const id=b.closest('.dam-flower-item').dataset.flower;const next=state.flowers[id]+(b.dataset.step==='plus'?1:-1);if(next<0||next>8||next>state.flowers[id]&&count()>=max())return;state.flowers[id]=next;render()}));
 $('#dam-add').addEventListener('click',async()=>{const msg=$('#dam-feedback');msg.className='dam-feedback';if(count()<1){msg.textContent='M cần chọn ít nhất một cành hoa.';msg.classList.add('error');return;}const cfg={...state,flowers:{...state.flowers},card:$('#dam-card').value.trim().slice(0,300),recipient:$('#dam-recipient').value.trim().slice(0,80)};
 if(!el.dataset.ajax){msg.textContent='Đây là bản xem thử. Để đặt hàng thật, dùng shortcode WooCommerce theo hướng dẫn.';return;}
 const btn=$('#dam-add');btn.disabled=true;msg.textContent='Đang thêm thiết kế vào giỏ hàng…';try{const body=new URLSearchParams({action:'dam_bouquet_add',nonce:el.dataset.nonce,product_id:el.dataset.product,config:JSON.stringify(cfg)});const res=await fetch(el.dataset.ajax,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:body.toString()});const data=await res.json();if(!data.success)throw new Error(data.data?.message||'Không thể thêm vào giỏ hàng.');msg.classList.add('success');msg.textContent='Đã thêm vào giỏ hàng! Đang chuyển trang…';window.location.href=data.data.cart_url;}catch(err){msg.classList.add('error');msg.textContent=err.message||'Có lỗi xảy ra';btn.disabled=false;}});
 render();
})();

</script>
<?php return str_replace(['__AJAX__','__NONCE__','__PRODUCT__'],[esc_url(admin_url('admin-ajax.php')),esc_attr(wp_create_nonce('dam_bb_add')),esc_attr($product->get_id())],ob_get_clean());
});
add_action('wp_ajax_dam_bouquet_add','dam_bb_add');
add_action('wp_ajax_nopriv_dam_bouquet_add','dam_bb_add');
function dam_bb_add(){
    if(!check_ajax_referer('dam_bb_add','nonce',false))wp_send_json_error(['message'=>'Phiên đã hết hạn, vui lòng tải lại trang.'],403);
    if(!class_exists('WooCommerce'))wp_send_json_error(['message'=>'WooCommerce chưa kích hoạt.'],400);
    if(!WC()->cart)wc_load_cart();
    $id=absint($_POST['product_id']??0);$p=wc_get_product($id);
    if(!$p || !$p->is_type('simple') || !$p->is_purchasable() || !$p->is_in_stock())wp_send_json_error(['message'=>'Sản phẩm không khả dụng.'],400);
    $json=isset($_POST['config'])?wp_unslash($_POST['config']):'';
    if(strlen($json)>5000)wp_send_json_error(['message'=>'Dữ liệu quá lớn.'],400);
    $cfg=json_decode($json,true);$validated=dam_bb_prices($cfg);
    if(is_wp_error($validated))wp_send_json_error(['message'=>$validated->get_error_message()],400);
    $item=['dam_bb'=>$validated['config'],'dam_bb_price'=>$validated['price'],'dam_bb_unique'=>wp_generate_uuid4()];
    $key=WC()->cart->add_to_cart($id,1,0,[], $item);
    if(!$key)wp_send_json_error(['message'=>'Không thêm được vào giỏ hàng. Kiểm tra cài đặt sản phẩm.'],400);
    WC()->cart->calculate_totals();wp_send_json_success(['cart_url'=>wc_get_cart_url()]);
}
add_action('woocommerce_before_calculate_totals',function($cart){
    if(is_admin()&&!wp_doing_ajax())return;
    foreach($cart->get_cart() as $item){
      if(!empty($item['dam_bb']) && isset($item['dam_bb_price'])){
        // Tính lại từ cấu hình đã được kiểm tra để tránh phụ thuộc vào giá gửi từ trình duyệt.
        $valid=dam_bb_prices($item['dam_bb']);
        if(!is_wp_error($valid))$item['data']->set_price($valid['price']);
      }
    }
},20);
add_filter('woocommerce_get_item_data',function($data,$cart_item){
    if(empty($cart_item['dam_bb']))return $data;
    foreach(dam_bb_labels($cart_item['dam_bb']) as $name=>$value)if($value!=='')$data[]=['key'=>$name,'value'=>$value];
    return $data;
},10,2);
add_action('woocommerce_checkout_create_order_line_item',function($item,$cart_item_key,$values,$order){
    if(empty($values['dam_bb']))return;
    foreach(dam_bb_labels($values['dam_bb']) as $name=>$value)if($value!=='')$item->add_meta_data($name,$value,true);
},10,4);
