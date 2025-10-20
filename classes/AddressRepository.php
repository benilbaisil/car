<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Address.php';

/**
 * AddressRepository class - handles database operations for addresses
 */
class AddressRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Save address to database
     */
    public function save(Address $address): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO addresses (user_id, name, street_address, city, state, zip_code, phone_number, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $ok = $stmt->execute([
                $address->getUserId(),
                $address->getName(),
                $address->getStreetAddress(),
                $address->getCity(),
                $address->getState(),
                $address->getZipCode(),
                $address->getPhoneNumber(),
                $address->getCreatedAt()
            ]);
            if ($ok) {
                $lastId = (int)$this->pdo->lastInsertId();
                if ($lastId > 0) {
                    $address->setId($lastId);
                }
            }
            return $ok;
        } catch (PDOException $e) {
            // If table is missing, create it and retry once
            if ($this->isTableMissingError($e)) {
                $this->createAddressesTableIfNotExists();
                try {
                    $stmt = $this->pdo->prepare('
                        INSERT INTO addresses (user_id, name, street_address, city, state, zip_code, phone_number, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $ok = $stmt->execute([
                        $address->getUserId(),
                        $address->getName(),
                        $address->getStreetAddress(),
                        $address->getCity(),
                        $address->getState(),
                        $address->getZipCode(),
                        $address->getPhoneNumber(),
                        $address->getCreatedAt()
                    ]);
                    if ($ok) {
                        $lastId = (int)$this->pdo->lastInsertId();
                        if ($lastId > 0) {
                            $address->setId($lastId);
                        }
                    }
                    return $ok;
                } catch (PDOException $e2) {
                    error_log('Error saving address after creating table: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log("Error saving address: " . $e->getMessage());
            return false;
        }
    }

    private function isTableMissingError(PDOException $e): bool
    {
        // MariaDB/MySQL will often use SQLSTATE[42S02] for table not found
        $code = $e->getCode();
        $msg = $e->getMessage();
        if ($code === '42S02') {
            return true;
        }
        // Fallback heuristic
        return stripos($msg, 'Base table or view not found') !== false
            || stripos($msg, 'doesn\'t exist') !== false
            || stripos($msg, 'no such table') !== false;
    }

    private function createAddressesTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            street_address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            zip_code VARCHAR(20) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log('Error creating addresses table: ' . $e->getMessage());
        }
    }

    /**
     * Get address by ID
     */
    public function getById(int $id): ?Address
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, user_id, name, street_address, city, state, zip_code, phone_number, created_at 
                FROM addresses WHERE id = ?
            ');
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            return new Address(
                (int)$data['user_id'],
                $data['name'],
                $data['street_address'],
                $data['city'],
                $data['state'],
                $data['zip_code'],
                $data['phone_number'],
                (int)$data['id'],
                $data['created_at']
            );
        } catch (PDOException $e) {
            error_log("Error fetching address: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user's latest address
     */
    public function getLatestByUserId(int $userId): ?Address
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, user_id, name, street_address, city, state, zip_code, phone_number, created_at 
                FROM addresses 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ');
            $stmt->execute([$userId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            return new Address(
                (int)$data['user_id'],
                $data['name'],
                $data['street_address'],
                $data['city'],
                $data['state'],
                $data['zip_code'],
                $data['phone_number'],
                (int)$data['id'],
                $data['created_at']
            );
        } catch (PDOException $e) {
            error_log("Error fetching latest address: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all addresses for a user
     */
    public function getAllByUserId(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, user_id, name, street_address, city, state, zip_code, phone_number, created_at 
                FROM addresses 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ');
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $addresses = [];
            foreach ($results as $data) {
                $addresses[] = new Address(
                    (int)$data['user_id'],
                    $data['name'],
                    $data['street_address'],
                    $data['city'],
                    $data['state'],
                    $data['zip_code'],
                    $data['phone_number'],
                    (int)$data['id'],
                    $data['created_at']
                );
            }

            return $addresses;
        } catch (PDOException $e) {
            error_log("Error fetching user addresses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update address
     */
    public function update(Address $address): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE addresses 
                SET name = ?, street_address = ?, city = ?, state = ?, zip_code = ?, phone_number = ?
                WHERE id = ? AND user_id = ?
            ');
            
            return $stmt->execute([
                $address->getName(),
                $address->getStreetAddress(),
                $address->getCity(),
                $address->getState(),
                $address->getZipCode(),
                $address->getPhoneNumber(),
                $address->getId(),
                $address->getUserId()
            ]);
        } catch (PDOException $e) {
            error_log("Error updating address: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete address
     */
    public function delete(int $addressId, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
            return $stmt->execute([$addressId, $userId]);
        } catch (PDOException $e) {
            error_log("Error deleting address: " . $e->getMessage());
            return false;
        }
    }
}
