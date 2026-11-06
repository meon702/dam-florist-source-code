/**
 * DAM FLORIST - WOOCOMMERCE FILTER FIXED
 * Shortcode: [dam_shop_filter]
 * Hỗ trợ WooCommerce Archive Products.
 * Elementor Query ID tùy chọn: dam_filter_products
 */
<?php
if (!defined('ABSPATH')) exit;

// 1. Danh mục chính của Dặm.
function dam_sf_categories_fixed() {
    return [
        'dam-dac-biet'   => 'Dặm Đặc Biệt',
        'dam-diu-dang'   => 'Dặm Dịu Dàng',
        'dam-nang'       => 'Dặm Nắng',
        'dam-yeu-thuong' => 'Dặm Yêu Thương'
    ];
}

// 2. Đọc các lựa chọn từ URL.
function dam_sf_params_fixed() {
    $allowed = array_keys(dam_sf_categories_fixed());

    $raw_cats = isset($_GET['dam_cat']) &&
        is_array($_GET['dam_cat'])
        ? wp_unslash($_GET['dam_cat']) : [];

    $cats = array_values(array_intersect(
        $allowed,
        array_map('sanitize_title', $raw_cats)
    ));

    $raw_sizes = isset($_GET['dam_size']) &&
        is_array($_GET['dam_size'])
        ? wp_unslash($_GET['dam_size']) : [];

    $sizes = array_map('sanitize_title', $raw_sizes);

    $min = isset($_GET['dam_min']) &&
        is_scalar($_GET['dam_min'])
        ? max(0, (float) wp_unslash($_GET['dam_min'])) : 0;

    $max = isset($_GET['dam_max']) &&
        is_scalar($_GET['dam_max']) &&
        $_GET['dam_max'] !== ''
        ? max(0, (float) wp_unslash($_GET['dam_max'])) : null;

    $rating = isset($_GET['dam_rating'])
        ? absint($_GET['dam_rating']) : 0;

    return [
        'cats' => $cats,
        'sizes' => $sizes,
        'min' => $min,
        'max' => $max,
        'rating' => in_array($rating, [0,3,4,5], true)
            ? $rating : 0,
        'price_active' => isset($_GET['dam_min']) ||
            isset($_GET['dam_max'])
    ];
}

// 3. Xác định URL chính xác của trang hiện tại.
// Không sử dụng home_url('/') làm fallback.
function dam_sf_action_fixed() {

    // Đang xem một danh mục WooCommerce.
    if (is_tax('product_cat')) {
        $url = get_term_link(get_queried_object());
        if (!is_wp_error($url) && $url) return $url;
    }

    // Đang ở trang Shop chuẩn của WooCommerce.
    if (is_shop() || is_post_type_archive('product')) {
        $shop_id = wc_get_page_id('shop');
        if ($shop_id > 0) {
            return get_permalink($shop_id);
        }
        return get_post_type_archive_link('product');
    }

    // Trang thường do Elementor thiết kế.
    if (is_page()) {
        $page_id = get_queried_object_id();
        if ($page_id) return get_permalink($page_id);
    }

    // Dự phòng: đường dẫn trang hiện tại,
    // không lấy trang chủ.
    global $wp;

    if (!empty($wp->request)) {
        return home_url('/' . trim($wp->request, '/') . '/');
    }

    return '';
}

// 4. Shortcode bộ lọc.
add_shortcode('dam_shop_filter', function() {

    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce chưa được kích hoạt.</p>';
    }

    $p = dam_sf_params_fixed();
    $action = dam_sf_action_fixed();

    if (!$action) {
        return '<p>Không xác định được trang cửa hàng. Hãy kiểm tra trang Shop trong WooCommerce.</p>';
    }

    $categories = dam_sf_categories_fixed();

    $sizes = taxonomy_exists('pa_size')
        ? get_terms([
            'taxonomy' => 'pa_size',
            'hide_empty' => true
        ]) : [];

    if (is_wp_error($sizes)) $sizes = [];

    ob_start();
    ?>
    <form class="dam-sf" method="get"
          action="<?php echo esc_url($action); ?>">

      <div class="dam-sf-heading">
        <h2>Bộ lọc hoa</h2>
        <span aria-hidden="true">✦</span>
      </div>

      <!-- DANH MỤC -->
      <section>
        <h3>Danh mục hoa</h3>

        <?php foreach ($categories as $slug => $name):
            $term = get_term_by(
                'slug', $slug, 'product_cat'
            );

            if (!$term) continue;
        ?>
          <label class="dam-sf-option">
            <input type="checkbox"
                   name="dam_cat[]"
                   value="<?php echo esc_attr($slug); ?>"
                   <?php checked(
                       in_array($slug, $p['cats'], true)
                   ); ?>>

            <span><?php echo esc_html($name); ?></span>

            <small>
              (<?php echo (int) $term->count; ?>)
            </small>
          </label>
        <?php endforeach; ?>
      </section>

      <!-- KÍCH THƯỚC -->
      <?php if (!empty($sizes)): ?>
        <section>
          <h3>Kích thước</h3>

          <?php foreach ($sizes as $size): ?>
            <label class="dam-sf-option">
              <input type="checkbox"
                     name="dam_size[]"
                     value="<?php echo esc_attr($size->slug); ?>"
                     <?php checked(
                         in_array($size->slug, $p['sizes'], true)
                     ); ?>>

              <span>
                <?php echo esc_html($size->name); ?>
              </span>
            </label>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <!-- KHOẢNG GIÁ -->
      <section>
        <h3>Khoảng giá</h3>

        <div class="dam-sf-prices">
          <label>
            <span>Từ (₫)</span>
            <input type="number"
                   name="dam_min"
                   min="0"
                   step="10000"
                   value="<?php echo esc_attr($p['min']); ?>">
          </label>

          <label>
            <span>Đến (₫)</span>
            <input type="number"
                   name="dam_max"
                   min="0"
                   step="10000"
                   placeholder="Không giới hạn"
                   value="<?php echo esc_attr(
                     $p['max'] === null ? '' : $p['max']
                   ); ?>">
          </label>
        </div>

        <input class="dam-sf-range"
               type="range"
               min="0"
               max="5000000"
               step="50000"
               value="<?php echo esc_attr(
                 min(5000000, $p['max'] ?? 5000000)
               ); ?>"
               aria-label="Giới hạn giá tối đa">

        <div class="dam-sf-range-labels">
          <span>0₫</span>
          <span>5.000.000₫</span>
        </div>
      </section>

      <!-- ĐÁNH GIÁ -->
      <section>
        <h3>Đánh giá</h3>

        <?php foreach ([5,4,3] as $stars): ?>
          <label class="dam-sf-option">
            <input type="radio"
                   name="dam_rating"
                   value="<?php echo esc_attr($stars); ?>"
                   <?php checked($p['rating'], $stars); ?>>

            <span class="dam-sf-stars">
              <?php
              echo esc_html(str_repeat('★', $stars));
              echo esc_html(str_repeat('☆', 5 - $stars));
              ?>
              <?php if ($stars < 5): ?>
                <small>trở lên</small>
              <?php endif; ?>
            </span>
          </label>
        <?php endforeach; ?>

        <label class="dam-sf-option">
          <input type="radio"
                 name="dam_rating"
                 value="0"
                 <?php checked($p['rating'], 0); ?>>
          <span>Tất cả đánh giá</span>
        </label>
      </section>

      <button type="submit" class="dam-sf-apply">
        Lọc sản phẩm
      </button>

      <a class="dam-sf-reset"
         href="<?php echo esc_url($action); ?>">
        Xóa bộ lọc
      </a>
    </form>

    <style>
    .dam-sf {
      --purple:#8068BD;
      --deep:#66509F;
      --border:#D8C9EC;
      --text:#61596D;
      width:100%;
      padding:25px;
      background:#FFFFFF;
      border:1px solid #EAE3F2;
      border-radius:12px;
      color:var(--text);
      font-family:inherit;
      box-sizing:border-box;
    }

    .dam-sf * {
      box-sizing:border-box;
    }

    .dam-sf-heading {
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      padding-bottom:15px;
    }

    .dam-sf-heading h2 {
      margin:0;
      font-size:21px;
      font-weight:600;
      color:var(--purple);
    }

    .dam-sf-heading span {
      font-size:22px;
      color:var(--purple);
    }

    .dam-sf section {
      border-top:1px solid #EAE3F2;
      padding:23px 0;
    }

    .dam-sf section h3 {
      font-family:Georgia,serif;
      font-size:21px;
      line-height:1.35;
      font-weight:500;
      color:var(--purple);
      margin:0 0 18px;
    }

    .dam-sf-option {
      display:flex;
      align-items:center;
      gap:11px;
      margin:0 0 14px;
      font-size:14px;
      line-height:1.5;
      cursor:pointer;
    }

    .dam-sf-option:last-child {
      margin-bottom:0;
    }

    .dam-sf-option input {
      width:17px;
      height:17px;
      margin:0;
      flex-shrink:0;
      accent-color:var(--purple);
      cursor:pointer;
    }

    .dam-sf-option span {
      flex:1;
      min-width:0;
    }

    .dam-sf-option small {
      color:#A295AF;
      font-size:12px;
      white-space:nowrap;
    }

    .dam-sf-prices {
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:12px;
      margin-bottom:18px;
    }

    .dam-sf-prices label span {
      display:block;
      margin-bottom:7px;
      font-size:12px;
      color:#8D819E;
    }

    .dam-sf-prices input {
      width:100%;
      min-width:0;
      height:42px;
      border:1px solid var(--border);
      border-radius:8px;
      background:white;
      color:#51495E;
      padding:8px;
      font-size:13px;
      box-shadow:none;
    }

    .dam-sf-prices input:focus {
      outline:none;
      border-color:var(--purple);
    }

    .dam-sf-range {
      display:block;
      width:100%;
      accent-color:var(--purple);
      cursor:pointer;
    }

    .dam-sf-range-labels {
      display:flex;
      justify-content:space-between;
      margin-top:7px;
      font-size:11px;
      color:#A295AF;
    }

    .dam-sf-stars {
      color:var(--purple);
      letter-spacing:1px;
    }

    .dam-sf-apply {
      display:block;
      width:100%;
      min-height:47px;
      border:0;
      border-radius:9px;
      padding:12px;
      background:var(--purple);
      color:#FFFFFF;
      font-size:14px;
      font-weight:600;
      cursor:pointer;
      transition:background .25s;
    }

    .dam-sf-apply:hover {
      background:var(--deep);
      color:#FFFFFF;
    }

    .dam-sf-reset {
      display:block;
      text-align:center;
      margin-top:15px;
      font-size:13px;
      color:var(--purple);
      text-decoration:underline;
    }

    @media(max-width:768px) {
      .dam-sf {padding:18px}
      .dam-sf section h3 {font-size:19px}
    }
    </style>

    <script>
    (() => {
      document.querySelectorAll('.dam-sf').forEach(form => {
        if (form.dataset.ready) return;
        form.dataset.ready = '1';

        const slider = form.querySelector('.dam-sf-range');
        const max = form.querySelector('[name="dam_max"]');
        if (!slider || !max) return;

        slider.addEventListener('input', () => {
          max.value = slider.value;
        });

        max.addEventListener('input', () => {
          slider.value = max.value === ''
            ? slider.max
            : Math.min(
                Number(slider.max),
                Math.max(0, Number(max.value) || 0)
              );
        });
      });
    })();
    </script>
    <?php
    return ob_get_clean();
});

// 5. Áp dụng các điều kiện lọc cho WP_Query.
function dam_sf_apply_fixed($query) {

    $has_filters = false;

    foreach ([
        'dam_cat',
        'dam_size',
        'dam_min',
        'dam_max',
        'dam_rating'
    ] as $key) {
        if (isset($_GET[$key])) {
            $has_filters = true;
            break;
        }
    }

    if (!$has_filters) return;

    $p = dam_sf_params_fixed();

    $tax = (array) $query->get('tax_query');
    $meta = (array) $query->get('meta_query');

    // Danh mục
    if ($p['cats']) {
        $tax[] = [
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => $p['cats'],
            'operator' => 'IN'
        ];
    }

    // Kích thước
    if ($p['sizes'] && taxonomy_exists('pa_size')) {
        $available = get_terms([
            'taxonomy' => 'pa_size',
            'fields' => 'slugs',
            'hide_empty' => false
        ]);

        if (!is_wp_error($available)) {
            $valid_sizes = array_intersect(
                $p['sizes'], $available
            );

            if ($valid_sizes) {
                $tax[] = [
                    'taxonomy' => 'pa_size',
                    'field' => 'slug',
                    'terms' => array_values($valid_sizes),
                    'operator' => 'IN'
                ];
            }
        }
    }

    // Giá
    if ($p['price_active']) {
        if ($p['max'] !== null && $p['min'] > $p['max']) {
            $query->set('post__in', [0]);
        } else {
            $clause = [
                'key' => '_price',
                'type' => 'DECIMAL'
            ];

            if ($p['max'] !== null) {
                $clause['value'] = [
                    $p['min'], $p['max']
                ];
                $clause['compare'] = 'BETWEEN';
            } else {
                $clause['value'] = $p['min'];
                $clause['compare'] = '>=';
            }

            $meta[] = $clause;
        }
    }

    // Đánh giá trung bình
    if ($p['rating']) {
        $meta[] = [
            'key' => '_wc_average_rating',
            'value' => $p['rating'],
            'type' => 'DECIMAL',
            'compare' => '>='
        ];
    }

    $query->set('tax_query', $tax);
    $query->set('meta_query', $meta);
}

// Truy vấn Shop / Archive WooCommerce.
add_action('woocommerce_product_query', function($query) {
    dam_sf_apply_fixed($query);
}, 20);

// Truy vấn Elementor có Query ID dam_filter_products.
// Chỉ dùng khi widget hỗ trợ Custom Query ID.
add_action('elementor/query/dam_filter_products', function($query) {
    dam_sf_apply_fixed($query);
}, 20);
