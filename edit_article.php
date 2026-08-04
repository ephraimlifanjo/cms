<?php session_start();
if (!isset($_SESSION["admin"])) { header("Location: login.php"); exit; }
include 'db.php';
$id = $_GET["id"];
$result = $conn->query("SELECT * FROM articles WHERE id=$id");
$article = $result->fetch_assoc();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $stmt = $conn->prepare("UPDATE articles SET title=?, content=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $content, $id);
    $stmt->execute();
    header("Location: admin.php");
}
?>
<form method="POST">
  <input name="title" value="<?= htmlspecialchars($article['title']) ?>"><br>
  <textarea name="content"><?= htmlspecialchars($article['content']) ?></textarea><br>
  <button type="submit">Mettre à jour</button>
</form>