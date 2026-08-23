<?php
require __DIR__ . '/bootstrap.php';

$requestedSlug = trim((string) env('CMS_PUBLIC_SITE_SLUG', ''));
$site = $requestedSlug !== '' ? site_by_slug($requestedSlug) : null;
if (!$site) {
    $available = public_sites(1);
    $site = $available[0] ?? null;
}

if (!$site) {
    $site = [
        'id' => 0,
        'name' => env('CMS_SITE_NAME', 'Nova Showcase') ?? 'Nova Showcase',
        'slug' => 'showcase',
        'tagline' => 'Designer · Developer · Builder',
        'bio' => 'A personal corner of the web for selected work, experiments and writing.',
        'avatar' => 'assets/images/avatar-default.svg',
        'cover_image' => 'assets/images/cover-code.svg',
        'accent' => '#5d5bf7',
    ];
}

$articles = (int) $site['id'] > 0 ? site_articles((int) $site['id'], true) : [];
$featured = array_values(array_filter($articles, fn(array $article): bool => (int)($article['featured'] ?? 0) === 1));
if (!$featured) $featured = array_slice($articles, 0, 3);
$journal = array_slice($articles, 0, 6);
$accent = valid_accent((string)($site['accent'] ?? '#5d5bf7'));

render_header($site['name'], $site['tagline'] ?: $site['bio'], 'showcase-body');
?>
<link rel="stylesheet" href="<?= e(app_url('assets/showcase.css')) ?>">
<div class="showcase" style="--accent:<?= e($accent) ?>">
  <header class="showcase-nav shell">
    <a class="showcase-identity" href="<?= e(app_url()) ?>">
      <img src="<?= e(safe_asset_url((string)$site['avatar'], 'assets/images/avatar-default.svg')) ?>" alt="">
      <span><?= e($site['name']) ?></span>
    </a>
    <nav aria-label="Navigation principale">
      <a href="#work">Work</a>
      <a href="#journal">Journal</a>
      <a href="#about">About</a>
      <a href="https://github.com/ephraimlifanjo/cms" rel="noreferrer">Source ↗</a>
    </nav>
    <a class="admin-entry" href="<?= e(app_url('login.php')) ?>" aria-label="Administration">Admin</a>
  </header>

  <main>
    <section class="showcase-hero shell">
      <div class="hero-status"><span></span> PERSONAL SHOWCASE · BUILT WITH NOVA CMS</div>
      <div class="hero-grid">
        <div class="hero-title-wrap">
          <h1><?= e($site['tagline'] ?: 'Ideas, work & things worth sharing.') ?></h1>
          <p><?= e($site['bio']) ?></p>
          <div class="hero-cta">
            <a href="#work" class="primary-action">Explore selected work <span>↘</span></a>
            <a href="#journal" class="quiet-action">Read the journal</a>
          </div>
        </div>
        <figure class="hero-visual">
          <img src="<?= e(safe_asset_url((string)$site['cover_image'], 'assets/images/cover-code.svg')) ?>" alt="<?= e($site['name']) ?>">
          <figcaption><span>Currently showing</span><strong><?= count($articles) ?> published piece<?= count($articles) === 1 ? '' : 's' ?></strong></figcaption>
        </figure>
      </div>
      <div class="hero-marquee" aria-hidden="true"><span>PROJECTS</span><i>◆</i><span>ARTICLES</span><i>◆</i><span>NOTES</span><i>◆</i><span>EXPERIMENTS</span><i>◆</i><span>IDEAS</span></div>
    </section>

    <section id="work" class="showcase-section shell">
      <header class="section-intro">
        <div><span class="section-index">01</span><h2>Selected work</h2></div>
        <p>A focused selection of things worth putting in front of people — projects, launches, case studies and experiments.</p>
      </header>

      <?php if ($featured): ?>
      <div class="work-stack">
        <?php foreach (array_slice($featured, 0, 3) as $index => $article): ?>
          <article class="work-card <?= $index === 0 ? 'work-card-large' : '' ?>">
            <a class="work-media" href="<?= e(app_url('article.php?site=' . rawurlencode((string)$site['slug']) . '&slug=' . rawurlencode((string)$article['slug']))) ?>">
              <img src="<?= e(safe_asset_url((string)$article['image'])) ?>" alt="">
              <span class="work-number">0<?= $index + 1 ?></span>
            </a>
            <div class="work-copy">
              <div class="work-meta"><span><?= e($article['category']) ?></span><time><?= e(substr((string)($article['published_at'] ?: $article['updated_at']), 0, 4)) ?></time></div>
              <h3><a href="<?= e(app_url('article.php?site=' . rawurlencode((string)$site['slug']) . '&slug=' . rawurlencode((string)$article['slug']))) ?>"><?= e($article['title']) ?></a></h3>
              <p><?= e($article['excerpt'] ?: excerpt((string)$article['content'], 190)) ?></p>
              <a class="project-link" href="<?= e(app_url('article.php?site=' . rawurlencode((string)$site['slug']) . '&slug=' . rawurlencode((string)$article['slug']))) ?>">View story <span>↗</span></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
        <div class="showcase-empty">
          <img src="<?= e(app_url('assets/images/hero-studio.svg')) ?>" alt="">
          <div><span class="section-index">YOUR FIRST BLOCK</span><h3>This area is ready for your best work.</h3><p>Publish an article from the admin and mark it as featured. It will become a showcase card here automatically.</p><a href="<?= e(app_url('login.php')) ?>">Open admin →</a></div>
        </div>
      <?php endif; ?>
    </section>

    <section class="manifesto-band">
      <div class="shell manifesto-inner">
        <span>Not another social profile.</span>
        <p>A small, intentional website where your work has room to breathe.</p>
      </div>
    </section>

    <section id="journal" class="showcase-section shell journal-section">
      <header class="section-intro">
        <div><span class="section-index">02</span><h2>Journal</h2></div>
        <p>Notes, observations, tutorials and updates. The CMS part stays simple; the public reading experience stays beautiful.</p>
      </header>
      <div class="journal-list">
        <?php if ($journal): foreach ($journal as $article): ?>
          <a class="journal-row" href="<?= e(app_url('article.php?site=' . rawurlencode((string)$site['slug']) . '&slug=' . rawurlencode((string)$article['slug']))) ?>">
            <span class="journal-date"><?= e(substr((string)($article['published_at'] ?: $article['updated_at']), 0, 10)) ?></span>
            <span class="journal-title"><?= e($article['title']) ?></span>
            <span class="journal-category"><?= e($article['category']) ?></span>
            <span class="journal-arrow">↗</span>
          </a>
        <?php endforeach; else: ?>
          <div class="journal-placeholder">No public notes yet. The showcase is ready for your first article.</div>
        <?php endif; ?>
      </div>
    </section>

    <section id="about" class="about-showcase shell">
      <div class="about-photo"><img src="<?= e(safe_asset_url((string)$site['avatar'], 'assets/images/avatar-default.svg')) ?>" alt="<?= e($site['name']) ?>"></div>
      <div class="about-copy">
        <span class="section-index">03 · ABOUT</span>
        <h2>A personal website first.<br>A CMS second.</h2>
        <p><?= e($site['bio']) ?></p>
        <p class="about-secondary">Nova CMS handles publishing, drafts, images and editing behind the scenes. Visitors only see a polished showcase of the person and their work.</p>
        <div class="about-links"><a href="#work">Selected work</a><a href="#journal">Writing</a><a href="https://github.com/ephraimlifanjo/cms">Fork this CMS ↗</a></div>
      </div>
    </section>
  </main>

  <footer class="showcase-footer shell">
    <div><strong><?= e($site['name']) ?></strong><span><?= e($site['tagline']) ?></span></div>
    <div><span>Personal showcase</span><span>Powered quietly by <a href="https://github.com/ephraimlifanjo/cms">Nova CMS</a></span></div>
  </footer>
</div>
<script src="<?= e(app_url('assets/app.js')) ?>" defer></script>
</body></html>
