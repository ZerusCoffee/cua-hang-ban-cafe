# Tổng Quan Dự Án "Cửa Hàng Bán Cafe"

Dự án "Cửa Hàng Bán Cafe" có một hệ thống khá hoàn chỉnh, bao gồm các chức năng dành cho khách hàng và một hệ thống quản trị mạnh mẽ được xây dựng bằng Filament.

## Các Chức Năng Đã Hoàn Thành

### Chức Năng Frontend/API (Dành cho Khách hàng)

*   **Xác thực & Quản lý Người dùng:**
    *   Đăng ký tài khoản khách hàng mới.
    *   Đăng nhập/Đăng xuất.
    *   Khôi phục mật khẩu.
    *   Quản lý thông tin cá nhân và hồ sơ.
*   **Danh mục Sản phẩm:**
    *   Xem danh sách các danh mục sản phẩm.
    *   Xem danh sách sản phẩm trong từng danh mục.
    *   Xem chi tiết từng sản phẩm.
    *   Tìm kiếm và lọc sản phẩm.
*   **Giỏ hàng:**
    *   Thêm/xóa sản phẩm vào giỏ hàng.
    *   Cập nhật số lượng sản phẩm trong giỏ hàng.
*   **Thanh toán & Đặt hàng:**
    *   Thực hiện quy trình thanh toán.
    *   Quản lý địa chỉ giao hàng.
    *   Áp dụng mã giảm giá.
*   **Lịch sử Đơn hàng:**
    *   Xem danh sách các đơn hàng đã đặt.
    *   Xem chi tiết từng đơn hàng.
*   **Đánh giá:**
    *   Viết đánh giá cho sản phẩm hoặc đơn hàng.
*   **Mã giảm giá:**
    *   Áp dụng các mã giảm giá cho đơn hàng.

### Chức Năng Backend (Bảng điều khiển Admin - Filament)

Hệ thống quản trị được xây dựng bằng Filament, cung cấp giao diện trực quan và đầy đủ cho các chức năng quản lý quan trọng:

*   **Quản lý loại sản phẩm & sản phẩm (thêm/sửa/xóa/ẩn):**
    *   `Categories Resource`: Quản lý danh mục sản phẩm (thêm, sửa, xóa).
    *   `Products Resource`: Quản lý sản phẩm (thêm, sửa, xóa, ẩn/hiện, gán nguyên liệu, cấu hình tùy chọn).
*   **Nhập hàng theo lô (batch/import inventory):**
    *   `ImportOrders Resource`: Quản lý các phiếu nhập hàng. Hệ thống hỗ trợ quy trình nhập hàng theo lô từ các nhà cung cấp.
*   **Quản lý đơn hàng + trạng thái:**
    *   `Orders Resource`: Quản lý toàn bộ đơn hàng của khách hàng, bao gồm cập nhật trạng thái đơn hàng (ví dụ: đang xử lý, đã giao, hủy bỏ). Bảng `order_status_histories` giúp theo dõi chi tiết lịch sử trạng thái.
*   **Quản lý tồn kho (FIFO) / Giá vốn:**
    *   Mặc dù không thể khẳng định chắc chắn là FIFO chỉ từ schema, nhưng sự tồn tại của `Ingredients Resource`, `IngredientObserver.php`, và bảng `ingredient_import_logs` với các trường như `stock_before`, `stock_after`, `cost_price_before`, `cost_price_after` cho thấy một hệ thống quản lý tồn kho và tính giá vốn bình quân (hoặc FIFO) đã được triển khai hoặc có khả năng phát triển. Bảng `order_items` với trường `unit_cost` (giá vốn lúc bán) cũng là một phần quan trọng trong việc tính toán lợi nhuận.
*   **Báo cáo nhập-xuất-tồn:**
    *   **Báo cáo Nhập:** `ImportStatsWidget` và `RecentImportsWidget` cho thấy khả năng thống kê và hiển thị các phiếu nhập hàng gần đây.
    *   **Báo cáo Xuất:** `ProductExportReportWidget` gợi ý về báo cáo các sản phẩm đã xuất bán.
    *   **Báo cáo Tồn:** `StockLookupWidget` cung cấp chức năng tra cứu tồn kho hiện tại. Bảng `ingredient_import_logs` là nền tảng cho việc tạo các báo cáo chi tiết về biến động tồn kho.
*   **Các Chức Năng Admin Khác:**
    *   `Coupons Resource`: Quản lý các mã giảm giá.
    *   `Customers Resource`: Quản lý thông tin khách hàng.
    *   `Ingredients Resource`: Quản lý nguyên liệu thô, bao gồm giá vốn, số lượng tồn kho và ngưỡng cảnh báo.
    *   `OptionGroups Resource`, `Options Resource`: Quản lý các nhóm tùy chọn (ví dụ: kích cỡ, mức đường, đá) cho sản phẩm.
    *   `Reviews Resource`: Quản lý và kiểm duyệt các đánh giá của khách hàng.
    *   `Suppliers Resource`: Quản lý thông tin nhà cung cấp nguyên liệu.
    *   `Units Resource`: Quản lý các đơn vị đo lường.
    *   `Users Resource`: Quản lý người dùng admin/nhân viên.

## Các Bảng Database Quan Trọng

*   **`users`**: Lưu trữ thông tin người dùng (admin và khách hàng).
*   **`customers`**: Thông tin chi tiết khách hàng.
*   **`products`**: Thông tin sản phẩm, bao gồm tên, giá, mô tả.
*   **`categories`**: Danh mục sản phẩm.
*   **`ingredients`**: Nguyên liệu thô, số lượng tồn kho (`stock`), giá vốn (`cost_price`), và ngưỡng cảnh báo (`threshold`).
*   **`units`**: Đơn vị tính cho nguyên liệu.
*   **`suppliers`**: Thông tin nhà cung cấp nguyên liệu.
*   **`import_orders`**: Lưu trữ thông tin các phiếu nhập hàng (mã phiếu, nhà cung cấp, trạng thái, ghi chú, ngày nhập).
*   **`import_order_details`**: Chi tiết từng mặt hàng (nguyên liệu) trong một phiếu nhập, bao gồm `ingredient_id`, `quantity`, `unit_price`, `total_price`.
*   **`ingredient_import_logs`**: **Rất quan trọng cho quản lý tồn kho**. Ghi lại nhật ký mỗi lần nhập nguyên liệu, bao gồm `quantity`, `stock_before`, `stock_after`, `unit_price`, `cost_price_before`, `cost_price_after` (giá vốn bình quân hoặc giá nhập theo lô), và `imported_at`.
*   **`orders`**: Thông tin chính về đơn hàng (khách hàng, tổng tiền, trạng thái).
*   **`order_items`**: Chi tiết các sản phẩm trong mỗi đơn hàng, bao gồm `product_id`, `product_name`, `price`, `quantity`, tùy chọn (`options`), và đặc biệt là `unit_cost` (giá vốn của sản phẩm tại thời điểm bán).
*   **`order_status_histories`**: Theo dõi lịch sử thay đổi trạng thái của đơn hàng.
*   **`order_profit_logs`**: Gợi ý một hệ thống ghi nhận và phân tích lợi nhuận theo từng đơn hàng.
*   **`coupons`**: Thông tin mã giảm giá.
*   **`coupon_usages`**: Ghi lại việc sử dụng mã giảm giá.
*   **`reviews`**: Đánh giá của khách hàng.
*   **`recipe_details`**: (Dự kiến) Lưu trữ công thức sản phẩm, liên kết sản phẩm với các nguyên liệu cần thiết và số lượng.
*   **`option_groups`**, **`options`**, **`product_options`**, **`product_option_modifiers`**: Quản lý các tùy chọn phức tạp cho sản phẩm (ví dụ: kích cỡ, thêm topping, mức đường/đá).