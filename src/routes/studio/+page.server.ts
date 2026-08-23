import { fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = async ({ locals, parent }) => {
  const { studioShowcase } = await parent();
  const { data: posts } = await locals.supabase.from('posts').select('*').eq('showcase_id', studioShowcase.id).order('updated_at', { ascending: false });
  return { posts: posts ?? [] };
};

export const actions: Actions = {
  delete: async ({ locals, request }) => {
    const { user } = await locals.safeGetSession();
    if (!user) return fail(401, { message: 'Connexion requise.' });
    const data = await request.formData();
    const id = String(data.get('id') || '');
    const { error } = await locals.supabase.from('posts').delete().eq('id', id).eq('author_id', user.id);
    if (error) return fail(400, { message: error.message });
    return { deleted: true };
  }
};
