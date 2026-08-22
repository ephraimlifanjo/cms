<?php
require __DIR__.'/bootstrap.php';
if (is_admin()) redirect('admin.php');
$error='';
$isLocalDemo = !env('VERCEL_ENV') && PHP_SAPI === 'cli-server';
$effectivePassword = $isLocalDemo ? '1234' : admin_password();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    if (login_rate_limited()) $error='Trop de tentatives. Réessayez dans quelques minutes.';
    else {
        if (!$effectivePassword) $error='Connexion admin désactivée : configurez CMS_ADMIN_PASSWORD.';
        elseif (hash_equals(admin_username(), (string)($_POST['username']??'')) && hash_equals($effectivePassword,(string)($_POST['password']??''))) {
            session_regenerate_id(true); $_SESSION['admin_authenticated']=true; clear_login_failures(); redirect('admin.php');
        } else { record_login_failure(); $error='Identifiants invalides.'; }
    }
}
render_header('Connexion','',true);
?><main class="auth-page"><section class="auth-card"><a class="brand" href="<?=e(app_url('index.php'))?>"><span class="brand-mark">N</span><span>Nova CMS</span></a><div class="auth-copy"><span class="eyebrow">Espace sécurisé</span><h1>Bon retour.</h1><p>Connectez-vous pour gérer vos publications.</p></div><?php if($isLocalDemo):?><div class="alert demo"><strong>Compte démo local</strong><span>Identifiant : <code>admin</code> · Mot de passe : <code>1234</code></span></div><?php endif;?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?><form method="post" class="form-stack"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><label>Identifiant<input name="username" autocomplete="username" required value="<?=e(admin_username())?>"></label><label>Mot de passe<input type="password" name="password" autocomplete="current-password" required></label><button class="btn primary" type="submit">Se connecter</button></form><a class="back-link" href="<?=e(app_url('index.php'))?>">← Retour au site</a></section></main><?php render_footer();
