<?php

declare(strict_types=1);

namespace App;


use App\Exception\StorageException;
use App\Exception\ConfigurationException;
use App\Exception\NotFoundException;
use Exception;
use PDO;
use PDOException;
use Throwable;

class database
{
    private PDO $conn;

    public function __construct(array $config)
    {
        try {
            $this->validateConfig($config);
            $this->createConnection($config);
        } catch (PDOException $e) {
            throw new StorageException('Błąd w połączeniu z bazą danych');
        }
    }

    public function getNote(int $id): array
    {
        try {
            $query = "SELECT * FROM notes WHERE id=$id";
            $result = $this->conn->query($query);
            $note = $result->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            throw new NotFoundException("notatka o id: $id nie istnieje");
        }

        return $note;
    }

    public function getNotes(): array
    {
        try {
            $notes = [];

            $query = "SELECT id, title, created FROM notes";

            $result = $this->conn->query($query);
            return $result->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            throw new StorageException('Nie udało sie pobrać danych o notatkach', 400, $e);
        }
    }

    public function createNote(array $data): void
    {
        try {
            $title = $this->conn->quote($data['title']);
            $description = $this->conn->quote($data['description']);
            $created = date('Y-m-d H:i:s');
            $query = "INSERT INTO notes(title,description,created) VALUES($title,$description,'$created')";
            $result = $this->conn->exec($query);
        } catch (Throwable $e) {
            throw new StorageException('Nie udało się utworzyc nowej notatki', 400, $e);
        }
    }

    public function editNote(int $id, array $data)
    {
        try {
            $title = $this->conn->quote($data['title']);
            $description = $this->conn->quote($data['description']);

            $query = "UPDATE notes SET title=$title, description=$description where id=$id";

            $this->conn->exec($query);
        } catch (Throwable $e) {
            throw new StorageException('Nie udało się odytować notatki', 400, $e);
        }
    }

    private function createConnection(array $config): void
    {
        $dsn = "mysql:dbname={$config['database']};
    host={$config['host']}";
        $this->conn = new PDO(
            $dsn,
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    public function deleteNote($id): void
    {
        try {
            $query = "DELETE FROM notes WHERE id=$id LIMIT 1";
            $this->conn->exec($query);
        } catch (Throwable $e) {
            throw new StorageException("Nie udało się usunac notatki", 400, $e);
        }
    }

    private function validateConfig(array $config): void
    {
        if (empty($config['database']) || empty($config['user']) || empty($config['host'])) {
            throw new ConfigurationException('Problem z konfiguracją baz danych. Skontaktuj się z administratorem');
        }
    }
}
