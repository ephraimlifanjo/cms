<?php
require __DIR__.'/bootstrap.php'; require_admin();
$article=['title'=>'','excerpt'=>'','content'=>'','category'=>'Général','tags'=>'','image'=>'','status'=>'draft','featured'=>0,'seo_title'=>'','seo_description'=>'']; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try {
        $title=trim((string)($_POST['title']??'')); $content=trim((string)($_POST['content']??''));
        if($title===''||$content==='') throw new RuntimeException('Titre et contenu requis.');
        $article=array_merge($article,['title'=>$title,'excerpt'=>trim((string)($_POST['excerpt']??'')),'content'=>$content,'category'=>trim((string)($_POST['category']??'Général')) ?: 'Général','tags'=>trim((string)($_POST['tags']??'')),'status'=>($_POST['status']??'draft')==='published'?'published':'draft','featured'=>isset($_POST['featured'])?1:0,'seo_title'=>trim((string)($_POST['seo_title']??'')),'seo_description'=>trim((string)($_POST['seo_description']??''))]);
        $uploaded=save_uploaded_image($_FILES['image']??[]); $article['image']=$uploaded ?: safe_image_url((string)($_POST['image_url']??''));
        $now=gmdate('c'); $slug=unique_slug(db(),$title); $published=$article['status']==='published'?$now:null;
        $s=db()->prepare('INSERT INTO articles (title,slug,excerpt,content,category,tags,image,status,featured,seo_title,seo_description,created_at,updated_at,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->execute([$article['title'],$slug,$article['excerpt'],$article['content'],$article['category'],$article['tags'],$article['image'],$article['status'],$article['featured'],$article['seo_title'],$article['seo_description'],$now,$now,$published]);
        flash('success','Article créé.'); redirect('admin.php');
    } catch(Throwable $ex){ $error=$ex->getMessage(); }
}
render_header('Nouvel article','',true); require __DIR__.'/editor.php'; render_footer();
