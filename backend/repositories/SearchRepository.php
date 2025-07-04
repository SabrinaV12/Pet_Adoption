<?php

class SearchRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getDistinctCountries(): array {
        $res = $this->conn->query("SELECT DISTINCT country FROM users WHERE country IS NOT NULL AND country != ''");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getDistinctCounties(): array {
        $res = $this->conn->query("SELECT DISTINCT county FROM users WHERE county IS NOT NULL AND county != ''");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getFilteredPets(array $filters) {
        $conditions = [];
        $params = [];
        $types = "";

        if (!empty($filters['type'])) {
            $placeholders = implode(',', array_fill(0, count($filters['type']), '?'));
            $conditions[] = "animal_type IN ($placeholders)";
            $params = array_merge($params, $filters['type']);
            $types .= str_repeat('s', count($filters['type']));
        }

        if (!empty($filters['breed'])) {
            $conditions[] = "breed = ?";
            $params[] = $filters['breed'];
            $types .= 's';
        }

        if (!empty($filters['color'])) {
            $conditions[] = "color = ?";
            $params[] = $filters['color'];
            $types .= 's';
        }

        if (!empty($filters['gender'])) {
            $conditions[] = "gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }

        if (!empty($filters['size'])) {
            $conditions[] = "size = ?";
            $params[] = $filters['size'];
            $types .= 's';
        }

        if (!empty($filters['age'])) {
            $conditions[] = "age <= ?";
            $params[] = $filters['age'];
            $types .= 'i';
        }

        if (!empty($filters['country'])) {
            $conditions[] = "users.country = ?";
            $params[] = $filters['country'];
            $types .= 's';
        }

        if (!empty($filters['county'])) {
            $conditions[] = "users.county = ?";
            $params[] = $filters['county'];
            $types .= 's';
        }

        error_log(print_r($filters, true));


        $sql = "
    SELECT 
        pets.*, 
        users.country AS owner_country, 
        users.county AS owner_county 
    FROM pets 
    JOIN users ON pets.user_id = users.id
";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $pets = $result->fetch_all(MYSQLI_ASSOC);

        return $pets;
    }
}
