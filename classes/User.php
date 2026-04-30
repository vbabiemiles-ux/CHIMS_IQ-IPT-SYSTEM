<?php

class User {

    private $conn;
    private $table = "users";

    public $id;
    public $name;
    public $email;

    public function __construct($db) {
        $this->conn = $db;
    }

    # CREATE
    public function create() {

        try {
            $query = "INSERT INTO " . $this->table . " (name, email)
                    VALUES (:name, :email)";

            $stmt = $this->conn->prepare($query);

            $stmt->execute([
                ":name" => $this->name,
                ":email" => $this->email,
            ]);

            return true;

        } catch(Throwable $e){
           return $e->getMessage();
        }
    }

    # READ ALL
    public function read() {

        try {
            $query = "SELECT * FROM " . $this->table . " WHERE deleted_at IS NULL ORDER BY id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);   
        } catch(Throwable $e){
           return $e->getMessage();
        }
    }

    # READ ONE
    public function readOne() {

        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";

            $stmt = $this->conn->prepare($query);

            $id = decryptId($this->id);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(Throwable $e){
            return $e->getMessage();
        }
    }

    # UPDATE
    public function update() {

        try {
            $query0 = "SELECT COUNT(*) FROM " . $this->table . " WHERE email = :email";
            $stmt0 = $this->conn->prepare($query0);
            $stmt0->execute([":email" => $this->email]);
            $emailExist = $stmt0->fetchColumn();
            /* 
            echo '<pre>';
            var_dump($emailExist);
            exit; */
            if($emailExist > 0){
                return [
                    "status" => false,
                    "message" => "Email Already Exist!"
                ];
            }

            $query = "UPDATE " . $this->table . "
                    SET name = :name, email = :email
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->id);

            $stmt->execute();
            
            return [
                "status" => $stmt->rowCount() > 0,
                "message" => "User updated successfully"
            ];

        } catch(Throwable $e){
            return $e->getMessage();
        }
    }

    # DELETE
    public function delete() {
        try {
            $query = "UPDATE " . $this->table . " SET deleted_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $decrypted_id = decryptId($this->id);
            $stmt->bindParam(":id", $decrypted_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return [
                    "status" => true,
                    "message" => "User deleted successfully!"
                ];
            }

            return [
                "status" => false,
                "message" => "No user found to delete!"
            ];

        } catch (\Throwable $th) {
            return [
                "status" => false,
                "message" => $th->getMessage()
            ];
        }
    }
}