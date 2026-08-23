import { error, fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = async ({ locals, params }) => {
  const { data: thread } = await locals.supabase.from('threads').select('*,author:profiles!threads_author_id_fkey(username,display_name,avatar_url)').eq('id', params.id).maybeSingle();
  if (!thread) error(404, 'Discussion introuvable');
  const { data: replies } = await locals.supabase.from('replies').select('*,author:profiles!replies_author_id_fkey(username,display_name,avatar_url)').eq('thread_id', params.id).order('created_at');
  return { thread, replies: replies ?? [] };
};

export const actions: Actions = {
  reply: async ({ locals, params, request }) => {
    const { user } = await locals.safeGetSession();
    if (!user) return fail(401, { message: 'Connecte-toi pour répondre.' });
    const form = await request.formData();
    const body = String(form.get('body') || '').trim();
    if (body.length < 1 || body.length > 8000) return fail(400, { message: 'Réponse invalide.' });
    const { error: insertError } = await locals.supabase.from('replies').insert({ thread_id: params.id, author_id: user.id, body });
    if (insertError) return fail(400, { message: insertError.message });
    return { success: true };
  }
};
