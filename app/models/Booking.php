<?php
class Booking {
    private $conn;
    private $table_name = "bookings";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $email, $phone, $date, $time, $people) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (customer_name, email, phone, booking_date, booking_time, people_count) 
                  VALUES (:name, :email, :phone, :date, :time, :people)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":time", $time);
        $stmt->bindParam(":people", $people);

        return $stmt->execute();
    }
}
?>