<?php
class MilestoneManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function recordVisit($user_id, $amount = 0) {
        try {
            // Update users visit_count and total_spent
            $stmt = $this->db->prepare("UPDATE users SET visit_count = visit_count + 1, total_spent = total_spent + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // Fetch new stats
            $stmt = $this->db->prepare("SELECT visit_count, total_spent FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $this->checkMilestones($user_id, $user['visit_count'], $user['total_spent']);
            }
        } catch (Exception $e) {
            error_log("MilestoneManager recordVisit Error: " . $e->getMessage());
        }
    }

    private function checkMilestones($user_id, $visit_count, $total_spent) {
        // Get all milestones the user hasn't achieved yet
        $stmt = $this->db->prepare("
            SELECT m.* 
            FROM milestones m
            LEFT JOIN user_milestones um ON m.id = um.milestone_id AND um.user_id = ?
            WHERE um.id IS NULL
        ");
        $stmt->execute([$user_id]);
        $unachieved_milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($unachieved_milestones as $milestone) {
            $achieved = false;
            if ($milestone['type'] == 'visit' && $visit_count >= $milestone['threshold']) {
                $achieved = true;
            } elseif ($milestone['type'] == 'spend' && $total_spent >= $milestone['threshold']) {
                $achieved = true;
            }

            if ($achieved) {
                // Insert into user_milestones
                $insertStmt = $this->db->prepare("
                    INSERT INTO user_milestones (user_id, milestone_id, achieved_at, is_redeemed) 
                    VALUES (?, ?, NOW(), 0)
                ");
                $insertStmt->execute([$user_id, $milestone['id']]);
            }
        }
    }
}
