# Báo Cáo Chi Tiết: Phân Hệ Dịch Vụ & Đặt Bàn (Booking Services)

**Dự án:** Hệ Thống Quản Lý Nhà Hàng (Restaurantly)
**Phân hệ:** Dịch vụ Đặt chỗ (Booking Service - `booking_service.php`)

---

## 1. Tổng Quan (Overview)
Phân hệ Dịch vụ là trung tâm tạo ra doanh thu và kết nối trực tiếp với khách hàng. Được thiết kế theo phong cách Fine Dining đẳng cấp, phân hệ này không chỉ là một biểu mẫu (form) nhập liệu đơn thuần mà là một "Hành trình trải nghiệm khách hàng", cho phép cá nhân hóa từng chi tiết nhỏ nhất.

## 2. Phân Loại Dịch Vụ (Service Types)
Hệ thống hiện tại hỗ trợ 3 loại hình dịch vụ chính, đáp ứng các nhu cầu đa dạng của giới thượng lưu:

1.  **Đặt Chỗ Cao Cấp (Standard Table Booking):**
    *   **Mục đích:** Khách hàng đến dùng bữa tại nhà hàng.
    *   **Đặc điểm:** Cho phép xem sơ đồ nhà hàng trực quan và chọn trước vị trí bàn (Bàn mở hoặc Phòng VIP riêng tư).
2.  **Không Gian Kỷ Niệm (Event/Birthday Booking):**
    *   **Mục đích:** Tổ chức các dịp đặc biệt (Sinh nhật, kỷ niệm cưới, cầu hôn, họp mặt).
    *   **Đặc điểm:** Cung cấp các tùy chọn cá nhân hóa sâu sắc như chọn gói trang trí (Lãng mạn, Hoàng gia), đặt bánh kem, và thiết kế hoa tươi.
3.  **Đầu Bếp Tại Gia (Private Chef/Bespoke Catering):**
    *   **Mục đích:** Đưa trải nghiệm Fine Dining về phục vụ tận không gian riêng của khách hàng (nhà riêng, biệt thự).
    *   **Đặc điểm:** Thu thập địa chỉ phục vụ chi tiết (liên kết với sổ địa chỉ của khách), thu thập yêu cầu ngân sách, phong cách ẩm thực và các lưu ý đặc biệt (dị ứng, khẩu vị).

## 3. Quy Trình Trải Nghiệm Người Dùng (User Workflow)

Hệ thống được thiết kế theo luồng cuộn dọc (Single-page flow) từ trên xuống dưới, giúp khách hàng không bị rối:

*   **Bước 1: Xác định thông tin cá nhân.** Tự động lấy dữ liệu từ tài khoản (Tên, SĐT) giúp tiết kiệm thời gian. Nhập ngày giờ và số lượng khách.
*   **Bước 2: Cấu hình Dịch vụ (Theo từng loại).** Khách hàng sẽ thấy các trường thông tin khác nhau tùy thuộc vào loại dịch vụ (chọn bàn, chọn gói trang trí, hoặc nhập địa chỉ phục vụ).
*   **Bước 3: Lựa chọn Ẩm thực (Tinh Hoa Ẩm Thực).** 
    *   Khách hàng có thể chọn "Gọi Món Tự Do" (A la Carte).
    *   Chọn các gói "Bộ Sưu Tập Hương Vị" (Combos) đã được set-up sẵn.
    *   Đặc biệt: Tùy chọn **"Thiết Kế Riêng" (Bespoke Tasting Menu)** mở ra form nhập liệu ngân sách và yêu cầu phong cách ẩm thực cho Đầu bếp.
*   **Bước 4: Gọi món Add-on.** Cho phép chọn thêm các món lẻ trực tiếp từ danh sách nếu cần.
*   **Bước 5: Ghi chú đặc biệt.** Các yêu cầu khác như dị ứng, dặn dò.
*   **Bước 6: Tóm tắt & Thanh toán cọc.** Panel "Tóm Tắt Đặt Chỗ" (Summary) trượt dọc theo màn hình, hiển thị minh bạch các khoản phí (Phí vị trí, phí món ăn) và yêu cầu **cọc trước 30%** để giữ chỗ.

## 4. Đặc Điểm Giao Diện & Kỹ Thuật (UI/UX & Technical Specs)

> [!TIP]
> **Đẳng Cấp Fine Dining:** Giao diện được thiết kế với dải màu Xanh ngọc lục bảo (`#143B36`) và Vàng kim (`var(--gold)`), kết hợp hiệu ứng kính (Glassmorphism) và font chữ có chân sang trọng (`Cormorant Garamond`).

*   **Real-time Update:** Bảng tóm tắt hóa đơn được cập nhật trực tiếp (Javascript) mỗi khi khách hàng thay đổi số lượng người, đổi bàn, hoặc chọn thêm món mà không cần tải lại trang.
*   **Smart Defaults:** Tự động lấy địa chỉ mặc định từ `user_addresses` cho dịch vụ Đầu Bếp Tại Gia.
*   **Interactive Map:** Tích hợp Modal xem sơ đồ nhà hàng để chọn chỗ ngồi trực quan.

## 5. Tiềm Năng Mở Rộng Trong Tương Lai
Để phân hệ này hoàn hảo hơn, có thể cân nhắc tích hợp thêm:
1.  **Cổng Thanh Toán Trực Tuyến:** Tích hợp VNPay, Momo, hoặc Stripe để khách hàng thanh toán 30% tiền cọc ngay lập tức.
2.  **Sommelier Service:** Tùy chọn chọn Rượu Vang ghép nối (Wine Pairing) với Bộ Sưu Tập Hương Vị.
3.  **Thông báo đa kênh:** Gửi Zalo ZNS hoặc SMS tự động khi khách hàng đặt bàn thành công.
