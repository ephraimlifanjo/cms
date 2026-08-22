<?php
require __DIR__.'/bootstrap.php'; require_admin();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Méthode non autorisée.');}
verify_csrf(); $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); if(!$id){flash('error','Article invalide.');redirect('admin.php');}
$s=db()->prepare('DELETE FROM articles WHERE id=?'); $s->execute([(int)$id]); flash('success','Article supprimé.'); redirect('admin.php');
