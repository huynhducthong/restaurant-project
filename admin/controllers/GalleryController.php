<?php
require_once __DIR__ . '/../../config/database.php';

class GalleryController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->createTableIfNotExists();
        $this->handleAction();
    }

    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS galleries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_url VARCHAR(255) NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->conn->exec($sql);
    }

    private function handleAction() {
        $action = $_GET['action'] ?? 'index';

        switch ($action) {
            case 'store':
                $this->store();
                break;
            case 'toggle':
                $this->toggleActive();
                break;
            case 'delete':
                $this->delete();
                break;
            default:
                $this->index();
                break;
        }
    }

    private function index() {
        $stmt = $this->conn->query("SELECT * FROM galleries ORDER BY sort_order ASC, id DESC");
        $galleries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/galleries/index.php';
    }

    private function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $sort_order = $_POST['sort_order'] ?? 0;
            $image_url = '';

            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/assets/img/gallery/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $image_url = $fileName;
                }
            }

            if ($image_url) {
                $sql = "INSERT INTO galleries (image_url, title, sort_order, is_active) VALUES (:image_url, :title, :sort_order, 1)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':image_url' => $image_url,
                    ':title' => $title,
                    ':sort_order' => $sort_order
                ]);
                $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => 'Thêm ảnh thành công!'];
            } else {
                $_SESSION['settings_flash'] = ['type' => 'error', 'msg' => 'Vui lòng chọn một hình ảnh hợp lệ.'];
            }

            header("Location: /restaurant-project/admin/controllers/settings.php?tab=gallery");
            exit;
        }
    }

    private function toggleActive() {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $sql = "UPDATE galleries SET is_active = NOT is_active WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => 'Cập nhật trạng thái thành công!'];
        }
        header("Location: /restaurant-project/admin/controllers/settings.php?tab=gallery");
        exit;
    }

    private function delete() {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            
            // Get image to delete file
            $stmt = $this->conn->prepare("SELECT image_url FROM galleries WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $gallery = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($gallery) {
                $filePath = __DIR__ . '/../../public/assets/img/gallery/' . $gallery['image_url'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                $stmt = $this->conn->prepare("DELETE FROM galleries WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => 'Xóa ảnh thành công!'];
            }
        }
        header("Location: /restaurant-project/admin/controllers/settings.php?tab=gallery");
        exit;
    }
}
