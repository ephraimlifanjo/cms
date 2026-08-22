<?php
require __DIR__ . '/bootstrap.php';

if (current_user()) {
    redirect('admin.php');
}

$csrf = auth_ready() ? csrf_token() : '';
$error = '';
$values = [
    'name' => '',
    'email' => '',
    'site_name' => '',
    'site_slug' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_ready() || is_read_only_demo()) {
        $error = 'La création de compte nécessite APP_KEY et une base persistante en production.';
    } else {
        verify_csrf();

        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
        $values['site_name'] = trim((string) ($_POST['site_name'] ?? ''));
        $values['site_slug'] = slugify((string) ($_POST['site_slug'] ?? $values['site_name']));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($values['name'] === '' || $values['site_name'] === '') {
            $error = 'Nom et nom du site requis.';
        } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse e-mail invalide.';
        } elseif (strlen($password) < 10) {
            $error = 'Utilisez au moins 10 caractères pour le mot de passe.';
        } elseif ($password !== $confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $pdo = db();
            $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $exists->execute([$values['email']]);

            if ($exists->fetch()) {
                $error = 'Un compte utilise déjà cette adresse e-mail.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $now = now_iso();
                    $ownerEmail = strtolower((string) env('PLATFORM_OWNER_EMAIL', ''));
                    $role = ($ownerEmail !== '' && hash_equals($ownerEmail, $values['email']))
                        ? 'admin'
                        : 'creator';

                    $userStatement = $pdo->prepare(
                        'INSERT INTO users (email, display_name, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $userStatement->execute([
                        $values['email'],
                        $values['name'],
                        password_hash($password, PASSWORD_DEFAULT),
                        $role,
                        $now,
                        $now,
                    ]);
                    $userId = last_inserted_id($pdo, 'users');

                    $siteSlug = unique_site_slug($pdo, $values['site_slug']);
                    $siteStatement = $pdo->prepare(
                        'INSERT INTO sites (owner_id, name, slug, tagline, bio, avatar, cover_image, accent, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $siteStatement->execute([
                        $userId,
                        $values['site_name'],
                        $siteSlug,
                        'Mon espace, mes idées.',
                        'Bienvenue sur mon espace Nova CMS.',
                        'assets/images/avatar-default.svg',
                        'assets/images/cover-creative.svg',
                        '#6d5dfc',
                        $now,
                        $now,
                    ]);
                    $siteId = last_inserted_id($pdo, 'sites');

                    $articleStatement = $pdo->prepare(
                        'INSERT INTO articles (site_id, author_id, title, slug, excerpt, content, category, tags, image, status, featured, seo_title, seo_description, created_at, updated_at, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $articleStatement->execute([
                        $siteId,
                        $userId,
                        'Bienvenue sur mon espace',
                        'bienvenue',
                        'Mon premier article avec Nova CMS.',
                        'Ceci est votre premier article. Modifiez-le depuis le dashboard, ajoutez une image et commencez à partager votre travail.',
                        'Journal',
                        'welcome',
                        'assets/images/blog-default.svg',
                        'draft',
                        0,
                        '',
                        '',
                        $now,
                        $now,
                        null,
                    ]);

                    $pdo->commit();
                    login_user(['id' => $userId, 'email' => $values['email']]);
                    redirect('admin.php?welcome=1');
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Impossible de créer le compte. Essayez un autre identifiant de site.';
                }
            }
        }
    }
}

render_header('Créer mon espace', 'Créez gratuitement votre mini-blog Nova CMS.', 'auth-page');
render_nav();
?>
<main class="auth-layout section-shell">
    <section class="auth-promo">
        <img src="<?= e(app_url('assets/images/cover-creative.svg')) ?>" alt="Illustration Nova CMS">
        <span class="eyebrow-text">VOTRE ESPACE EN 60 SECONDES</span>
        <h1>Un compte.<br>Votre blog.<br><em>Votre identité.</em></h1>
        <p>Pas de mot de passe admin partagé. Chaque créateur possède son propre compte et son propre site.</p>
    </section>

    <section class="auth-card">
        <div class="auth-card-head">
            <span class="step-badge">Nouveau créateur</span>
            <h2>Créer mon compte</h2>
            <p>Le mot de passe est hashé côté serveur et n’est jamais stocké en clair.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!auth_ready() || is_read_only_demo()): ?>
            <div class="alert alert-info">
                <strong>Mode démo public.</strong> L’inscription sera activée dès qu’une base persistante + <code>APP_KEY</code> seront configurées.
            </div>
        <?php endif; ?>

        <form method="post" class="form-stack">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="field-grid">
                <label>
                    Votre nom
                    <input name="name" value="<?= e($values['name']) ?>" autocomplete="name" required placeholder="Ex. Alex N.">
                </label>
                <label>
                    E-mail
                    <input type="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required placeholder="vous@example.com">
                </label>
            </div>

            <label>
                Nom du blog
                <input name="site_name" value="<?= e($values['site_name']) ?>" required placeholder="Ex. Alex Builds">
            </label>

            <label>
                Identifiant public
                <div class="slug-input">
                    <span>/site/</span>
                    <input name="site_slug" value="<?= e($values['site_slug']) ?>" placeholder="alex-builds">
                </div>
            </label>

            <div class="field-grid">
                <label>
                    Mot de passe
                    <input type="password" name="password" minlength="10" autocomplete="new-password" required placeholder="10 caractères minimum">
                </label>
                <label>
                    Confirmer
                    <input type="password" name="password_confirm" minlength="10" autocomplete="new-password" required>
                </label>
            </div>

            <button class="btn btn-primary btn-full" <?= (!auth_ready() || is_read_only_demo()) ? 'disabled' : '' ?>>
                Créer mon espace <span>→</span>
            </button>
        </form>

        <p class="auth-switch">Déjà un compte ? <a href="<?= e(app_url('login.php')) ?>">Se connecter</a></p>
    </section>
</main>
<?php render_footer(); ?>
