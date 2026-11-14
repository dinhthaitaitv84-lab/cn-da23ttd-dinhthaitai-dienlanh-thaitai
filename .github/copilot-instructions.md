# QUY TẮC DỰ ÁN (bắt buộc tuân thủ ở mọi yêu cầu)

Đề tài: Website quản lý dịch vụ sửa chữa điện, điện lạnh tại nhà.
Stack: HTML + CSS + JavaScript thuần, PHP thuần MVC, MySQL (XAMPP), deploy InfinityFree.

## Kiến trúc
- Cấu trúc: scr/app/{controllers,models,views}, scr/config, scr/public/{css,js,uploads}, scr/index.php là front controller.
- Controller kế thừa Controller.php; model dùng PDO + prepared statements.
- View dùng layout chung (header/footer), không lặp HTML.

## Quy tắc code
- PHP 7.4+, không dùng composer/thư viện ngoài.
- PDO prepared statements; escape output bằng htmlspecialchars.
- password_hash/password_verify; CSRF token cho mọi form POST.
- Phân quyền role: admin, technician, customer; kiểm tra ở đầu mỗi action.
- Upload ảnh: jpg/png/webp, tối đa 2MB, đổi tên ngẫu nhiên, lưu public/uploads.
- Giao diện tiếng Việt; tên biến/hàm/bảng tiếng Anh; responsive, font system-ui/Segoe UI, cỡ chữ cơ bản 16px.
- Bảng màu giao diện: --primary:#264653; --primary-dark:#1b343e; --accent:#b08d57; --bg:#f4f2ee; --surface:#fff; --text:#2b2b2b; --muted:#6b7280; --border:#e3ded5; --radius:10px; --shadow:0 2px 8px rgba(0,0,0,.06).

## QUY TẮC TRẢ LỜI (NGHIÊM NGẶT)
1. Chỉ viết code và thực thi. KHÔNG giải thích, KHÔNG dẫn dắt, KHÔNG nhắc lại yêu cầu trước và trong khi làm.
2. Chỉ sửa/tạo đúng các file được yêu cầu. Không viết lại file không liên quan.
3. Không tự thêm tính năng, không tạo dữ liệu thừa, không thêm comment dài.
4. Sau khi làm xong, chỉ trả lời đúng định dạng sau (tối đa 5 dòng):
   - Đã tạo: <danh sách file>
   - Đã sửa: <danh sách file>
   - Cách test: <1-2 dòng>
5. Nếu thiếu thông tin hoặc có lỗi, chỉ hỏi/ báo đúng 1 câu ngắn.