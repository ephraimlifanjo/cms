import { fail, redirect } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = async ({ locals }) => {
  const { user } = await locals.safeGetSession();
  if (!user) redirect(303, '/auth/login?next=/community/new');
  const { data: channels } = await locals.supabase.from('community_channels').select('*').order('sort_order');
  return { channels: channels ?? [] };
};

export const actions: Actions = {
  default: async ({ locals, request }) => {
    const { user } = await locals.safeGetSession();
    if (!user) return fail(401, { message: 'Connexion requise.', title: '', body: '', channelId: 'general' });
    const data = await request.formData();
    const title = String(data.get('title') || '').trim();
    const body = String(data.get('body') || '').trim();
    const channelId = String(data.get('channel_id') || 'general');
    if (title.length < 3 || title.length > 180 || body.length < 3 || body.length > 12000) return fail(400, { message: 'Titre ou message invalide.', title, body, channelId });
    const { data: created, error } = await locals.supabase.from('threads').insert({ author_id: user.id, channel_id: channelId, title, body }).select('id').single();
    if (error || !created) return fail(400, { message: error?.message || 'Création impossible.', title, body, channelId });
    redirect(303, `/community/${created.id}`);
  }
};
