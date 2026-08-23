import { redirect } from '@sveltejs/kit';
import type { EmailOtpType } from '@supabase/supabase-js';
import type { RequestHandler } from './$types';

export const GET: RequestHandler = async ({ locals, url }) => {
  const tokenHash = url.searchParams.get('token_hash');
  const type = url.searchParams.get('type') as EmailOtpType | null;
  const code = url.searchParams.get('code');

  if (code) {
    const { error } = await locals.supabase.auth.exchangeCodeForSession(code);
    if (!error) redirect(303, '/studio');
  }

  if (tokenHash && type) {
    const { error } = await locals.supabase.auth.verifyOtp({ token_hash: tokenHash, type });
    if (!error) redirect(303, '/studio');
  }

  redirect(303, '/auth/login?message=confirmation-failed');
};
