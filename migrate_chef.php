<?php
// File: migrate_chef.php
require_once __DIR__ . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    echo "Connecting to database...\n";

    // 1. Run migrations SQL
    $sql = file_get_contents(__DIR__ . '/migration_chef.sql');
    $db->exec($sql);
    echo "Tables 'chef_gallery' and 'chef_certificates' verified/created successfully.\n";

    // 2. Migrate existing gallery data from chefs.gallery_images column to chef_gallery table
    $stmt = $db->query("SELECT id, name, gallery_images FROM chefs");
    $chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $migratedCount = 0;
    foreach ($chefs as $chef) {
        $chefId = $chef['id'];
        $galleryImagesJson = $chef['gallery_images'];

        if (!empty($galleryImagesJson)) {
            $images = json_decode($galleryImagesJson, true);
            if (is_array($images) && !empty($images)) {
                // Check if already migrated to avoid duplicates
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM chef_gallery WHERE chef_id = ?");
                $checkStmt->execute([$chefId]);
                $exists = (int)$checkStmt->fetchColumn();

                if ($exists === 0) {
                    $insertStmt = $db->prepare("INSERT INTO chef_gallery (chef_id, image, sort_order) VALUES (?, ?, ?)");
                    foreach ($images as $index => $img) {
                        $insertStmt->execute([$chefId, $img, $index]);
                        $migratedCount++;
                    }
                    echo "Migrated " . count($images) . " gallery images for Chef: " . $chef['name'] . " (ID: $chefId)\n";
                } else {
                    echo "Gallery images for Chef: " . $chef['name'] . " already migrated. Skipping.\n";
                }
            }
        }
    }

    echo "Migration finished. Total new image records inserted: $migratedCount\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
