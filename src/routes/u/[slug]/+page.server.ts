import { error } from '@sveltejs/kit';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = async ({ locals, params }) => {
  const { data: showcase } = await locals.supabase.from('showcases').select('*,profile:profiles!showcases_owner_id_fkey(*)').eq('slug', params.slug).maybeSingle();
  if (!showcase) error(404, 'Showcase introuvable');
  const { data: posts } = await locals.supabase.from('posts').select('*').eq('showcase_id', showcase.id).eq('status', 'published').order('featured', { ascending: false }).order('published_at', { ascending: false });
  return { showcase, posts: posts ?? [] };
};
