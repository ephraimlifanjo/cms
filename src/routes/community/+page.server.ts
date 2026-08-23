import type { PageServerLoad } from './$types';

function one<T>(value: T | T[] | null | undefined): T | null {
  return Array.isArray(value) ? (value[0] ?? null) : (value ?? null);
}

export const load: PageServerLoad = async ({ locals, url }) => {
  const channel = url.searchParams.get('channel') || '';
  const { data: channels } = await locals.supabase.from('community_channels').select('*').order('sort_order');
  let query = locals.supabase.from('threads').select('id,title,body,channel_id,created_at,author:profiles!threads_author_id_fkey(username,display_name,avatar_url),replies(count)').order('created_at', { ascending: false }).limit(40);
  if (channel) query = query.eq('channel_id', channel);
  const { data: threads } = await query;
  return {
    channels: channels ?? [],
    threads: (threads ?? []).map((thread) => ({ ...thread, author: one(thread.author) })),
    channel
  };
};
