<?php
// Подключение к MySQL
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'todo_db';

// Создаем соединение
$conn = new mysqli($host, $user, $password, $dbname);

// Проверка соединения
if ($conn->connect_errno) {
    die("Ошибка соединения: " . $conn->connect_error);
}

// Создание таблицы, если её нет
$sql_create_table = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    completed TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$conn->query($sql_create_table);

// Обработка POST-запроса для добавления задачи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
    $title = $_POST['title'];
    $stmt = $conn->prepare("INSERT INTO tasks (title) VALUES (?)");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $stmt->close();
}

// Удаление или обновление задачи по GET-запросу
if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_GET['action'] === 'complete') {
        $stmt = $conn->prepare("UPDATE tasks SET completed = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Фильтрация задач на основе GET-параметра
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT id, title, completed FROM tasks" . ($filter !== 'all' ? " WHERE completed = " . ($filter === 'completed' ? '1' : '0') : '');
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список задач</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 400px; margin-bottom: 20px; }
        td, th { border: 1px solid #ccc; padding: 8px; }
        .completed { text-decoration: line-through; color: green; }
    </style>
</head>
<body>
<h1>Список задач</h1>
<form method="post">
    <label for="title">Новая задача:</label>
    <input type="text" name="title" id="title" required>
    <button type="submit">Добавить</button>
</form>
<div>
    <strong>Фильтрация:</strong>
    <a href="?filter=all">Все</a> |
    <a href="?filter=completed">Выполненные</a> |
    <a href="?filter=not_completed">Невыполненные</a>
</div>
<table>
    <tr>
        <th>ID</th>
        <th>Задача</th>
        <th>Статус</th>
        <th>Действия</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td class="<?= $row['completed'] ? 'completed' : ''; ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES); ?></td>
            <td><?= $row['completed'] ? 'Выполнено' : 'Не выполнено'; ?></td>
            <td>
                <a href="?action=delete&id=<?= $row['id']; ?>" onclick="return confirm('Вы уверены?');">Удалить</a>
                <?php if (!$row['completed']): ?>
                    | <a href="?action=complete&id=<?= $row['id']; ?>">Отметить выполненной</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
</body>
</html>