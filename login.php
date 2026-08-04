<?php session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["username"] === "admin" && $_POST["password"] === "1234") {
        $_SESSION["admin"] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Identifiants invalides";
    }
} ?>
<form method="POST">
  <input name="username" placeholder="Username">
  <input name="password" type="password" placeholder="Password">
  <button type="submit">Login</button>
</form>
<?php if (!empty($error)) echo $error; ?>