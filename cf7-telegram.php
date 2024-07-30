<?php
/*
Plugin Name: CF 7 to Telegram
Plugin URI: https://page1.vn/
Description: Tự động gửi thông tin từ Contact Form 7 đến Telegram
Version: 1.0
Author: PAGE1 SEO Agency
Author URI: https://page1.vn/
*/

add_action('admin_menu', 'cf7_to_telegram_add_options_page');
add_action('admin_init', 'cf7_to_telegram_register_settings');

function cf7_to_telegram_add_options_page() {
    add_options_page(
        'Contact Form 7 to Telegram',
        'CF7 to Telegram',
        'manage_options',
        'cf7-to-telegram',
        'cf7_to_telegram_options_page'
    );
}

function cf7_to_telegram_register_settings() {
    register_setting('cf7-to-telegram-settings', 'cf7_to_telegram_bot_token', 'sanitize_text_field');
    register_setting('cf7-to-telegram-settings', 'cf7_to_telegram_chat_id', 'sanitize_text_field');
}

function cf7_to_telegram_options_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Bạn không có quyền truy cập vào trang này.'));
    }
    ?>
    <div class="wrap">
        <h1>Contact Form 7 to Telegram</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cf7-to-telegram-settings');
            do_settings_sections('cf7-to-telegram-settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Telegram Bot Token</th>
                    <td>
                        <input type="text" name="cf7_to_telegram_bot_token" value="<?php echo esc_attr(get_option('cf7_to_telegram_bot_token')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Telegram Chat ID or Channel Name</th>
                    <td>
                        <input type="text" name="cf7_to_telegram_chat_id" value="<?php echo esc_attr(get_option('cf7_to_telegram_chat_id')); ?>" />
                        <p class="description">Nhập ID số hoặc tên kênh Telegram bắt đầu bằng "@".</p>
                    </td>
                </tr>
            </table>
            <?php
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('wpcf7_mail_sent', 'send_to_telegram');

function send_to_telegram($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $posted_data = $submission->get_posted_data();

        $telegram_bot_token = get_option('cf7_to_telegram_bot_token');
        $telegram_chat_id = get_option('cf7_to_telegram_chat_id');

        // Lấy thông tin tên form và tên website
        $form_title = sanitize_text_field($contact_form->title());
        $site_name = get_bloginfo('name');

        // Tạo tiêu đề và nội dung tin nhắn
        $message = "Có một lượt gửi form từ website " . sanitize_text_field($site_name) . ":\n";
        $message .= "Tên form: " . sanitize_text_field($form_title) . "\n\n";
        foreach ($posted_data as $key => $value) {
            if (!is_array($value)) {
                $message .= sanitize_text_field($key) . ": " . sanitize_text_field($value) . "\n";
            }
        }

        // Gửi tin nhắn tới Telegram
        $url = "https://api.telegram.org/bot" . sanitize_text_field($telegram_bot_token) . "/sendMessage";
        $response = wp_remote_post($url, array(
            'body' => array(
                'chat_id' => sanitize_text_field($telegram_chat_id),
                'text' => $message,
            ),
        ));

        if (is_wp_error($response)) {
            error_log('Error sending message to Telegram: ' . $response->get_error_message());
        }
    }
}
?>
