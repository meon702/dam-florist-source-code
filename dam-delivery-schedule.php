/**
 * DAM FLORIST - BOOKING FULL V2
 * Shortcode: [dam_product_booking]
 * WooCommerce Simple Products
 *
 * Múi giờ: theo WordPress
 * Thời hạn chọn: 30 ngày
 * Khung giờ: khóa khi đã đến giờ bắt đầu
 * Phụ kiện: phí tính trên mỗi bó hoa
 */
<?php
if (!defined('ABSPATH')) exit;

// ================================
// 1. THIẾT LẬP
// ================================

function damv2_addons() {
    return [
        'ribbon' => ['Ruy băng cao cấp', 20000],
        'card'   => ['Thiệp viết tay', 15000],
        'wrap'   => ['Giấy gói đặc biệt', 30000],
        'pearl'  => ['Phụ kiện ngọc trai', 25000],
        'topper' => ['Que cắm lời chúc', 15000],
        'bag'    => ['Túi quà cao cấp', 35000]
    ];
}

function damv2_slots() {
    return [
        '08:00' => ['08:00', '12:00'],
        '13:00' => ['13:00', '17:00'],
        '18:00' => ['18:00', '21:00']
    ];
}

function damv2_now() {
    return current_datetime();
}

function damv2_date($date) {
    if (!is_string($date)) return false;

    $d = DateTimeImmutable::createFromFormat(
        '!Y-m-d', $date, wp_timezone()
    );

    if (!$d || $d->format('Y-m-d') !== $date) {
        return false;
    }

    $today = damv2_now()->setTime(0, 0, 0);

    return $d >= $today &&
        $d <= $today->modify('+30 days');
}

function damv2_available($date, $slot) {
    if (!damv2_date($date)) return false;

    $slots = damv2_slots();

    if (!isset($slots[$slot])) return false;

    $start = DateTimeImmutable::createFromFormat(
        '!Y-m-d H:i',
        $date . ' ' . $slots[$slot][0],
        wp_timezone()
    );

    if (!$start) return false;

    // Không nhận khi đã tới giờ bắt đầu.
    return $start > damv2_now();
}

function damv2_first_date() {
    $today = damv2_now()->setTime(0, 0, 0);

    for ($i = 0; $i <= 30; $i++) {
        $date = $today->modify("+{$i} days")
                      ->format('Y-m-d');

        foreach (array_keys(damv2_slots()) as $slot) {
            if (damv2_available($date, $slot)) {
                return $date;
            }
        }
    }

    return '';
}

// ================================
// 2. SHORTCODE HIỂN THỊ
// ================================

add_shortcode('dam_product_booking', function($atts) {

    if (!function_exists('wc_get_product')) {
        return '<p>WooCommerce chưa được kích hoạt.</p>';
    }

    $atts = shortcode_atts(['id' => 0], $atts);

    $id = absint($atts['id']);

    if (!$id && is_singular('product')) {
        $id = get_queried_object_id();
    }

    $product = $id ? wc_get_product($id) : false;

    if (!$product) {
        return '<p>Không tìm thấy sản phẩm. Hãy đặt shortcode trong trang sản phẩm hoặc thêm id sản phẩm.</p>';
    }

    if (!$product->is_type('simple')) {
        return '<p>Giao diện này chỉ hỗ trợ sản phẩm đơn giản (Simple Product).</p>';
    }

    $uid = wp_unique_id('damv2-');
    $price = (float) $product->get_price();

    $today = damv2_now()->setTime(0, 0, 0);
    $max_date = $today->modify('+30 days');

    $selected_date = damv2_first_date();

    $max_qty = $product->is_sold_individually()
        ? 1
        : $product->get_max_purchase_quantity();

    if ($max_qty < 1) $max_qty = 999;

    $config = [
        'timezone' => wp_timezone_string(),
        'price' => $price,
        'currency' => get_woocommerce_currency(),
        'maxQty' => $max_qty,
        'slots' => damv2_slots(),
        'endDate' => $max_date->format('Y-m-d')
    ];

    ob_start();
    ?>

    <div id="<?php echo esc_attr($uid); ?>"
         class="damv2"
         data-config="<?php echo esc_attr(wp_json_encode($config)); ?>">

      <h2 class="damv2-title">
        <?php echo esc_html($product->get_name()); ?>
      </h2>

      <div class="damv2-rating">
        <span class="damv2-stars">★★★★★</span>
        <span>
          <?php echo esc_html($product->get_average_rating()); ?>/5
          (<?php echo esc_html($product->get_rating_count()); ?> đánh giá)
        </span>
      </div>

      <div class="damv2-desc">
        <?php echo wp_kses_post(
            wpautop($product->get_short_description())
        ); ?>
      </div>

      <div class="damv2-price">
        <?php echo wp_kses_post($product->get_price_html()); ?>
      </div>

      <?php if ($product->is_purchasable() &&
                $product->is_in_stock() &&
                $selected_date): ?>

      <form method="post" class="cart damv2-form"
            action="<?php echo esc_url(get_permalink($id)); ?>">

        <?php wp_nonce_field('damv2_submit', 'damv2_nonce'); ?>

        <input type="hidden" name="damv2_form" value="1">
        <input type="hidden" name="add-to-cart"
               value="<?php echo esc_attr($id); ?>">

        <!-- NGÀY GIAO -->
        <section class="damv2-section">
          <h3>Chọn ngày giao hoa</h3>

          <div class="damv2-date-grid">
            <?php
            $labels = ['HÔM NAY', 'NGÀY MAI', 'NGÀY KIA'];

            for ($i = 0; $i < 3; $i++):
                $d = $today->modify("+{$i} days");
                $value = $d->format('Y-m-d');
                $active = ($value === $selected_date);
            ?>
              <button type="button"
                class="damv2-day <?php echo $active ? 'is-active' : ''; ?>"
                data-date="<?php echo esc_attr($value); ?>">
                <span><?php echo esc_html($d->format('d/m')); ?></span>
                <strong><?php echo esc_html($labels[$i]); ?></strong>
              </button>
            <?php endfor; ?>

            <button type="button" class="damv2-day damv2-calendar">
              <span class="damv2-calendar-icon">▦</span>
              <strong>LỊCH</strong>
            </button>
          </div>

          <input type="date" class="damv2-picker"
            min="<?php echo esc_attr($today->format('Y-m-d')); ?>"
            max="<?php echo esc_attr($max_date->format('Y-m-d')); ?>"
            value="<?php echo esc_attr($selected_date); ?>"
            aria-label="Chọn ngày giao khác">

          <input type="hidden" name="damv2_date"
            class="damv2-date-value"
            value="<?php echo esc_attr($selected_date); ?>">

          <p class="damv2-date-summary" aria-live="polite"></p>
        </section>

        <!-- KHUNG GIỜ -->
        <section class="damv2-section">
          <h3>Chọn khung giờ giao hàng</h3>

          <div class="damv2-time-grid">
            <?php foreach (damv2_slots() as $key => $times): ?>
              <label class="damv2-time">
                <input type="radio" name="damv2_time"
                  value="<?php echo esc_attr($key); ?>">
                <span>
                  <?php echo esc_html(
                      $times[0] . ' – ' . $times[1]
                  ); ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>

          <p class="damv2-time-hint">
            Các khung giờ đã qua sẽ tự động bị khóa.
          </p>
        </section>

        <!-- PHỤ KIỆN -->
        <section class="damv2-section damv2-addon-section">
          <h3>Phụ kiện đi kèm</h3>

          <div class="damv2-addon-grid">
            <?php foreach (damv2_addons() as $key => $addon): ?>
              <label class="damv2-addon">
                <input type="checkbox" name="damv2_addons[]"
                  value="<?php echo esc_attr($key); ?>"
                  data-cost="<?php echo esc_attr($addon[1]); ?>">

                <span>
                  <?php echo esc_html($addon[0]); ?>
                  <small>
                    +<?php echo wp_kses_post(wc_price($addon[1])); ?>
                  </small>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- TỔNG TIỀN -->
        <div class="damv2-summary">
          <div>
            <span>Giá sản phẩm</span>
            <strong class="damv2-base">
              <?php echo wp_kses_post(wc_price($price)); ?>
            </strong>
          </div>

          <div>
            <span>Phụ kiện thêm</span>
            <strong class="damv2-extra">
              <?php echo wp_kses_post(wc_price(0)); ?>
            </strong>
          </div>

          <div class="damv2-total-row">
            <span>Tổng tạm tính</span>
            <strong class="damv2-total">
              <?php echo wp_kses_post(wc_price($price)); ?>
            </strong>
          </div>
        </div>

        <!-- SỐ LƯỢNG + GIỎ HÀNG -->
        <div class="damv2-buy">
          <div class="damv2-qty">
            <button type="button" class="damv2-minus"
              aria-label="Giảm số lượng">−</button>

            <input type="number" name="quantity"
              value="1" min="1"
              max="<?php echo esc_attr($max_qty); ?>"
              step="1" aria-label="Số lượng">

            <button type="button" class="damv2-plus"
              aria-label="Tăng số lượng">+</button>
          </div>

          <button type="submit" class="damv2-submit">
            Thêm vào giỏ hàng
          </button>
        </div>

        <p class="damv2-message" role="status"></p>

        <p class="damv2-note">
          Lịch giao sẽ được Dặm xác nhận sau khi tiếp nhận đơn hàng.
        </p>
      </form>

      <?php else: ?>
        <p>Sản phẩm chưa thể đặt hàng hoặc chưa có lịch giao khả dụng.</p>
      <?php endif; ?>
    </div>

    <style>
    #<?php echo esc_attr($uid); ?> {
      --purple:#8068BD;
      --deep:#66509F;
      --light:#F7F3FC;
      --border:#D8C9EC;
      --muted:#9487A4;
      font-family:inherit;
      color:#51495E;
      width:100%;
    }

    #<?php echo esc_attr($uid); ?> * {
      box-sizing:border-box;
    }

    #<?php echo esc_attr($uid); ?> .damv2-title {
      font-family:Georgia,serif;
      color:var(--purple);
      font-size:29px;
      font-weight:500;
      line-height:1.35;
      margin:0 0 12px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-rating {
      display:flex;
      flex-wrap:wrap;
      gap:9px;
      font-size:13px;
      color:var(--muted);
      margin-bottom:16px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-stars {
      color:var(--purple);
      letter-spacing:2px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-desc {
      font-size:15px;
      line-height:1.8;
      margin-bottom:18px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-price {
      font-size:27px;
      font-weight:600;
      color:var(--purple);
      margin-bottom:22px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-section {
      margin:23px 0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-section h3 {
      font-size:15px;
      font-weight:600;
      color:var(--purple);
      margin:0 0 13px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-date-grid {
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:9px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-day {
      background:#fff;
      border:1px solid var(--border);
      border-radius:9px;
      min-width:0;
      min-height:79px;
      padding:8px 3px;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:5px;
      color:#51495E;
      cursor:pointer;
      transition:background .2s,border-color .2s;
    }

    #<?php echo esc_attr($uid); ?> .damv2-day span {
      font-size:12px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-day strong {
      font-size:11px;
      color:var(--purple);
      font-weight:600;
    }

    #<?php echo esc_attr($uid); ?> .damv2-day.is-active {
      border:2px solid var(--purple);
      background:var(--light);
    }

    #<?php echo esc_attr($uid); ?> .damv2-day:disabled {
      opacity:.4;
      cursor:not-allowed;
      background:#F0F0F0;
      border-color:#DDD;
    }

    #<?php echo esc_attr($uid); ?> .damv2-calendar-icon {
      font-size:23px!important;
      color:var(--purple);
    }

    #<?php echo esc_attr($uid); ?> .damv2-picker {
      display:none;
      width:100%;
      margin-top:10px;
      padding:10px;
      border:1px solid var(--border);
      border-radius:9px;
      color:#51495E;
      background:#fff;
    }

    #<?php echo esc_attr($uid); ?> .damv2-date-summary {
      font-size:12px;
      color:var(--muted);
      margin:9px 0 0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-time-grid {
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:9px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-time {
      position:relative;
      margin:0;
      cursor:pointer;
    }

    #<?php echo esc_attr($uid); ?> .damv2-time input {
      position:absolute;
      width:1px;
      height:1px;
      opacity:0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-time span {
      display:block;
      padding:12px 4px;
      border:1px solid var(--border);
      border-radius:9px;
      text-align:center;
      color:#51495E;
      font-size:12px;
      background:white;
    }

    #<?php echo esc_attr($uid); ?> .damv2-time input:checked + span {
      background:var(--light);
      border:2px solid var(--purple);
      color:var(--purple);
    }

    #<?php echo esc_attr($uid); ?> .damv2-time input:disabled + span {
      opacity:.38;
      background:#F0F0F0;
      cursor:not-allowed;
      border-color:#DDD;
    }

    #<?php echo esc_attr($uid); ?> .damv2-time input:focus-visible + span {
      outline:2px solid var(--deep);
      outline-offset:2px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-time-hint {
      font-size:12px;
      color:var(--muted);
      margin:10px 0 0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-addon-section {
      border-top:1px solid #EAE3F2;
      border-bottom:1px solid #EAE3F2;
      padding:22px 0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-addon-grid {
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:15px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-addon {
      display:flex;
      align-items:flex-start;
      gap:9px;
      font-size:13px;
      cursor:pointer;
    }

    #<?php echo esc_attr($uid); ?> .damv2-addon input {
      accent-color:var(--purple);
      margin:3px 0 0;
      flex-shrink:0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-addon small {
      display:block;
      font-size:12px;
      color:var(--muted);
    }

    #<?php echo esc_attr($uid); ?> .damv2-summary {
      padding:8px 0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-summary > div {
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:14px;
      font-size:14px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-summary span {
      color:var(--purple);
    }

    #<?php echo esc_attr($uid); ?> .damv2-total-row {
      border-top:1px solid #EAE3F2;
      padding-top:17px;
      font-size:19px!important;
    }

    #<?php echo esc_attr($uid); ?> .damv2-total-row strong {
      color:var(--purple);
    }

    #<?php echo esc_attr($uid); ?> .damv2-buy {
      display:flex;
      align-items:stretch;
      gap:12px;
      margin-top:17px;
    }

    #<?php echo esc_attr($uid); ?> .damv2-qty {
      display:flex;
      height:48px;
      border:1px solid var(--border);
      border-radius:9px;
      overflow:hidden;
      background:white;
    }

    #<?php echo esc_attr($uid); ?> .damv2-qty button {
      width:35px;
      padding:0;
      background:var(--light);
      border:0;
      color:var(--purple);
      font-size:19px;
      cursor:pointer;
    }

    #<?php echo esc_attr($uid); ?> .damv2-qty input {
      width:45px;
      min-width:0;
      height:100%;
      padding:0;
      border:0;
      text-align:center;
      color:#51495E;
      background:white;
      box-shadow:none;
      -moz-appearance:textfield;
    }

    #<?php echo esc_attr($uid); ?> .damv2-qty input::-webkit-inner-spin-button {
      -webkit-appearance:none;
    }

    #<?php echo esc_attr($uid); ?> .damv2-submit {
      flex:1;
      min-height:48px;
      background:var(--purple);
      color:white;
      border:0;
      border-radius:9px;
      padding:11px 16px;
      font-size:14px;
      font-weight:600;
      cursor:pointer;
    }

    #<?php echo esc_attr($uid); ?> .damv2-submit:hover {
      background:var(--deep);
      color:white;
    }

    #<?php echo esc_attr($uid); ?> .damv2-submit:disabled {
      opacity:.5;
      cursor:not-allowed;
    }

    #<?php echo esc_attr($uid); ?> .damv2-message {
      font-size:13px;
      color:#A14655;
      margin:10px 0 0;
    }

    #<?php echo esc_attr($uid); ?> .damv2-note {
      color:var(--muted);
      font-size:12px;
      line-height:1.6;
      margin:12px 0;
    }

    @media(max-width:600px) {
      #<?php echo esc_attr($uid); ?> .damv2-title {
        font-size:24px;
      }

      #<?php echo esc_attr($uid); ?> .damv2-date-grid {
        gap:6px;
      }

      #<?php echo esc_attr($uid); ?> .damv2-day {
        min-height:70px;
      }

      #<?php echo esc_attr($uid); ?> .damv2-day strong {
        font-size:10px;
      }

      #<?php echo esc_attr($uid); ?> .damv2-time-grid {
        grid-template-columns:1fr;
      }

      #<?php echo esc_attr($uid); ?> .damv2-addon-grid {
        grid-template-columns:1fr;
      }
    }
    </style>

    <script>
    (() => {
      const root = document.getElementById(
        <?php echo wp_json_encode($uid); ?>
      );
      if (!root || !root.querySelector('.damv2-form')) return;

      const cfg = JSON.parse(root.dataset.config);
      const form = root.querySelector('.damv2-form');
      const dayButtons = [...root.querySelectorAll('[data-date]')];
      const calendar = root.querySelector('.damv2-calendar');
      const picker = root.querySelector('.damv2-picker');
      const dateValue = root.querySelector('.damv2-date-value');
      const radios = [...root.querySelectorAll('[name="damv2_time"]')];
      const addons = [...root.querySelectorAll('[data-cost]')];
      const qty = root.querySelector('[name="quantity"]');
      const submit = root.querySelector('.damv2-submit');
      const message = root.querySelector('.damv2-message');

      const fmt = amount => new Intl.NumberFormat(
        cfg.currency === 'VND' ? 'vi-VN' : 'en-US',
        {style:'currency',currency:cfg.currency}
      ).format(amount);

      // Lấy ngày giờ thực theo múi giờ WordPress.
      function nowLocal() {
        const parts = new Intl.DateTimeFormat('en-GB', {
          timeZone:cfg.timezone,
          year:'numeric',
          month:'2-digit',
          day:'2-digit',
          hour:'2-digit',
          minute:'2-digit',
          second:'2-digit',
          hourCycle:'h23'
        }).formatToParts(new Date());

        const p = {};
        parts.forEach(x => {
          if (x.type !== 'literal') p[x.type] = x.value;
        });

        return {
          date:`${p.year}-${p.month}-${p.day}`,
          seconds:Number(p.hour)*3600 +
                  Number(p.minute)*60 + Number(p.second)
        };
      }

      function validSlot(date, key) {
        const now = nowLocal();
        if (!date || date < now.date || date > cfg.endDate) {
          return false;
        }
        if (!cfg.slots[key]) return false;
        if (date > now.date) return true;

        const [h,m] = cfg.slots[key][0].split(':').map(Number);
        return h*3600 + m*60 > now.seconds;
      }

      function hasSlot(date) {
        return Object.keys(cfg.slots).some(
          key => validSlot(date,key)
        );
      }

      // Tăng ngày bằng UTC để không lệch do múi giờ máy khách.
      function addDays(date, n) {
        const d = new Date(date + 'T12:00:00Z');
        d.setUTCDate(d.getUTCDate() + n);
        return d.toISOString().slice(0,10);
      }

      function nextAvailable() {
        const now = nowLocal();

        for (let i=0; i<=30; i++) {
          const date = addDays(now.date,i);
          if (date > cfg.endDate) break;
          if (hasSlot(date)) return date;
        }
        return '';
      }

      function updateSchedule() {
        const now = nowLocal();
        picker.min = now.date;
        picker.max = cfg.endDate;

        dayButtons.forEach(button => {
          button.disabled = !hasSlot(button.dataset.date);
        });

        if (!hasSlot(dateValue.value)) {
          dateValue.value = nextAvailable();
        }

        if (dateValue.value) {
          picker.value = dateValue.value;
        }

        dayButtons.forEach(button => {
          button.classList.toggle(
            'is-active',
            !button.disabled &&
            button.dataset.date === dateValue.value
          );
        });

        const custom = !dayButtons.some(
          b => !b.disabled && b.dataset.date === dateValue.value
        );

        calendar.classList.toggle('is-active',custom);

        radios.forEach(input => {
          input.disabled = !validSlot(dateValue.value,input.value);
          if (input.disabled) input.checked = false;
        });

        root.querySelector('.damv2-date-summary').textContent =
          dateValue.value
            ? 'Ngày giao: ' + dateValue.value.split('-').reverse().join('/')
            : 'Chưa có lịch giao khả dụng';

        submit.disabled = !dateValue.value;
      }

      function chooseDate(date) {
        if (!hasSlot(date)) {
          message.textContent = 'Ngày này không còn khung giờ giao hợp lệ.';
          updateSchedule();
          return;
        }

        message.textContent = '';
        dateValue.value = date;
        updateSchedule();
      }

      dayButtons.forEach(button => {
        button.addEventListener('click', () => {
          chooseDate(button.dataset.date);
        });
      });

      calendar.addEventListener('click', () => {
        picker.style.display = 'block';

        if (picker.showPicker) {
          try { picker.showPicker(); }
          catch(e) { picker.focus(); }
        } else {
          picker.focus();
        }
      });

      picker.addEventListener('change', () => {
        chooseDate(picker.value);
      });

      function calculate() {
        let count = Math.floor(Number(qty.value) || 1);
        count = Math.max(1,Math.min(cfg.maxQty,count));
        qty.value = count;

        let extra = 0;
        addons.forEach(a => {
          if (a.checked) extra += Number(a.dataset.cost);
        });

        root.querySelector('.damv2-base').textContent =
          fmt(cfg.price*count);

        root.querySelector('.damv2-extra').textContent =
          fmt(extra*count);

        root.querySelector('.damv2-total').textContent =
          fmt((cfg.price+extra)*count);
      }

      root.querySelector('.damv2-minus').onclick = () => {
        qty.value = Number(qty.value)-1;
        calculate();
      };

      root.querySelector('.damv2-plus').onclick = () => {
        qty.value = Number(qty.value)+1;
        calculate();
      };

      qty.addEventListener('change',calculate);
      addons.forEach(a => a.addEventListener('change',calculate));

      form.addEventListener('submit',event => {
        updateSchedule();
        const selected = radios.find(r => r.checked && !r.disabled);

        if (!dateValue.value || !selected ||
            !validSlot(dateValue.value,selected.value)) {
          event.preventDefault();
          message.textContent =
            'Vui lòng chọn ngày và khung giờ giao còn hiệu lực.';
          return;
        }

        message.textContent = '';
      });

      updateSchedule();
      calculate();

      // Cập nhật khi thời gian thực trôi qua.
      window.setInterval(updateSchedule,15000);

      window.addEventListener('focus', updateSchedule);
    })();
    </script>
    <?php
    return ob_get_clean();
});

// ================================
// 3. KIỂM TRA KHI THÊM VÀO GIỎ
// ================================

add_filter('woocommerce_add_to_cart_validation',
function($valid,$product_id,$quantity) {

    if (!isset($_POST['damv2_form'])) return $valid;
    if (!$valid) return false;

    $nonce = isset($_POST['damv2_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['damv2_nonce']))
        : '';

    if (!wp_verify_nonce($nonce,'damv2_submit')) {
        wc_add_notice('Phiên đặt hàng không hợp lệ. Vui lòng thử lại.','error');
        return false;
    }

    $date = isset($_POST['damv2_date'])
        ? sanitize_text_field(wp_unslash($_POST['damv2_date']))
        : '';

    $time = isset($_POST['damv2_time'])
        ? sanitize_text_field(wp_unslash($_POST['damv2_time']))
        : '';

    if (!damv2_available($date,$time)) {
        wc_add_notice(
            'Ngày hoặc giờ giao đã qua. Vui lòng chọn lịch giao khác.',
            'error'
        );
        return false;
    }

    $product = wc_get_product($product_id);

    if (!$product || !$product->is_type('simple')) {
        wc_add_notice('Loại sản phẩm không được hỗ trợ.','error');
        return false;
    }

    return $valid;
},20,3);

// ================================
// 4. LƯU DỮ LIỆU GIỎ HÀNG
// ================================

add_filter('woocommerce_add_cart_item_data',
function($data,$product_id) {

    if (!isset($_POST['damv2_form'])) return $data;

    $nonce = isset($_POST['damv2_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['damv2_nonce']))
        : '';

    if (!wp_verify_nonce($nonce,'damv2_submit')) return $data;

    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('simple')) return $data;

    $date = isset($_POST['damv2_date'])
        ? sanitize_text_field(wp_unslash($_POST['damv2_date']))
        : '';

    $time = isset($_POST['damv2_time'])
        ? sanitize_text_field(wp_unslash($_POST['damv2_time']))
        : '';

    if (!damv2_available($date,$time)) return $data;

    $raw = isset($_POST['damv2_addons']) &&
           is_array($_POST['damv2_addons'])
        ? array_map('sanitize_text_field',
            wp_unslash($_POST['damv2_addons']))
        : [];

    $options = damv2_addons();
    $selected = [];
    $extra = 0;

    foreach (array_unique($raw) as $key) {
        if (!isset($options[$key])) continue;

        $selected[$key] = $options[$key][0];
        $extra += $options[$key][1];
    }

    $data['damv2_date'] = $date;
    $data['damv2_time'] = $time;
    $data['damv2_addons'] = $selected;
    $data['damv2_extra'] = $extra;

    return $data;
},10,2);

// ================================
// 5. CẬP NHẬT GIÁ WOOCOMMERCE
// ================================

add_action('woocommerce_before_calculate_totals',
function($cart) {

    if (is_admin() && !wp_doing_ajax()) return;
    if (!$cart) return;

    foreach ($cart->get_cart() as $key => $item) {
        if (!isset($item['damv2_extra'])) continue;

        $product = wc_get_product($item['product_id']);
        if (!$product) continue;

        $base_price = (float) $product->get_price();
        $extra = (float) $item['damv2_extra'];

        $item['data']->set_price($base_price+$extra);
    }
},20);

// ================================
// 6. HIỂN THỊ GIỎ HÀNG
// ================================

add_filter('woocommerce_get_item_data',
function($data,$item) {

    if (!empty($item['damv2_date'])) {
        $d = $item['damv2_date'];

        $data[] = [
            'key'=>'Ngày giao hoa',
            'value'=>esc_html(
                implode('/',array_reverse(explode('-',$d)))
            )
        ];
    }

    if (!empty($item['damv2_time'])) {
        $slots = damv2_slots();
        $key = $item['damv2_time'];

        if (isset($slots[$key])) {
            $data[] = [
                'key'=>'Khung giờ giao',
                'value'=>esc_html(
                    $slots[$key][0].' - '.$slots[$key][1]
                )
            ];
        }
    }

    foreach (($item['damv2_addons'] ?? []) as $addon) {
        $data[] = [
            'key'=>'Phụ kiện',
            'value'=>esc_html($addon)
        ];
    }

    return $data;
},10,2);

// ================================
// 7. LƯU VÀO ĐƠN HÀNG
// ================================

add_action('woocommerce_checkout_create_order_line_item',
function($item,$cart_item_key,$values,$order) {

    if (!empty($values['damv2_date'])) {
        $item->add_meta_data(
            'Ngày giao hoa',
            $values['damv2_date']
        );
    }

    if (!empty($values['damv2_time'])) {
        $slots = damv2_slots();
        $key = $values['damv2_time'];

        if (isset($slots[$key])) {
            $item->add_meta_data(
                'Khung giờ giao',
                $slots[$key][0].' - '.$slots[$key][1]
            );
        }
    }

    if (!empty($values['damv2_addons'])) {
        $item->add_meta_data(
            'Phụ kiện',
            implode(', ',$values['damv2_addons'])
        );
    }
},10,4);
