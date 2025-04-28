# WP-Optin-Wheel-Pro
Mục tiêu: Ngăn người dùng quay vòng quay nếu số CMND (number_1) của họ đã được ghi lại trong cơ sở dữ liệu cho vòng quay cụ thể đó.

# Bước 1: Sửa đổi tệp Log_Service.php

Mục đích: Thêm một hàm mới để kiểm tra xem một giá trị trường cụ thể (như CMND) đã tồn tại trong bảng log (wp_wof_optins) cho một vòng quay nhất định hay chưa.
Vị trí: Mở tệp d:\mabel-wheel-of-fortune\code\services\class-log-service.php.
Thêm hàm mới: Thêm hàm has_field_value_been_logged vào bên trong lớp Log_Service.

```
<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Code\Models\Wheel_Model;
        use MABEL_WOF\Core\Common\Linq\Enumerable; // <-- Đảm bảo dòng này tồn tại ở đầu tệp

	class Log_Service {

		// ... (Giữ nguyên các hàm hiện có như type_of_logging, drop_logs, ...) ...

        /**
         * Kiểm tra xem một giá trị trường cụ thể đã được ghi log cho một vòng quay chưa.
         *
         * @param int $wheel_id ID của vòng quay.
         * @param string $field_id ID của trường cần kiểm tra (ví dụ: 'number_1').
         * @param mixed $field_value Giá trị cần tìm kiếm.
         * @return bool True nếu giá trị tồn tại cho trường đó, False nếu không.
         */
        public static function has_field_value_been_logged($wheel_id, $field_id, $field_value) {
            global $wpdb;

            // Chuẩn bị câu truy vấn để chọn log có dữ liệu trường cho vòng quay cụ thể
            $query = $wpdb->prepare(
                "SELECT fields FROM " . $wpdb->prefix . "wof_optins WHERE wheel_id = %d AND type = 0 AND fields IS NOT NULL AND fields != '' AND fields != '[]'",
                intval($wheel_id)
            );

            $results = $wpdb->get_results($query);

            if (empty($results)) {
                return false; // Không tìm thấy log nào có trường dữ liệu cho vòng quay này
            }

            // Lặp qua các log và kiểm tra dữ liệu JSON 'fields'
            foreach ($results as $row) {
                $logged_fields = json_decode($row->fields);

                // Kiểm tra xem giải mã JSON có thành công và là một mảng không
                if (is_array($logged_fields)) {
                    foreach ($logged_fields as $logged_field) {
                        // Kiểm tra xem ID và giá trị trường có khớp không
                        if (isset($logged_field->id) && $logged_field->id === $field_id && isset($logged_field->value) && (string)$logged_field->value === (string)$field_value) {
                            return true; // Tìm thấy trùng khớp
                        }
                    }
                }
            }

            return false; // Không tìm thấy trùng khớp sau khi kiểm tra tất cả log
        }


		// ... (Giữ nguyên các hàm hiện có khác như update_optin_in_db, log_play_to_db, ...) ...

		public static function has_played_yet(Wheel_Model $wheel,$provider_obj,$mail = '', $days = -1, &$out_checked_with = null) {
			// ... (Giữ nguyên code hiện có) ...
		}

	}

}

```

# Bước 2: Sửa đổi tệp Public_Controller.php

Mục đích: Gọi hàm kiểm tra CMND mới tạo (has_field_value_been_logged) bên trong phương thức optin() trước khi cho phép quay. Nếu CMND đã tồn tại, gửi lỗi về trình duyệt.
Vị trí: Mở tệp d:\mabel-wheel-of-fortune\code\controllers\class-public-controller.php.
Sửa đổi phương thức optin(): Tìm phương thức optin() và thêm đoạn mã kiểm tra CMND vào sau dòng $fields = isset($_POST['fields']) ? json_decode(sanitize_text_field(stripslashes($_POST['fields']))) : [];.

```
		public function optin() {

			// ... (Giữ nguyên phần đầu của hàm optin) ...

			$fields = isset($_POST['fields']) ? json_decode(sanitize_text_field(stripslashes($_POST['fields']))) : [];

			// --- START: Khối mã kiểm tra CMND ---
            $cmnd_field_id = 'number_1'; // ID của trường CMND của bạn
            $cmnd_value = null;

            // Tìm giá trị CMND từ các trường đã gửi
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    if (isset($field->id) && $field->id === $cmnd_field_id && isset($field->value)) {
                        $cmnd_value = trim($field->value);
                        break;
                    }
                }
            }

            // Nếu giá trị CMND được gửi lên, kiểm tra xem nó có tồn tại trong log không
            if ($cmnd_value !== null && $cmnd_value !== '') {
                if (Log_Service::has_field_value_been_logged($wheel->id, $cmnd_field_id, $cmnd_value)) {
                    // CMND đã tồn tại, gửi lỗi
                    wp_send_json_error(
                        // Sử dụng cài đặt mới hoặc thông báo mặc định
                        $wheel->setting_or_default('cmnd_already_used', __('This CMND has already been used.', 'mabel-wheel-of-fortune'))
                    );
                }
            }
            // --- END: Khối mã kiểm tra CMND ---


			// ... (Giữ nguyên phần còn lại của hàm optin) ...

			$this->play($wheel); // Chỉ được gọi nếu tất cả các kiểm tra đều thành công

		}

```

# Bước 3: (Tùy chọn nhưng nên làm) Thêm cài đặt thông báo lỗi tùy chỉnh

Mục đích: Cho phép quản trị viên tùy chỉnh thông báo lỗi hiển thị khi CMND bị trùng lặp.
Sửa đổi tệp Wheel_Model.php:
Vị trí: d:\mabel-wheel-of-fortune\code\models\class-wheel-model.php
Thêm thuộc tính mới vào lớp Wheel_Model:

```
public $cmnd_already_used;
```

Sửa đổi tệp Wheel_Service.php:
Vị trí: d:\mabel-wheel-of-fortune\code\services\class-wheel-service.php
Trong hàm raw_to_wheel, thêm dòng sau:

```
if(!empty($options->cmnd_already_used))
    $wheel->cmnd_already_used = $options->cmnd_already_used;
```

Sửa đổi tệp Admin_Controller.php:
Vị trí: d:\mabel-wheel-of-fortune\code\controllers\class-admin-controller.php
Trong hàm create_addwheel_model, tìm mảng $content_settings->options và thêm một Text_Option mới vào đó:

```
$content_settings->options[] = $this->add_data_attribute_for_data_bind(new Text_Option(
    'cmnd_already_used',
    __("'CMND already used' error", 'mabel-wheel-of-fortune'),
    null,
    __("This CMND has already been used.", 'mabel-wheel-of-fortune'),
    __('Error message shown if the entered CMND value is already found in the logs for this wheel.','mabel-wheel-of-fortune')
));
```
