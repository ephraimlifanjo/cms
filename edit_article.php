<?php
require __DIR__.'/bootstrap.php'; require_admin();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT); if(!$id || !($article=article_by_id((int)$id))){http_response_code(404);exit('Article introuvable.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try {
        $title=trim((string)($_POST['title']??'')); $content=trim((string)($_POST['content']??'')); if($title===''||$content==='') throw new RuntimeException('Titre et contenu requis.');
        $article=array_merge($article,['title'=>$title,'excerpt'=>trim((string)($_POST['excerpt']??'')),'content'=>$content,'category'=>trim((string)($_POST['category']??'Général')) ?: 'Général','tags'=>trim((string)($_POST['tags']??'')),'status'=>($_POST['status']??'draft')==='published'?'published':'draft','featured'=>isset($_POST['featured'])?1:0,'seo_title'=>trim((string)($_POST['seo_title']??'')),'seo_description'=>trim((string)($_POST['seo_description']??''))]);
        $uploaded=save_uploaded_image($_FILES['image']??[]); $candidate=safe_image_url((string)($_POST['image_url']??'')); if($uploaded||$candidate!=='') $article['image']=$uploaded ?: $candidate;
        $slug=unique_slug(db(),$title,(int)$id); $now=gmdate('c'); $published=$article['status']==='published'?($article['published_at'] ?: $now):null;
        $s=db()->prepare('UPDATE articles SET title=?,slug=?,excerpt=?,content=?,category=?,tags=?,image=?,status=?,featured=?,seo_title=?,seo_description=?,updated_at=?,published_at=? WHERE id=?');
        $s->execute([$article['title'],$slug,$article['excerpt'],$article['content'],$article['category'],$article['tags'],$article['image'],$article['status'],$article['featured'],$article['seo_title'],$article['seo_description'],$now,$published,(int)$id]);
        flash('success','Article mis à jour.'); redirect('admin.php');
    } catch(Throwable $ex){ $error=$ex->getMessage(); }
}
render_header('Modifier','',true); require __DIR__.'/editor.php'; render_footer();
