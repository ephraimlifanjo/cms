import { error } from '@sveltejs/kit';
import type { PageServerLoad } from './$types';

function one<T>(value: T | T[] | null | undefined): T | null {
  return Array.isArray(value) ? (value[0] ?? null) : (value ?? null);
}

export const load: PageServerLoad = async ({ locals, params }) => {
  const { data: rawShowcase } = await locals.supabase.from('showcases').select('*,profile:profiles!showcases_owner_id_fkey(*)').eq('slug', params.slug).maybeSingle();
  if (!rawShowcase) error(404, 'Showcase introuvable');
  const showcase = { ...rawShowcase, profile: one(rawShowcase.profile) };
  const { data: posts } = await locals.supabase.from('posts').select('*').eq('showcase_id', showcase.id).eq('status', 'published').order('featured', { ascending: false }).order('published_at', { ascending: false });
  return { showcase, posts: posts ?? [] };
};
