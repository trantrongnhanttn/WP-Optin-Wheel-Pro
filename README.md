# WP-Optin-Wheel-Pro
Mục tiêu: Ngăn người dùng quay vòng quay nếu số CMND (number_1) của họ đã được ghi lại trong cơ sở dữ liệu cho vòng quay cụ thể đó.

# Bước 1: Sửa đổi tệp Log_Service.php

Mục đích: Thêm một hàm mới để kiểm tra xem một giá trị trường cụ thể (như CMND) đã tồn tại trong bảng log (wp_wof_optins) cho một vòng quay nhất định hay chưa.
Vị trí: Mở tệp d:\mabel-wheel-of-fortune\code\services\class-log-service.php.
Thêm hàm mới: Thêm hàm has_field_value_been_logged vào bên trong lớp Log_Service.

# Bước 2: Sửa đổi tệp Public_Controller.php

Mục đích: Gọi hàm kiểm tra CMND mới tạo (has_field_value_been_logged) bên trong phương thức optin() trước khi cho phép quay. Nếu CMND đã tồn tại, gửi lỗi về trình duyệt.
Vị trí: Mở tệp d:\mabel-wheel-of-fortune\code\controllers\class-public-controller.php.
Sửa đổi phương thức optin(): Tìm phương thức optin() và thêm đoạn mã kiểm tra CMND vào sau dòng $fields = isset($_POST['fields']) ? json_decode(sanitize_text_field(stripslashes($_POST['fields']))) : [];.

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
