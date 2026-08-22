<?php
declare(strict_types=1);

const CMS_VERSION = '1.1.0';
const AUTH_COOKIE = 'nova_auth';
const CSRF_COOKIE = 'nova_csrf';

function load_dotenv(): void {
    $path = __DIR__ . '/.env';
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) continue;
        $value = trim($value, "\"'");
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
load_dotenv();

function env(string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') return $default;
    return (string) $value;
}
function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}
function app_url(string $path = ''): string {
    $base = rtrim((string) env('APP_URL', ''), '/');
    if ($base === '') {
        $scheme = is_https() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $base = $scheme . '://' . $host;
    }
    return $base . '/' . ltrim($path, '/');
}
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . app_url($path)); exit; }
function now_iso(): string { return gmdate('c'); }
function is_vercel(): bool { return env('VERCEL_ENV') !== null; }
function persistent_database_configured(): bool { return env('DATABASE_URL') !== null || !is_vercel(); }
function is_read_only_demo(): bool { return is_vercel() && env('DATABASE_URL') === null; }

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

function app_key(): ?string {
    $configured = env('APP_KEY');
    if ($configured && strlen($configured) >= 32) return $configured;
    if (is_vercel()) return null;
    $file = __DIR__ . '/storage/app.key';
    if (is_file($file)) return trim((string) file_get_contents($file));
    if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
    $key = bin2hex(random_bytes(32));
    @file_put_contents($file, $key, LOCK_EX);
    return $key;
}
function auth_ready(): bool { return app_key() !== null && persistent_database_configured(); }
function base64url_encode(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
function base64url_decode(string $data): string|false { $pad = strlen($data) % 4; if ($pad) $data .= str_repeat('=', 4 - $pad); return base64_decode(strtr($data, '-_', '+/'), true); }
function sign_value(string $value): string { $key = app_key(); if (!$key) throw new RuntimeException('APP_KEY manquante.'); return hash_hmac('sha256', $value, $key); }
function set_secure_cookie(string $name, string $value, int $expires): void { setcookie($name, $value, ['expires'=>$expires,'path'=>'/','secure'=>is_https(),'httponly'=>true,'samesite'=>'Lax']); }
function login_user(array $user): void {
    $payload = base64url_encode(json_encode(['id'=>(int)$user['id'],'email'=>(string)$user['email'],'exp'=>time()+60*60*24*30], JSON_UNESCAPED_SLASHES));
    set_secure_cookie(AUTH_COOKIE, $payload . '.' . sign_value($payload), time()+60*60*24*30);
}
function logout_user(): void { set_secure_cookie(AUTH_COOKIE, '', time()-3600); }
function current_user(): ?array {
    static $loaded=false, $user=null; if($loaded)return $user; $loaded=true;
    if(!auth_ready())return null; $cookie=(string)($_COOKIE[AUTH_COOKIE]??''); if(!str_contains($cookie,'.'))return null;
    [$payload,$signature]=explode('.',$cookie,2); if(!hash_equals(sign_value($payload),$signature))return null;
    $json=base64url_decode($payload); $data=$json?json_decode($json,true):null;
    if(!is_array($data)||(int)($data['exp']??0)<time()||empty($data['id']))return null;
    $stmt=db()->prepare('SELECT id,email,display_name,role,created_at FROM users WHERE id = ?'); $stmt->execute([(int)$data['id']]); $user=$stmt->fetch()?:null; return $user;
}
function require_user(): array { $user=current_user(); if(!$user)redirect('login.php'); return $user; }
function is_platform_admin(?array $user=null): bool { $user??=current_user(); return ($user['role']??'')==='admin'; }

function csrf_token(): string {
    $key=app_key(); if(!$key)return ''; $seed=(string)($_COOKIE[CSRF_COOKIE]??'');
    if(!preg_match('/^[a-f0-9]{64}$/',$seed)){ $seed=bin2hex(random_bytes(32)); set_secure_cookie(CSRF_COOKIE,$seed,time()+60*60*24*7); $_COOKIE[CSRF_COOKIE]=$seed; }
    return hash_hmac('sha256','csrf|'.$seed,$key);
}
function verify_csrf(): void {
    $seed=(string)($_COOKIE[CSRF_COOKIE]??''); $token=(string)($_POST['_csrf']??''); $key=app_key();
    if(!$key||!preg_match('/^[a-f0-9]{64}$/',$seed)||!hash_equals(hash_hmac('sha256','csrf|'.$seed,$key),$token)){ http_response_code(419); exit('Session expirée. Rechargez la page et réessayez.'); }
}

function db(): PDO {
    static $pdo; if($pdo instanceof PDO)return $pdo; $url=env('DATABASE_URL');
    if($url){
        $parts=parse_url($url); if(!$parts||empty($parts['scheme'])||empty($parts['host']))throw new RuntimeException('DATABASE_URL invalide.');
        $scheme=strtolower((string)$parts['scheme']); $name=ltrim((string)($parts['path']??''),'/'); $user=rawurldecode((string)($parts['user']??'')); $pass=rawurldecode((string)($parts['pass']??'')); $port=isset($parts['port'])?';port='.(int)$parts['port']:'';
        if(in_array($scheme,['postgres','postgresql'],true))$dsn="pgsql:host={$parts['host']}{$port};dbname={$name};sslmode=require";
        elseif($scheme==='mysql')$dsn="mysql:host={$parts['host']}{$port};dbname={$name};charset=utf8mb4";
        else throw new RuntimeException('DATABASE_URL doit utiliser mysql:// ou postgresql://.');
        $pdo=new PDO($dsn,$user,$pass);
    } else {
        $file=is_vercel()?'/tmp/nova-cms.sqlite':(env('SQLITE_PATH',__DIR__.'/storage/cms.sqlite')??__DIR__.'/storage/cms.sqlite'); if(!is_dir(dirname($file)))@mkdir(dirname($file),0775,true); $pdo=new PDO('sqlite:'.$file);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC); migrate($pdo); return $pdo;
}
function id_sql(string $driver): string { return match($driver){'pgsql'=>'BIGSERIAL PRIMARY KEY','mysql'=>'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY',default=>'INTEGER PRIMARY KEY AUTOINCREMENT'}; }
function bool_sql(string $driver): string { return $driver==='mysql'?'TINYINT(1)':'INTEGER'; }
function text_sql(string $driver): string { return $driver==='mysql'?'LONGTEXT':'TEXT'; }
function last_inserted_id(PDO $pdo,string $table): int { if((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql')return (int)$pdo->query("SELECT currval(pg_get_serial_sequence('{$table}','id'))")->fetchColumn(); return (int)$pdo->lastInsertId(); }
function try_add_column(PDO $pdo,string $table,string $column,string $definition): void { try{$pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");}catch(Throwable){} }
function migrate(PDO $pdo): void {
    static $done=false; if($done)return; $driver=(string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME); $id=id_sql($driver); $bool=bool_sql($driver); $text=text_sql($driver);
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id {$id}, email VARCHAR(190) NOT NULL UNIQUE, display_name VARCHAR(120) NOT NULL, password_hash VARCHAR(255) NOT NULL, role VARCHAR(30) NOT NULL DEFAULT 'creator', created_at VARCHAR(40) NOT NULL, updated_at VARCHAR(40) NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS sites (id {$id}, owner_id BIGINT NOT NULL, name VARCHAR(140) NOT NULL, slug VARCHAR(160) NOT NULL UNIQUE, tagline VARCHAR(220) NOT NULL DEFAULT '', bio {$text} NOT NULL, avatar VARCHAR(500) NOT NULL DEFAULT '', cover_image VARCHAR(500) NOT NULL DEFAULT '', accent VARCHAR(20) NOT NULL DEFAULT '#6d5dfc', created_at VARCHAR(40) NOT NULL, updated_at VARCHAR(40) NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (id {$id}, site_id BIGINT NULL, author_id BIGINT NULL, title VARCHAR(220) NOT NULL, slug VARCHAR(240) NOT NULL, excerpt VARCHAR(500) NOT NULL DEFAULT '', content {$text} NOT NULL, category VARCHAR(80) NOT NULL DEFAULT 'Général', tags VARCHAR(240) NOT NULL DEFAULT '', image VARCHAR(500) NOT NULL DEFAULT '', status VARCHAR(20) NOT NULL DEFAULT 'draft', featured {$bool} NOT NULL DEFAULT 0, seo_title VARCHAR(220) NOT NULL DEFAULT '', seo_description VARCHAR(320) NOT NULL DEFAULT '', created_at VARCHAR(40) NOT NULL, updated_at VARCHAR(40) NOT NULL, published_at VARCHAR(40) NULL)");
    try_add_column($pdo,'articles','site_id','BIGINT NULL'); try_add_column($pdo,'articles','author_id','BIGINT NULL'); try{$pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_articles_site_slug ON articles(site_id, slug)');}catch(Throwable){} $done=true; seed_demo($pdo);
}
function seed_demo(PDO $pdo): void {
    if(env('CMS_SEED_DEMO','1')!=='1')return; if((int)$pdo->query('SELECT COUNT(*) FROM sites')->fetchColumn()>0)return; $now=now_iso();
    $site=$pdo->prepare('INSERT INTO sites (owner_id,name,slug,tagline,bio,avatar,cover_image,accent,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $site->execute([0,'Nova Makers Journal','nova-makers','Build. Share. Grow.','Un blog de démonstration créé avec Nova CMS pour montrer ce qu’un développeur peut publier en quelques minutes.','assets/images/avatar-default.svg','assets/images/cover-code.svg','#6d5dfc',$now,$now]); $siteId=last_inserted_id($pdo,'sites');
    $article=$pdo->prepare('INSERT INTO articles (site_id,author_id,title,slug,excerpt,content,category,tags,image,status,featured,seo_title,seo_description,created_at,updated_at,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $article->execute([$siteId,null,'Construire en public avec Nova CMS','construire-en-public','Un espace simple pour partager projets, notes techniques et parcours.',"Nova CMS est pensé pour les développeurs, créateurs et petites communautés.\n\nCréez votre compte, personnalisez votre mini-site et publiez des articles avec votre propre identité visuelle.",'Développement','php,open-source,community','assets/images/blog-default.svg','published',1,'Construire en public avec Nova CMS','Découvrez un mini-blog développeur propulsé par Nova CMS.',$now,$now,$now]);
}
function slugify(string $text): string { $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',trim($text))?:$text; $ascii=strtolower($ascii); $ascii=preg_replace('/[^a-z0-9]+/','-',$ascii)??''; return trim($ascii,'-')?:'site-'.bin2hex(random_bytes(3)); }
function unique_site_slug(PDO $pdo,string $input,?int $exceptId=null): string { $base=slugify($input);$slug=$base;$i=2;do{$sql='SELECT id FROM sites WHERE slug = ?'.($exceptId?' AND id <> ?':'');$stmt=$pdo->prepare($sql);$params=[$slug];if($exceptId)$params[]=$exceptId;$stmt->execute($params);if(!$stmt->fetch())return $slug;$slug=$base.'-'.$i++;}while($i<9999);return $base.'-'.bin2hex(random_bytes(2)); }
function unique_article_slug(PDO $pdo,int $siteId,string $title,?int $exceptId=null): string { $base=slugify($title);$slug=$base;$i=2;do{$sql='SELECT id FROM articles WHERE site_id = ? AND slug = ?'.($exceptId?' AND id <> ?':'');$stmt=$pdo->prepare($sql);$params=[$siteId,$slug];if($exceptId)$params[]=$exceptId;$stmt->execute($params);if(!$stmt->fetch())return $slug;$slug=$base.'-'.$i++;}while($i<9999);return $base.'-'.bin2hex(random_bytes(2)); }
function site_by_owner(int $userId): ?array { $s=db()->prepare('SELECT * FROM sites WHERE owner_id = ? ORDER BY id LIMIT 1');$s->execute([$userId]);return $s->fetch()?:null; }
function site_by_slug(string $slug): ?array { $s=db()->prepare('SELECT * FROM sites WHERE slug = ?');$s->execute([$slug]);return $s->fetch()?:null; }
function public_sites(int $limit=12): array { $s=db()->prepare("SELECT s.*, (SELECT COUNT(*) FROM articles a WHERE a.site_id=s.id AND a.status='published') AS article_count FROM sites s ORDER BY s.updated_at DESC LIMIT ?");$s->bindValue(1,$limit,PDO::PARAM_INT);$s->execute();return $s->fetchAll(); }
function article_by_id_for_site(int $id,int $siteId): ?array { $s=db()->prepare('SELECT * FROM articles WHERE id=? AND site_id=?');$s->execute([$id,$siteId]);return $s->fetch()?:null; }
function article_by_slug_for_site(int $siteId,string $slug): ?array { $s=db()->prepare("SELECT * FROM articles WHERE site_id=? AND slug=? AND status='published'");$s->execute([$siteId,$slug]);return $s->fetch()?:null; }
function site_articles(int $siteId,bool $publishedOnly=true): array { $sql='SELECT * FROM articles WHERE site_id=?'.($publishedOnly?" AND status='published'":'').' ORDER BY featured DESC, COALESCE(published_at,updated_at) DESC';$s=db()->prepare($sql);$s->execute([$siteId]);return $s->fetchAll(); }
function excerpt(string $text,int $max=170): string { $text=trim(preg_replace('/\s+/',' ',strip_tags($text))??'');return mb_strlen($text)<=$max?$text:rtrim(mb_substr($text,0,$max-1)).'…'; }
function safe_asset_url(string $value,string $fallback='assets/images/blog-default.svg'): string { $value=trim($value);if($value==='')return app_url($fallback);if(str_starts_with($value,'assets/images/')||str_starts_with($value,'uploads/'))return app_url($value);$url=filter_var($value,FILTER_VALIDATE_URL);$scheme=strtolower((string)parse_url($value,PHP_URL_SCHEME));return($url&&in_array($scheme,['http','https'],true))?$value:app_url($fallback); }
function save_uploaded_image(array $file): string { if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return '';if(($file['error']??1)!==UPLOAD_ERR_OK)throw new RuntimeException('Upload impossible.');if(($file['size']??0)>5*1024*1024)throw new RuntimeException('Image trop volumineuse (5 Mo max).');if(is_vercel())throw new RuntimeException('Sur Vercel, utilisez une URL d’image externe ou un stockage objet persistant.');$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($file['tmp_name']);$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];if(!isset($allowed[$mime]))throw new RuntimeException('Format image non autorisé.');$name=bin2hex(random_bytes(12)).'.'.$allowed[$mime];$target=__DIR__.'/uploads/'.$name;if(!is_dir(dirname($target)))mkdir(dirname($target),0775,true);if(!move_uploaded_file($file['tmp_name'],$target))throw new RuntimeException('Impossible d’enregistrer l’image.');return 'uploads/'.$name; }
function normalize_image_input(string $url,?array $file=null,string $fallback=''): string { if($file&&($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)return save_uploaded_image($file);$url=trim($url);if($url==='')return $fallback;if(str_starts_with($url,'assets/images/'))return $url;if(filter_var($url,FILTER_VALIDATE_URL)&&in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true))return $url;return $fallback; }
function valid_accent(string $value): string { return preg_match('/^#[0-9a-fA-F]{6}$/',$value)?$value:'#6d5dfc'; }
function render_header(string $title,string $description='',string $bodyClass=''): void { $siteName=env('CMS_SITE_NAME','Nova CMS')??'Nova CMS';$full=$title?$title.' — '.$siteName:$siteName;?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title><?=e($full)?></title><meta name="description" content="<?=e($description?:'Créez votre blog développeur avec Nova CMS.')?>"><link rel="icon" href="<?=e(app_url('assets/images/logo-mark.svg'))?>" type="image/svg+xml"><link rel="stylesheet" href="<?=e(app_url('assets/style.css'))?>"></head><body class="<?=e($bodyClass)?>"><?php }
function render_nav(bool $dashboard=false): void { $user=current_user();?><header class="topbar"><div class="topbar-inner"><a class="wordmark" href="<?=e(app_url())?>"><img src="<?=e(app_url('assets/images/logo-mark.svg'))?>" alt=""><span>Nova CMS</span></a><nav><?php if($dashboard):?><a href="<?=e(app_url('admin.php'))?>">Dashboard</a><?php else:?><a href="<?=e(app_url('#creators'))?>">Créateurs</a><a href="<?=e(app_url('#features'))?>">Fonctions</a><?php endif;?><?php if($user):?><a class="nav-pill" href="<?=e(app_url('admin.php'))?>">Mon espace</a><?php else:?><a href="<?=e(app_url('login.php'))?>">Connexion</a><a class="nav-pill" href="<?=e(app_url('register.php'))?>">Créer mon blog</a><?php endif;?></nav></div></header><?php }
function render_footer(): void { ?><footer class="site-footer"><div><strong>Nova CMS</strong><p>Open source publishing for builders.</p></div><div><span>v<?=e(CMS_VERSION)?></span><a href="https://github.com/ephraimlifanjo/cms" rel="noreferrer">GitHub</a></div></footer><script src="<?=e(app_url('assets/app.js'))?>" defer></script></body></html><?php }
