import { createServerClient } from '@supabase/ssr';
import { PUBLIC_SUPABASE_PUBLISHABLE_KEY, PUBLIC_SUPABASE_URL } from '$env/static/public';
import type { Handle } from '@sveltejs/kit';

export const handle: Handle = async ({ event, resolve }) => {
  event.locals.supabase = createServerClient(PUBLIC_SUPABASE_URL, PUBLIC_SUPABASE_PUBLISHABLE_KEY, {
    cookies: {
      getAll: () => event.cookies.getAll(),
      setAll: (cookiesToSet) => {
        for (const { name, value, options } of cookiesToSet) {
          event.cookies.set(name, value, { ...options, path: '/' });
        }
      }
    }
  });

  event.locals.safeGetSession = async () => {
    const { data: sessionData } = await event.locals.supabase.auth.getSession();
    if (!sessionData.session) return { session: null, user: null };

    const { data: userData, error } = await event.locals.supabase.auth.getUser();
    if (error || !userData.user) return { session: null, user: null };
    return { session: sessionData.session, user: userData.user };
  };

  return resolve(event, {
    filterSerializedResponseHeaders: (name) => name === 'content-range' || name === 'x-supabase-api-version'
  });
};
