<?php
require __DIR__ . '/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
verify_csrf();logout_user();redirect('login.php');
