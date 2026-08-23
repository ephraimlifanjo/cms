import { fail, redirect } from '@sveltejs/kit';
import { uniquePostSlug } from '$lib/server/content';
import type { Actions } from './$types';

type EditorValues = { title: string; kind: string; excerpt: string; content: string; coverUrl: string; status: string; featured: boolean; tags: string };
const emptyValues: EditorValues = { title: '', kind: 'project', excerpt: '', content: '', coverUrl: '', status: 'draft', featured: false, tags: '' };

export const actions: Actions = {
  default: async ({ locals, request }) => {
    const { user } = await locals.safeGetSession();
    if (!user) return fail(401, { message: 'Connexion requise.', values: emptyValues });
    const { data: showcase } = await locals.supabase.from('showcases').select('id').eq('owner_id', user.id).single();
    if (!showcase) return fail(400, { message: 'Showcase introuvable.', values: emptyValues });

    const data = await request.formData();
    const title = String(data.get('title') || '').trim();
    const kind = String(data.get('kind') || 'article');
    const excerpt = String(data.get('excerpt') || '').trim();
    const content = String(data.get('content') || '').trim();
    const coverUrl = String(data.get('cover_url') || '').trim();
    const status = String(data.get('status') || 'draft');
    const featured = data.get('featured') === 'on';
    const tags = String(data.get('tags') || '').split(',').map((t) => t.trim().toLowerCase()).filter(Boolean).slice(0, 12);
    const values = { title, kind, excerpt, content, coverUrl, status, featured, tags: tags.join(', ') };
    if (title.length < 2 || title.length > 180) return fail(400, { message: 'Titre invalide.', values });
    if (!['project', 'article', 'note'].includes(kind) || !['draft', 'published'].includes(status)) return fail(400, { message: 'Type ou statut invalide.', values });

    const slug = await uniquePostSlug(locals.supabase, showcase.id, title);
    const { error } = await locals.supabase.from('posts').insert({ showcase_id: showcase.id, author_id: user.id, kind, slug, title, excerpt, content, cover_url: coverUrl, status, featured, tags, published_at: status === 'published' ? new Date().toISOString() : null });
    if (error) return fail(400, { message: error.message, values });
    redirect(303, '/studio');
  }
};
