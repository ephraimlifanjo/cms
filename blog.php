<?php include 'db.php';
$result = $conn->query("SELECT * FROM articles ORDER BY created_at DESC");
while($row = $result->fetch_assoc()) {
    echo '<h2>' . htmlspecialchars($row['title']) . '</h2>';
    echo '<p>' . nl2br(htmlspecialchars($row['content'])) . '</p>';
    if ($row['image']) {
        echo '<img src="uploads/' . htmlspecialchars($row['image']) . '" style="max-width:200px;">';
    }
    echo '<hr>';
}
?>