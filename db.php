<?php
// Backward-compatible entry point. New code uses bootstrap.php + db().
require_once __DIR__ . '/bootstrap.php';
$conn = db();
