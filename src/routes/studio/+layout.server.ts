import { redirect } from '@sveltejs/kit';
import type { LayoutServerLoad } from './$types';

export const load: LayoutServerLoad = async ({ locals }) => {
  const { user } = await locals.safeGetSession();
  if (!user) redirect(303, '/auth/login?next=/studio');
  const [{ data: profile }, { data: showcase }] = await Promise.all([
    locals.supabase.from('profiles').select('*').eq('id', user.id).single(),
    locals.supabase.from('showcases').select('*').eq('owner_id', user.id).single()
  ]);
  return { studioUser: user, studioProfile: profile, studioShowcase: showcase };
};
