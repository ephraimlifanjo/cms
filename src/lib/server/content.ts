import type { SupabaseClient } from '@supabase/supabase-js';
import { slugify } from '$lib/utils';

export async function uniquePostSlug(supabase: SupabaseClient, showcaseId: string, title: string, exceptId?: string): Promise<string> {
  const base = slugify(title) || `post-${Date.now()}`;
  for (let n = 1; n <= 50; n += 1) {
    const candidate = n === 1 ? base : `${base}-${n}`;
    let query = supabase.from('posts').select('id').eq('showcase_id', showcaseId).eq('slug', candidate).limit(1);
    if (exceptId) query = query.neq('id', exceptId);
    const { data } = await query;
    if (!data?.length) return candidate;
  }
  return `${base}-${crypto.randomUUID().slice(0, 6)}`;
}

export async function uploadMedia(supabase: SupabaseClient, userId: string, bucket: 'avatars' | 'covers', file: File | null, prefix: string): Promise<string | null> {
  if (!file || file.size === 0) return null;
  const allowed = new Map([
    ['image/jpeg', 'jpg'],
    ['image/png', 'png'],
    ['image/webp', 'webp'],
    ['image/gif', 'gif']
  ]);
  const extension = allowed.get(file.type);
  if (!extension) throw new Error('Format image non supporté.');
  const max = bucket === 'avatars' ? 5 * 1024 * 1024 : 10 * 1024 * 1024;
  if (file.size > max) throw new Error('Image trop lourde.');

  const path = `${userId}/${prefix}-${crypto.randomUUID()}.${extension}`;
  const bytes = await file.arrayBuffer();
  const { error } = await supabase.storage.from(bucket).upload(path, bytes, { contentType: file.type, upsert: false, cacheControl: '3600' });
  if (error) throw error;
  return supabase.storage.from(bucket).getPublicUrl(path).data.publicUrl;
}
