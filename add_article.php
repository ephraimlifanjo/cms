<?php session_start();
if (!isset($_SESSION["admin"])) { header("Location: login.php"); exit; }
include 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $image = '';
    if ($_FILES["image"]["name"]) {
        $image = basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], "uploads/" . $image);
    }
    $stmt = $conn->prepare("INSERT INTO articles (title, content, image) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $image);
    $stmt->execute();
    header("Location: admin.php");
}
?>
<form method="POST" enctype="multipart/form-data">
  <input name="title" placeholder="Titre"><br>
  <textarea name="content" placeholder="Contenu"></textarea><br>
  <input type="file" name="image"><br>
  <button type="submit">Ajouter</button>
</form>