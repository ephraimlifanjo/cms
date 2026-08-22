<?php
declare(strict_types=1);

const CMS_VERSION = '1.0.0';

function env(string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') return $default;
    return (string) $value;
}
function is_https(): bool { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'); }
function app_url(string $path = ''): string {
    $base = rtrim((string) env('APP_URL', ''), '/');
    if ($base === '') { $scheme = is_https() ? 'https' : 'http'; $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000'; $base = $scheme . '://' . $host; }
    return $base . '/' . ltrim($path, '/');
}
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . app_url($path)); exit; }

function start_secure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('novacms_session');
    session_set_cookie_params(['httponly'=>true,'secure'=>is_https(),'samesite'=>'Lax','path'=>'/']);
    session_start();
}
start_secure_session();
header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: strict-origin-when-cross-origin'); header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void { $token=(string)($_POST['_csrf']??''); if($token===''||!hash_equals(csrf_token(),$token)){http_response_code(419);exit('Session expirée. Rechargez la page et réessayez.');} }
function flash(string $type,string $message): void { $_SESSION['flash']=compact('type','message'); }
function take_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return is_array($f)?$f:null; }
function admin_username(): string { return env('CMS_ADMIN_USERNAME','admin') ?? 'admin'; }
function admin_password(): ?string { $value=env('CMS_ADMIN_PASSWORD'); if($value)return $value; if(!env('VERCEL_ENV')&&PHP_SAPI==='cli-server')return 'change-me-now'; return null; }
function is_admin(): bool { return !empty($_SESSION['admin_authenticated']); }
function require_admin(): void { if(!is_admin()) redirect('login.php'); }
function login_rate_limited(): bool { $now=time(); $attempts=array_values(array_filter($_SESSION['login_attempts']??[],fn($t)=>is_int($t)&&$t>$now-600)); $_SESSION['login_attempts']=$attempts; return count($attempts)>=6; }
function record_login_failure(): void { $_SESSION['login_attempts'][]=time(); }
function clear_login_failures(): void { unset($_SESSION['login_attempts']); }

function db(): PDO {
    static $pdo; if($pdo instanceof PDO)return $pdo;
    $url=env('DATABASE_URL');
    if($url){
        $parts=parse_url($url); if(!$parts||empty($parts['scheme'])||empty($parts['host'])) throw new RuntimeException('DATABASE_URL invalide.');
        $scheme=strtolower((string)$parts['scheme']); $name=ltrim((string)($parts['path']??''),'/'); $user=rawurldecode((string)($parts['user']??'')); $pass=rawurldecode((string)($parts['pass']??'')); $port=isset($parts['port'])?';port='.(int)$parts['port']:'';
        if(in_array($scheme,['postgres','postgresql'],true))$dsn="pgsql:host={$parts['host']}{$port};dbname={$name};sslmode=require";
        elseif($scheme==='mysql')$dsn="mysql:host={$parts['host']}{$port};dbname={$name};charset=utf8mb4";
        else throw new RuntimeException('DATABASE_URL doit utiliser mysql:// ou postgresql://.');
        $pdo=new PDO($dsn,$user,$pass);
    } else {
        $file=env('SQLITE_PATH',__DIR__.'/storage/cms.sqlite'); if(env('VERCEL_ENV'))$file='/tmp/nova-cms.sqlite'; $dir=dirname((string)$file); if(!is_dir($dir))@mkdir($dir,0775,true); $pdo=new PDO('sqlite:'.$file);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC); migrate($pdo); return $pdo;
}
function migrate(PDO $pdo): void {
    static $done=false; if($done)return; $driver=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if($driver==='pgsql'){$id='BIGSERIAL PRIMARY KEY';$bool='BOOLEAN';$text='TEXT';} elseif($driver==='mysql'){$id='BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';$bool='TINYINT(1)';$text='LONGTEXT';} else {$id='INTEGER PRIMARY KEY AUTOINCREMENT';$bool='INTEGER';$text='TEXT';}
    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (id {$id}, title VARCHAR(220) NOT NULL, slug VARCHAR(240) NOT NULL UNIQUE, excerpt VARCHAR(500) NOT NULL DEFAULT '', content {$text} NOT NULL, category VARCHAR(80) NOT NULL DEFAULT 'Général', tags VARCHAR(240) NOT NULL DEFAULT '', image VARCHAR(500) NOT NULL DEFAULT '', status VARCHAR(20) NOT NULL DEFAULT 'draft', featured {$bool} NOT NULL DEFAULT 0, seo_title VARCHAR(220) NOT NULL DEFAULT '', seo_description VARCHAR(320) NOT NULL DEFAULT '', created_at VARCHAR(40) NOT NULL, updated_at VARCHAR(40) NOT NULL, published_at VARCHAR(40) NULL)");
    $done=true; $count=(int)$pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
    if($count===0&&env('CMS_SEED_DEMO','1')==='1'){ $now=gmdate('c'); $s=$pdo->prepare('INSERT INTO articles (title,slug,excerpt,content,category,tags,status,featured,created_at,updated_at,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)'); $s->execute(['Bienvenue sur Nova CMS','bienvenue-sur-nova-cms','Un micro-CMS PHP rapide, sécurisé et agréable à utiliser.',"Nova CMS est prêt. Connectez-vous à l’administration pour créer vos propres contenus.\n\nLe projet supporte les brouillons, la publication, la recherche, le SEO et les images.",'Actualités','cms,php,open-source','published',1,$now,$now,$now]); }
}
function slugify(string $text): string { $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',trim($text))?:$text; $ascii=strtolower($ascii); $ascii=preg_replace('/[^a-z0-9]+/','-',$ascii)??''; return trim($ascii,'-')?:'article-'.bin2hex(random_bytes(3)); }
function unique_slug(PDO $pdo,string $title,?int $exceptId=null): string { $base=slugify($title);$slug=$base;$i=2;do{$sql='SELECT id FROM articles WHERE slug = ?'.($exceptId?' AND id <> ?':'');$s=$pdo->prepare($sql);$p=[$slug];if($exceptId)$p[]=$exceptId;$s->execute($p);if(!$s->fetch())return $slug;$slug=$base.'-'.$i++;}while($i<9999);return $base.'-'.bin2hex(random_bytes(2)); }
function article_by_id(int $id): ?array { $s=db()->prepare('SELECT * FROM articles WHERE id=?');$s->execute([$id]);return $s->fetch()?:null; }
function article_by_slug(string $slug): ?array { $s=db()->prepare("SELECT * FROM articles WHERE slug=? AND status='published'");$s->execute([$slug]);return $s->fetch()?:null; }
function categories(): array { return db()->query("SELECT category, COUNT(*) total FROM articles WHERE status='published' GROUP BY category ORDER BY category")->fetchAll(); }
function save_uploaded_image(array $file): string { if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return ''; if(($file['error']??1)!==UPLOAD_ERR_OK)throw new RuntimeException('Upload impossible.'); if(($file['size']??0)>5*1024*1024)throw new RuntimeException('Image trop volumineuse (5 Mo max).'); $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($file['tmp_name']);$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];if(!isset($allowed[$mime]))throw new RuntimeException('Format image non autorisé.');if(env('VERCEL_ENV'))throw new RuntimeException('Sur Vercel, utilisez une URL d’image externe ou un stockage objet persistant.');$name=bin2hex(random_bytes(12)).'.'.$allowed[$mime];$target=__DIR__.'/uploads/'.$name;if(!is_dir(dirname($target)))mkdir(dirname($target),0775,true);if(!move_uploaded_file($file['tmp_name'],$target))throw new RuntimeException('Impossible d’enregistrer l’image.');return 'uploads/'.$name; }
function safe_image_url(string $value): string { $value=trim($value);if($value==='')return '';if(str_starts_with($value,'uploads/'))return $value;$url=filter_var($value,FILTER_VALIDATE_URL);if(!$url||!in_array(strtolower((string)parse_url($value,PHP_URL_SCHEME)),['http','https'],true))return '';return $value; }
function excerpt(string $text,int $max=170): string { $text=trim(preg_replace('/\s+/',' ',strip_tags($text))??'');return mb_strlen($text)<=$max?$text:rtrim(mb_substr($text,0,$max-1)).'…'; }
function render_header(string $title,string $description='',bool $admin=false): void { $site=env('CMS_SITE_NAME','Nova CMS');$full=$title?$title.' — '.$site:$site;?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($full)?></title><meta name="description" content="<?=e($description?:'Micro-CMS PHP open source, rapide et sécurisé.')?>"><link rel="stylesheet" href="<?=e(app_url('assets/style.css'))?>"></head><body class="<?=$admin?'admin-body':'public-body'?>"><?php }
function render_footer(): void { ?><footer class="site-footer"><span>Nova CMS <?=e(CMS_VERSION)?></span><span>PHP · Open Source</span></footer><script src="<?=e(app_url('assets/app.js'))?>" defer></script></body></html><?php }
