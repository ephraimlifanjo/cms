<?php
$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$routes = [
    '/' => 'index.php', '/index.php' => 'index.php', '/blog.php' => 'blog.php',
    '/login.php' => 'login.php', '/logout.php' => 'logout.php', '/admin.php' => 'admin.php',
    '/add_article.php' => 'add_article.php', '/edit_article.php' => 'edit_article.php', '/delete_article.php' => 'delete_article.php',
    '/health' => 'health.php', '/health.php' => 'health.php',
];
$file = $routes[$path] ?? null;
if ($file) { require $root . '/' . $file; return; }
http_response_code(404); require $root . '/bootstrap.php'; render_header('404'); echo '<main class="shell"><div class="empty"><h1>Page introuvable</h1><a class="btn" href="'.e(app_url('index.php')).'">Accueil</a></div></main>'; render_footer();
