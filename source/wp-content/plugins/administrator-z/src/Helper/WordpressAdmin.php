<?php

namespace Adminz\Helper;

final class WordpressAdmin {

    public $field_group = 'test'; // = param 'page'
    public $page_title = 'Example';
    public $menu_title = 'Example';
    public $menu_slug = 'test';
    public $capability = 'manage_options';
    public $parent_slug = 'options-general.php';
    public $icon_url = 'dashicons-admin-generic';
    public $position = 99;

    function __construct() {
    }

    function add_submenu_page() {
        add_action('admin_menu', function () {
            add_submenu_page(
                $this->parent_slug,
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'callback'],
            );
        });
    }

    function add_menu_page() {
        add_action('admin_menu', function () {
            add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'callback'],
                'dashicons-admin-generic',
                $this->position
            );
        });
    }

    function callback() {
?>
        <div class="wrap adminz_wrap">
            <h2> <?= esc_attr($this->page_title) ?> </h2>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->field_group);
                do_settings_sections($this->field_group);
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    function get_transient_key(){
        $add = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return 'adminz_quiz_' . md5($add . $user_agent);
    }

    function init_quiz() {
        $field = function () {
            $transient_key = $this->get_transient_key();
            $quiz_data = get_transient($transient_key);

            if ($quiz_data === false) {
                // Tạo câu hỏi mới nếu chưa có transient
                $num1 = rand(100, 999);
                $num2 = rand(10, 99);
                $operators = ['+', '-', '*', '^'];
                $operator = $operators[array_rand($operators)];

                switch ($operator) {
                    case '+':
                        $result = $num1 + $num2;
                        break;
                    case '-':
                        $result = $num1 - $num2;
                        break;
                    case '*':
                        $result = $num1 * $num2;
                        break;
                    case '^':
                        $num1 = rand(2, 9);
                        $num2 = rand(2, 4);
                        $result = pow($num1, $num2);
                        break;
                }

                // Lưu câu hỏi và kết quả vào transient
                $quiz_data = [
                    'question' => "$num1 $operator $num2",
                    'answer' => $result
                ];
                set_transient($transient_key, $quiz_data, 5 * MINUTE_IN_SECONDS); // Lưu 5 phút
            }

            // echo "<pre>"; print_r($quiz_data); echo "</pre>";
            $google_calc_link = "https://www.google.com/search?q=" . urlencode($quiz_data['question']);

            // Hiển thị câu hỏi
            echo <<<HTML
            <style>
                #adminz_quiz_login {
                    display: flex;
                    align-items: center;
                    padding: 10px;
                    border: .0625rem solid #8c8f94;
                    border-radius: 5px;
                    background-color: #d6d8d978;
                }
                #adminz_quiz_login strong {
                    width: 100%;
                    font-size:1.3em;
                    text-align: center;
                }
                #adminz_quiz_login input {
                    text-align: center;
                }
                #adminz_quiz_login_helper {
                    margin-bottom: 15px;
                }
            </style>
            <div id="adminz_quiz_login">
                <strong>{$quiz_data['question']} = ?</strong>
                <input type="text" name="quiz_answer" required>
            </div>
            <div id="adminz_quiz_login_helper">
                <small>
                    <a href="$google_calc_link" target="_blank">Search on Google</a>
                </small>
            </div>
            <input type="hidden" name="quiz_key" value="{$transient_key}">
            HTML;
        };

        add_action('login_form', $field);
        add_action('woocommerce_login_form', $field);

        add_filter('authenticate', function ($user, $username, $password) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!isset($_POST['quiz_answer']) || !isset($_POST['quiz_key'])) {
                    return new \WP_Error('quiz_error', __('You need to answer the question before logging in.', 'administrator-z'));
                }

                $transient_key = $this->get_transient_key();
                $quiz_data = get_transient($transient_key);

                if ($quiz_data === false) {
                    return new \WP_Error('quiz_error', __('The quiz has expired. Please try again.', 'administrator-z'));
                }

                if (intval($_POST['quiz_answer']) !== $quiz_data['answer']) {
                    return new \WP_Error('quiz_error', __('The answer is incorrect. Please try again.', 'administrator-z'));
                }

                delete_transient($transient_key);
            }

            return $user;
        }, 30, 3);
    }
}



// $page              = new \Adminz\Helper\WordpressAdmin();
// $page->field_group = $this->field_group;
// $page->page_title  = 'Crawl Options';
// $page->menu_title  = 'Crawl Options';
// $page->menu_slug   = 'crawl-options';
// $page->add_menu_page();


// add_action( 'admin_init', function () {

// 	// Thêm section
// 	$section_id = 'test_section_id';
// 	add_settings_section(
// 		$section_id,
// 		'Section Example',
// 		function () {
// 			echo '<p>Sed ut perspiciatis unde omnis iste natus...</p>';
// 		},
// 		$this->field_group
// 	);

// 	// Thêm 1 option và 1 field mới
// 	$option_name = 'test_field_1';
// 	register_setting( $this->field_group, $option_name );
// 	add_settings_field(
// 		wp_rand(),
// 		'Field Example 1',
// 		function () use ($option_name) {
// 			$value = get_option( $option_name );
// 			echo <<<HTML
// 					<input type="text" name="$option_name" value="$value">
// 					HTML;
// 		},
// 		$this->field_group,
// 		$section_id
// 	);

// 	// Thêm 1 option và 2 field mới
// 	$option_name = 'test_field_2';
// 	register_setting( $this->field_group, $option_name );
// 	add_settings_field(
// 		wp_rand(),
// 		'Field Example 2',
// 		function () use ($option_name) {
// 			$value = get_option( $option_name )['xxx'] ?? '';
// 			echo <<<HTML
// 					<input type="text" name="{$option_name}[xxx]" value="$value">
// 					HTML;
// 		},
// 		$this->field_group,
// 		$section_id
// 	);
// 	add_settings_field(
// 		wp_rand(),
// 		'Field Example 2.2',
// 		function () use ($option_name) {
// 			$value = get_option( $option_name )['yyy'] ?? '';
// 			echo <<<HTML
// 					<input type="text" name="{$option_name}[yyy]" value="$value">
// 					HTML;
// 		},
// 		$this->field_group,
// 		$section_id
// 	);
// } );






// $page = new \Adminz\Helper\WordpressAdmin();
// $page->field_group = $this->field_group;
// $page->page_title = 'PopoDoo Settings';
// $page->menu_title = 'PopoDoo Settings';
// $page->menu_slug = 'ppd-settings';
// $page->add_menu_page();

// add_action('admin_init', function () {

//     $section_id = 'pages';

//     // Register main option array
//     register_setting(
//         $this->field_group,
//         $this->field_group
//     );

//     // Add section
//     add_settings_section(
//         $section_id,
//         'Pages',
//         function () {
//             // Section description (optional)
//         },
//         $this->field_group
//     );

//     $option_name = 'page_he_thong_chi_nhanh';

//     add_settings_field(
//         $option_name,
//         'Page Hệ thống chi nhánh',
//         function () use ($option_name) {

//             // Get saved value safely
//             $value = $this->options[$option_name] ?? '';
//             echo adminz_field([
//                 'field' => 'select',
//                 'attribute' => [
//                     // Field name as requested
//                     'name' => $this->field_group . '[' . $option_name . ']',
//                 ],
//                 'post_select' => [
//                     'post_type' => 'page',
//                 ],
//                 'value' => $value,
//             ]);
//         },
//         $this->field_group,
//         $section_id
//     );
// });