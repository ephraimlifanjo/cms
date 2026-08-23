import { error, fail, redirect } from '@sveltejs/kit';
import { uniquePostSlug } from '$lib/server/content';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = async ({ locals, params }) => {
  const { user } = await locals.safeGetSession();
  if (!user) redirect(303, '/auth/login');
  const { data: post } = await locals.supabase.from('posts').select('*').eq('id', params.id).eq('author_id', user.id).maybeSingle();
  if (!post) error(404, 'Contenu introuvable');
  return { post };
};

export const actions: Actions = {
  default: async ({ locals, params, request }) => {
    const { user } = await locals.safeGetSession(); if (!user) return fail(401);
    const { data: existing } = await locals.supabase.from('posts').select('*').eq('id', params.id).eq('author_id', user.id).single();
    if (!existing) return fail(404, { message: 'Contenu introuvable.' });
    const data = await request.formData(); const title=String(data.get('title')||'').trim(); const kind=String(data.get('kind')||'article'); const excerpt=String(data.get('excerpt')||'').trim(); const content=String(data.get('content')||'').trim(); const coverUrl=String(data.get('cover_url')||'').trim(); const status=String(data.get('status')||'draft'); const featured=data.get('featured')==='on'; const tags=String(data.get('tags')||'').split(',').map(t=>t.trim().toLowerCase()).filter(Boolean).slice(0,12);
    if (title.length < 2 || title.length > 180) return fail(400, { message: 'Titre invalide.' });
    const slug = title === existing.title ? existing.slug : await uniquePostSlug(locals.supabase, existing.showcase_id, title, existing.id);
    const publishedAt = status === 'published' ? (existing.published_at || new Date().toISOString()) : null;
    const { error: updateError } = await locals.supabase.from('posts').update({ title,slug,kind,excerpt,content,cover_url:coverUrl,status,featured,tags,published_at:publishedAt }).eq('id', existing.id).eq('author_id', user.id);
    if (updateError) return fail(400, { message: updateError.message });
    redirect(303, '/studio');
  }
};
