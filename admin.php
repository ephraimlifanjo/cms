<?php session_start();
if (!isset($_SESSION["admin"])) { header("Location: login.php"); exit; }
include 'db.php';
echo '<a href="add_article.php">Ajouter un article</a><hr>';
$result = $conn->query("SELECT * FROM articles ORDER BY created_at DESC");
while($row = $result->fetch_assoc()) {
    echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
    echo '<a href="edit_article.php?id=' . $row['id'] . '">Modifier</a> | ';
    echo '<a href="delete_article.php?id=' . $row['id'] . '">Supprimer</a><hr>';
}
?>