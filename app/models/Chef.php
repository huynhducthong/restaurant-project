<?php
// File: app/models/Chef.php

class Chef {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Lấy thông tin chi tiết đầu bếp theo ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM chefs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách ảnh gallery của đầu bếp
     */
    public function getGallery($chef_id) {
        $stmt = $this->db->prepare("SELECT * FROM chef_gallery WHERE chef_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$chef_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách chứng chỉ của đầu bếp
     */
    public function getCertificates($chef_id) {
        $stmt = $this->db->prepare("SELECT * FROM chef_certificates WHERE chef_id = ? ORDER BY issue_date DESC, id DESC");
        $stmt->execute([$chef_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra xem đầu bếp có ít nhất 1 chứng chỉ hay không
     */
    public function hasCertificates($chef_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM chef_certificates WHERE chef_id = ?");
        $stmt->execute([$chef_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Lấy số lượng ảnh gallery hiện tại của đầu bếp
     */
    public function getGalleryCount($chef_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM chef_gallery WHERE chef_id = ?");
        $stmt->execute([$chef_id]);
        return (int)$stmt->fetchColumn();
    }
}
?>