<?php
require __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
try { db()->query('SELECT 1'); echo json_encode(['ok'=>true,'version'=>CMS_VERSION,'database'=>'ready'], JSON_UNESCAPED_SLASHES); }
catch (Throwable $e) { http_response_code(503); echo json_encode(['ok'=>false,'version'=>CMS_VERSION,'database'=>'unavailable'], JSON_UNESCAPED_SLASHES); }
