import type { PageServerLoad } from './$types';

function one<T>(value: T | T[] | null | undefined): T | null {
  return Array.isArray(value) ? (value[0] ?? null) : (value ?? null);
}

export const load: PageServerLoad = async ({ locals }) => {
  const [showcasesRes, postsRes, threadsRes] = await Promise.all([
    locals.supabase.from('showcases').select('id,slug,title,headline,cover_url,accent,profile:profiles!showcases_owner_id_fkey(username,display_name,avatar_url,bio)').eq('is_published', true).order('updated_at', { ascending: false }).limit(6),
    locals.supabase.from('posts').select('id,slug,title,excerpt,kind,cover_url,published_at,created_at,showcase:showcases!posts_showcase_id_fkey(slug,title)').eq('status', 'published').eq('featured', true).order('published_at', { ascending: false }).limit(6),
    locals.supabase.from('threads').select('id,title,body,channel_id,created_at,author:profiles!threads_author_id_fkey(username,display_name,avatar_url),replies(count)').order('created_at', { ascending: false }).limit(5)
  ]);

  return {
    showcases: (showcasesRes.data ?? []).map((item) => ({ ...item, profile: one(item.profile) })),
    featuredPosts: (postsRes.data ?? []).map((item) => ({ ...item, showcase: one(item.showcase) })),
    threads: (threadsRes.data ?? []).map((item) => ({ ...item, author: one(item.author) }))
  };
};
