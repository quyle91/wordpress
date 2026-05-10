<?php

namespace Adminz\Helper;

class Wpcf7 {

    public $form_tags = [
        /*[ 'tag_name'=>'menu-294', 'callback'=>'' ]*/];

    function __construct() {
        //
    }

    private $newletters_form_id;
    private $newletters_field_name;
    private $newletters_table_name;
    private $newletters_table_label;
    private $newletters_post_type = ['post'];

    function prefill_form_by_url_params() {

        //
        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_script(
                'prefill_form_by_url_params',
                ADMINZ_DIR_URL . 'assets/js/wpcf7/prefill_form_by_url_params.js',
                [],
                ADMINZ_VERSION,
                true
            );
        });
    }

    function make_form_newletters($form_id, $field_name) {
        if (!$form_id) {
            return;
        }

        // prepare data
        $this->newletters_form_id = $form_id;
        $this->newletters_field_name = $field_name;
        $this->newletters_table_name = 'adminz_wpcf7_newletters';
        $this->newletters_table_label = 'Adminz Wpcf7 newletters';
        $this->newletters_post_type = apply_filters('adminz_newletters_post_type', $this->newletters_post_type);

        // prepare table
        \WpDatabaseHelperV2\Database\DbTable::make()
            ->name($this->newletters_table_name)
            ->title($this->newletters_table_label)
            ->fields([
                //
                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('id')
                    ->type('INT(11)')
                    ->notNull()
                    ->autoIncrement()
                    ->primary(),

                //
                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('form_id')
                    ->type('INT(11)')
                    ->notNull(),

                //
                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('email')
                    ->type('varchar(255)')
                    ->notNull(),

                //
                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('created_at')
                    ->type('DATETIME')
                    ->notNull()
                    ->default('CURRENT_TIMESTAMP'),
            ])
            ->registerAdminPage()
            ->create();

        // save submitted data
        add_action('wpcf7_mail_sent', function ($contact_form) {
            if ($contact_form->id() == $this->newletters_form_id) {
                $submission = \WPCF7_Submission::get_instance();
                if ($submission) {
                    $posted_data = $submission->get_posted_data();
                    if ($email = ($posted_data[$this->newletters_field_name] ?? '')) {
                        global $wpdb;
                        $table_name = $wpdb->prefix . $this->newletters_table_name;
                        $wpdb->insert(
                            $table_name,
                            [
                                'form_id' => $contact_form->id(),
                                'email' => $email,
                            ]
                        );
                    }
                }
            }
        });

        // send email when submit new post
        add_action('transition_post_status', function ($new_status, $old_status, $post) {
            if ($new_status === 'publish' && $old_status !== 'publish' && $old_status !== 'trash') {
                // Kiểm tra nếu post type là một trong các loại newsletter
                if (in_array($post->post_type, $this->newletters_post_type)) {

                    // Lấy thông tin bài viết
                    $title = get_the_title($post->ID);
                    $excerpt = wp_trim_words(get_the_excerpt($post->ID), 40);
                    $categories = get_the_term_list($post->ID, 'category', '', ', ', '');
                    $publish_date = get_the_date(get_option('date_format'), $post->ID);
                    $link = get_permalink($post->ID);


                    // Tạo nội dung email dưới dạng HTML
                    $text_title = __("Title");
                    $text_excerpt = __("Excerpt");
                    $text_category = __('Categories');
                    $text_date = __('Published');
                    $text_readmore = __('Read more');
                    $message = <<<HTML
						<p><strong>$text_title:</strong> $title</p>
						<p><strong>$text_excerpt:</strong> $excerpt</p>
						<p><strong>$text_category:</strong> $categories</p>
						<p><strong>$text_date:</strong> $publish_date</p>
						<p><a href=$link>$text_readmore</a></p>
					HTML;

                    // Tiêu đề email
                    $domain = parse_url(get_home_url(), PHP_URL_HOST);
                    $subject = sprintf(__("There is a new article on the site %s", 'administrator-z'), $domain);

                    // Headers của email
                    $headers = array('Content-Type: text/html; charset=UTF-8');

                    // Các file đính kèm nếu có
                    $attachments = [];

                    // Lấy danh sách email từ bảng trong cơ sở dữ liệu
                    global $wpdb;
                    $table_name = $wpdb->prefix . $this->newletters_table_name;
                    $sql = "SELECT DISTINCT email FROM $table_name";
                    $results = $wpdb->get_results($sql, ARRAY_A);

                    if (!empty($results)) {
                        // Lấy mảng các email
                        $emails = wp_list_pluck($results, 'email');
                        $emails = array_unique($emails);

                        foreach ((array) $emails as $key => $email) {

                            $text_unregister = __('Unsubscribe', 'administrator-z');
                            $link_unregister = add_query_arg(
                                [
                                    'adminz_newletters_unregister' => $email,
                                    'token' => wp_create_nonce('adminz_unsubscribe_' . $email)
                                ],
                                get_site_url()
                            );
                            $message .= <<<HTML
								<p><a href=$link_unregister>$text_unregister</a></p>
							HTML;

                            wp_mail(
                                $email,
                                $subject,
                                $message,
                                $headers,
                                $attachments
                            );
                        }
                    }
                }
            }
        }, 10, 3);

        // unregister
        add_action('init', function () {
            if ($email = ($_GET['adminz_newletters_unregister'] ?? '')) {
                if (!wp_verify_nonce($_GET['token'] ?? '', 'adminz_unsubscribe_' . $email)) {
                    wp_die('Action failed. Invalid nonce.');
                }
                global $wpdb;
                $table_name = $wpdb->prefix . $this->newletters_table_name;
                $sql = "DELETE FROM wp_adminz_wpcf7_newletters WHERE `wp_adminz_wpcf7_newletters`.`id` = 2";
                $sql = $wpdb->prepare(
                    "DELETE FROM $table_name WHERE email = %s",
                    $email,
                );
                $result = $wpdb->query($sql);
                if ($result) {
                    wp_die(_x('Completed', 'request status'));
                }
            }
        });
    }

    function make_form_tags() {
        if (!empty($this->form_tags) and is_array($this->form_tags)) {
            foreach ($this->form_tags as $key => $item) {
                add_filter('wpcf7_form_tag', function ($tag, $replace) use ($item) {
                    if (in_array($tag['type'], ['select', 'select*', 'radio', 'checkbox', 'checkbox*'])) {
                        if (isset($item['tag_name']) and $tag['name'] == $item['tag_name']) {
                            $callback_return = call_user_func($item['callback']);
                            if (!empty($callback_return) and is_array($callback_return)) {
                                // reset array
                                $tag['values'] = [];
                                $tag['labels'] = [];
                                if (in_array($tag['type'], ['select', 'select*'])) {
                                    // Option đầu tiên được lấy làm mặc định, nó ko có giá trị 
                                    if (isset($tag['raw_values'][0])) {
                                        $default = $tag['raw_values'][0];
                                        $tag['values'] = [''];
                                        $tag['labels'] = [$default];
                                    }
                                }
                                foreach ($callback_return as $key => $value) {
                                    $tag['values'][] = $key;
                                    $tag['labels'][] = $value;
                                }

                                if ($search = array_search('include_blank', $tag['options']) >= 0) {
                                    if ($search == true) {
                                        $search = 0;
                                    }
                                    unset($tag['options'][$search]);
                                }
                            }
                        }
                    }
                    return $tag;
                }, 10, 2);
            }
        }
    }

    function save_submissions() {

        // create post type
        add_action('init', function () {
            $labels = array(
                'name' => 'Wpcf7 Submit',
                'singular_name' => 'Wpcf7 Submit',
            );

            $args = array(
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'supports' => array('title', 'editor'),
                'menu_icon' => 'dashicons-email',
            );

            register_post_type('wpcf7_submission', $args);
        });

        // create metabox
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'wpcf7_submission_data',
                'Form Submission Data',
                function ($post) {
                    $meta = get_post_meta($post->ID);
                    echo '<table class="widefat striped">';
                    echo '<thead><tr> <th>Meta Key</th> <th>Meta Value</th> </tr><thead>';
                    echo '<tbody>';
                    foreach ($meta as $key => $value) {
                        if ($key[0] !== '_') { // Bỏ qua metadata hệ thống
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($key) . '</strong></td>';
                            echo '<td>' . esc_html(implode(', ', $value)) . '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '<div class="adminz_wrap">';
                    $link = add_query_arg(
                        [
                            'adminz_test_postmeta' => $post->ID
                        ],
                        get_site_url()
                    );
                    echo '<small><strong>More: </strong></small>' . adminz_copy($link);
                    echo '</div>';
                },
                'wpcf7_submission',
                'normal',
                'high'
            );
        });

        // save submit to post type
        add_action('wpcf7_mail_sent', function ($contact_form) {
            $submission = \WPCF7_Submission::get_instance();
            if (!$submission) {
                return;
            }

            $form_data = $submission->get_posted_data();
            $form_id = $contact_form->id();
            $form_title = $contact_form->title();

            // Tạo một post mới trong CPT
            $post_id = wp_insert_post(array(
                'post_type' => 'wpcf7_submission',
                'post_title' => 'Submission - ' . $form_title . ' (' . date('Y-m-d H:i:s') . ')',
                'post_status' => 'publish',
            ));

            if ($post_id) {
                foreach ($form_data as $key => $value) {
                    if (!empty($value)) {
                        update_post_meta($post_id, $key, $value);
                    }
                }
            }
        });
    }

    function make_thankyou() {

        // Tab panels
        add_filter('wpcf7_editor_panels', function ($panels) {
            $panels['thankyou_panel'] = array(
                'title' => 'Adminz Thankyou',
                'callback' => function ($post) {
                    $form_id = $post->id();
                    $enable_thankyou = get_post_meta($form_id, '_enable_thankyou', true);
                    $thankyou_style = get_post_meta($form_id, '_thankyou_style', true);
                    $thankyou_content = get_post_meta($form_id, '_thankyou_content', true);
                    $thankyou_redirect = get_post_meta($form_id, '_thankyou_redirect', true);

                    $checked_enable = checked($enable_thankyou, 1, false);
                    $selected_alert = selected($thankyou_style, 'alert_content', false);
                    $selected_toggle = selected($thankyou_style, 'toggle_content', false);
                    $selected_redirect = selected($thankyou_style, 'redirect', false);
                    $thankyou_content_esc = esc_textarea($thankyou_content);
                    $thankyou_redirect_esc = esc_attr($thankyou_redirect);

                    echo <<<HTML
                    <p>
                    <label for="enable-thankyou">
                    <input type="checkbox" id="enable-thankyou" name="_enable_thankyou" value="1" {$checked_enable} />
                    Enable Thankyou Message
                    </label>
                    </p>
                    <p>
                    <label for="thankyou-style">Thankyou Style:</label>
                    <select id="thankyou-style" name="_thankyou_style">
                    <option value="alert_content" {$selected_alert}>Alert content</option>
                    <option value="toggle_content" {$selected_toggle}>Toggle content</option>
                    <option value="redirect" {$selected_redirect}>Redirect</option>
                    </select>
                    </p>
                    <p>
                    <label for="thankyou-content">Thankyou Content:</label>
                    <textarea id="thankyou-content" name="_thankyou_content" rows="4" style="width: 100%;" placeholder="Html or shortcode">{$thankyou_content_esc}</textarea>
                    </p>
                    <p>
                    <label for="redirect">Redirect to URL:</label>
                    <input type="text" id="redirect" name="_thankyou_redirect" value="{$thankyou_redirect_esc}" placeholder="URL here" />
                    </p>
                    HTML;
                },
            );
            return $panels;
        }, 10, 1);


        // Lưu dữ liệu từ tab "Thankyou Settings"
        add_action('wpcf7_save_contact_form', function ($contact_form) {
            $form_id = $contact_form->id();
            if (isset($_POST['_enable_thankyou'])) {
                update_post_meta($form_id, '_enable_thankyou', 1);
            } else {
                update_post_meta($form_id, '_enable_thankyou', 0);
            }
            if (isset($_POST['_thankyou_style'])) {
                update_post_meta($form_id, '_thankyou_style', sanitize_text_field($_POST['_thankyou_style']));
            }
            if (isset($_POST['_thankyou_content'])) {
                update_post_meta($form_id, '_thankyou_content', wp_unslash($_POST['_thankyou_content']));
            }
            if (isset($_POST['_thankyou_redirect'])) {
                update_post_meta($form_id, '_thankyou_redirect', esc_url_raw(wp_unslash($_POST['_thankyou_redirect'])));
            }
        });

        // footer		
        add_action('wp_footer', function () {
            // Kiểm tra xem có phải là trang đơn (singular) không
            // if (!is_singular()) {
            // 	return;
            // }

            // Lấy tất cả các form CF7 trên trang
            $forms = \WPCF7_ContactForm::find();
            if (empty($forms)) {
                return;
            }

            // Mảng lưu trữ nội dung "thankyou" của các form
            $thankyou_data = [];

            // Duyệt qua từng form
            foreach ($forms as $form) {
                $form_id = $form->id();
                $enable_thankyou = get_post_meta($form_id, '_enable_thankyou', true);
                $thankyou_style = get_post_meta($form_id, '_thankyou_style', true);
                $thankyou_content = get_post_meta($form_id, '_thankyou_content', true);
                $thankyou_redirect = get_post_meta($form_id, '_thankyou_redirect', true);

                // Nếu không bật tính năng thankyou, bỏ qua
                if (!$enable_thankyou) {
                    continue;
                }

                // Xử lý shortcode và giữ nguyên HTML
                $processed_content = do_shortcode(wp_kses_post($thankyou_content));

                // Lưu nội dung "thankyou" vào mảng
                $thankyou_data[$form_id] = [
                    'style' => $thankyou_style,
                    'content' => $processed_content,
                    'redirect' => $thankyou_redirect
                ];
            }

            // Nếu không có form nào được kích hoạt tính năng thankyou, thoát
            if (empty($thankyou_data)) {
                return;
            }

            // Chuyển mảng PHP thành chuỗi JSON
            $thankyou_data_json = wp_json_encode($thankyou_data);

            // Tạo mã HTML một lần duy nhất
            echo <<<HTML
			<div id="overlay" style="display: none;"></div>
			<div id="custom-popup">
				<button id="close-popup">&times;</button>
				<svg id="checkmark" width="100" height="100" viewBox="0 0 100 100">
					<circle cx="50" cy="50" r="45" stroke="green" stroke-width="5" fill="none"/>
					<path d="M20 50 L40 70 L80 30" stroke="green" stroke-width="5" fill="none" stroke-linecap="round"/>
				</svg>
				<div id="popup-text"></div>
			</div>
			HTML;

            // scripts
            echo <<<HTML
			<script type="text/javascript">
				// Lưu trữ nội dung "thankyou" của các form
				let thankyouData = $thankyou_data_json;

				// Xử lý sự kiện wpcf7mailsent
				document.addEventListener('wpcf7mailsent', function (event) {
					const formId = event.detail.contactFormId;
					const data = thankyouData[formId];
					const form = event.target;
					if (data) {

						// alert
						if (data.style === 'alert_content') {
							const overlay = document.getElementById('overlay');
							const popup = document.getElementById('custom-popup');
							const checkmark = document.getElementById('checkmark');
							const popupText = document.getElementById('popup-text');

							// Hiển thị overlay và popup
							overlay.style.display = 'block';
							popup.style.display = 'block';
							checkmark.style.display = 'block';
							popupText.innerHTML = data.content; // Sử dụng innerHTML để hiển thị HTML

							// Animation cho checkmark
							setTimeout(() => {
								checkmark.style.strokeDasharray = '1000';
								checkmark.style.strokeDashoffset = '0';
							}, 100);

							// Tự động đóng popup sau 5 giây
							setTimeout(() => {
								overlay.style.display = 'none';
								popup.style.display = 'none';
							}, 5000); // 5000 milliseconds = 5 giây
							
						} 

						// toggle
						if (data.style === 'toggle_content') {

							// Tạo một thẻ div mới
							const thankyouDiv = document.createElement('div');
							thankyouDiv.id = 'thankyou-message-' + formId;
							thankyouDiv.innerHTML = data.content; // Điền nội dung vào thẻ div

							// Thêm thẻ div vào DOM, ngay sau form
							form.parentNode.insertBefore(thankyouDiv, form.nextSibling);

							// Ẩn form và hiển thị thẻ div
							form.style.display = 'none';
							thankyouDiv.style.display = 'block';
						}

						// redirect
						if (data.style === 'redirect' && data.redirect) {
							const formData = new FormData(form);
							const queryParams = new URLSearchParams();

							formData.forEach((value, key) => {
								queryParams.append(key, value);
							});

							const redirectUrl = new URL(data.redirect, window.location.origin);
							redirectUrl.search = queryParams.toString();	
							// console.log(redirectUrl.toString()); 		
							window.location.href = redirectUrl.toString();
						}
					}
				}, false);

				// Đóng popup và ẩn overlay khi nhấn nút close
				document.getElementById('close-popup').addEventListener('click', function () {
					document.getElementById('overlay').style.display = 'none';
					document.getElementById('custom-popup').style.display = 'none';
				});

				// Đóng popup và ẩn overlay khi nhấn bên ngoài popup
				document.getElementById('overlay').addEventListener('click', function () {
					document.getElementById('overlay').style.display = 'none';
					document.getElementById('custom-popup').style.display = 'none';
				});
			</script>
			HTML;

            // Thêm CSS của bạn
            echo <<<CSS
			<style type="text/css">
				#checkmark {
					display: none;
					stroke-dasharray: 1000;
					stroke-dashoffset: 1000;
					transition: stroke-dashoffset 1s ease-in-out;
					margin: 15px auto 30px auto;
				}

				#custom-popup {
					display: none;
					position: fixed;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					background: white;
					padding: 15px;
					box-shadow: 0 1px 3px -2px rgba(0,0,0,.12),0 1px 2px rgba(0,0,0,.24);
					width: 500px;
					max-width: calc(100% - 30px);
					text-align: center;
					z-index: 1002;
				}

				#close-popup {
					position: absolute;
					top: 10px;
					right: 10px;
					border: 1px solid lightgray;
					background: white;
					font-size: 1.3em;
					width: 1.3em;
					height: 1.3em;
					cursor: pointer;
					padding: 0;
					margin: 0;
					border-radius: 0%;
					display: flex;
					align-items: center;
					justify-content: center;
					box-sizing: border-box;
					transition: background 0.3s ease, border-color 0.3s ease;
					min-height: unset;
				}

				#close-popup:hover {
					background: #f0f0f0;
					border-color: #ccc;
				}

				#close-popup:active {
					background: #e0e0e0;
					border-color: #bbb;
				}

				#overlay {
					display: none;
					position: fixed;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					background: rgba(0, 0, 0, 0.5);
					z-index: 1001;
				}
			</style>
			CSS;
        }, 100);
    }
}


/*
	////////// CF7 Form tested
	<div style="border: 1px solid black">[select menu-602 "Chọn"]</div> <div style="border: 1px solid black">[select* menu-603 "Chọn"]</div> <div style="border: 1px solid black">[checkbox checkbox-551 use_label_element "Chọn"]</div> <div style="border: 1px solid black">[checkbox* checkbox-553 use_label_element "Chọn"]</div> <div style="border: 1px solid black">[radio radio-774 default:1 "Chọn"]</div> <div style="border: 1px solid black">[radio radio-775 use_label_element default:1 "Chọn"]</div> <div style="border: 1px solid black">[submit "Gửi"]</div>


	// CODE functions.php tested
	$a = new \Adminz\Helper\Cf7;
	$a->form_tags = [
		[
			'tag_name'=> 'menu-602',
			'callback'=> function(){
				return [
					"1"=>"Option 1",
					"2"=>"Option 2"
				];
			}
		],
		[
			'tag_name'=> 'menu-603',
			'callback'=> function(){
				return [
					"1"=>"Option 1",
					"2"=>"Option 2"
				];
			}
		],
		[
			'tag_name'=> 'checkbox-551',
			'callback'=> function(){
				return [
					"1"=>"Option 1",
					"2"=>"Option 2"
				];
			}
		],
		[
			'tag_name'=> 'checkbox-553',
			'callback'=> function(){
				return [
					"1"=>"Option 1",
					"2"=>"Option 2"
				];
			}
		],
		[
			'tag_name'=> 'radio-774',
			'callback'=> function(){
				return [
					"1"=>"Option 1",
					"2"=>"Option 2"
				];
			}
		],
		[
			'tag_name'=> 'radio-775',
			'callback'=> function(){
				return [
					"1"=>"Option 1",
					"2"=>"Option 2"
				];
			}
		]
	];
	$a->make_form_tags();
*/