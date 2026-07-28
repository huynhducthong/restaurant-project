# Sơ đồ Use Case - Restaurantly

## Mô tả ngắn

Sơ đồ dưới đây được vẽ lại dựa trên các phân hệ hiện có trong dự án `Restaurantly`, gồm:

- Khối khách truy cập và khách hàng
- Khối vận hành backend cho admin và nhân viên
- Các tác nhân ngoài hệ thống như Google OAuth, Email OTP và Telegram

## Mermaid

```mermaid
usecaseDiagram
  actor Guest as "Khách vãng lai"
  actor Customer as "Khách hàng"
  actor Staff as "Nhân viên"
  actor Admin as "Admin"
  actor Google as "Google OAuth"
  actor Email as "Email OTP"
  actor Telegram as "Telegram"

  rectangle Restaurantly {
    (Xem trang chủ)
    (Xem thực đơn)
    (Xem combo)
    (Xem đầu bếp)
    (Xem bài viết / About Us)
    (Gửi liên hệ)
    (Đăng ký newsletter)

    (Đăng ký tài khoản)
    (Đăng nhập)
    (Đăng nhập bằng Google)
    (Quên mật khẩu / nhận OTP)
    (Quản lý hồ sơ cá nhân)
    (Quản lý địa chỉ)

    (Đặt bàn)
    (Đặt tiệc sinh nhật / sự kiện)
    (Đặt chef tại gia)
    (Chọn bàn / món / combo)
    (Xem kết quả đặt dịch vụ)

    (Like / bình luận / chia sẻ bài viết)
    (Lưu bài viết)
    (Báo cáo bình luận)

    (Đăng nhập backend)
    (Xem dashboard)
    (Quản lý banner / nội dung / footer)
    (Quản lý thực đơn / combo / đầu bếp)
    (Quản lý bàn / dịch vụ)
    (Quản lý người dùng)
    (Xác nhận booking)
    (Quản lý kho)
    (Nhập kho)
    (Chuyển kho)
    (Kiểm kê kho)
    (Lập PO / nhận hàng)
    (Xem báo cáo / xuất PDF-CSV)
    (Cấu hình hệ thống)

    (Gửi OTP qua email)
    (Gửi thông báo Telegram)
  }

  Guest --> (Xem trang chủ)
  Guest --> (Xem thực đơn)
  Guest --> (Xem combo)
  Guest --> (Xem đầu bếp)
  Guest --> (Xem bài viết / About Us)
  Guest --> (Gửi liên hệ)
  Guest --> (Đăng ký newsletter)
  Guest --> (Đăng ký tài khoản)
  Guest --> (Đăng nhập)
  Guest --> (Đăng nhập bằng Google)
  Guest --> (Quên mật khẩu / nhận OTP)

  Customer --> (Đăng nhập)
  Customer --> (Quản lý hồ sơ cá nhân)
  Customer --> (Quản lý địa chỉ)
  Customer --> (Đặt bàn)
  Customer --> (Đặt tiệc sinh nhật / sự kiện)
  Customer --> (Đặt chef tại gia)
  Customer --> (Xem kết quả đặt dịch vụ)
  Customer --> (Like / bình luận / chia sẻ bài viết)
  Customer --> (Lưu bài viết)
  Customer --> (Báo cáo bình luận)

  Staff --> (Đăng nhập backend)
  Staff --> (Xem dashboard)
  Staff --> (Xác nhận booking)
  Staff --> (Quản lý kho)
  Staff --> (Nhập kho)
  Staff --> (Chuyển kho)
  Staff --> (Kiểm kê kho)
  Staff --> (Xem báo cáo / xuất PDF-CSV)

  Admin --> (Đăng nhập backend)
  Admin --> (Xem dashboard)
  Admin --> (Quản lý banner / nội dung / footer)
  Admin --> (Quản lý thực đơn / combo / đầu bếp)
  Admin --> (Quản lý bàn / dịch vụ)
  Admin --> (Quản lý người dùng)
  Admin --> (Xác nhận booking)
  Admin --> (Quản lý kho)
  Admin --> (Nhập kho)
  Admin --> (Chuyển kho)
  Admin --> (Kiểm kê kho)
  Admin --> (Lập PO / nhận hàng)
  Admin --> (Xem báo cáo / xuất PDF-CSV)
  Admin --> (Cấu hình hệ thống)

  (Đặt bàn) ..> (Chọn bàn / món / combo) : include
  (Đặt tiệc sinh nhật / sự kiện) ..> (Chọn bàn / món / combo) : include
  (Đặt chef tại gia) ..> (Chọn bàn / món / combo) : include
  (Quên mật khẩu / nhận OTP) ..> (Gửi OTP qua email) : include
  (Đăng nhập bằng Google) --> Google
  (Gửi OTP qua email) --> Email
  (Xác nhận booking) ..> (Gửi thông báo Telegram) : include
  (Lập PO / nhận hàng) ..> (Nhập kho) : include
  (Quản lý kho) ..> (Nhập kho) : include
  (Quản lý kho) ..> (Chuyển kho) : include
  (Quản lý kho) ..> (Kiểm kê kho) : include
  (Gửi thông báo Telegram) --> Telegram
```

## Gợi ý sử dụng

- Có thể chèn trực tiếp file này vào báo cáo Markdown
- Nếu cần đưa vào Word hoặc slide, có thể render từ Mermaid sang PNG/SVG
- Nếu bạn muốn, mình có thể tiếp tục tách riêng thành:
  - sơ đồ use case tổng quát
  - sơ đồ use case phía khách hàng
  - sơ đồ use case phía admin
