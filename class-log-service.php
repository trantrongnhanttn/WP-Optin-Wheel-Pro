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
