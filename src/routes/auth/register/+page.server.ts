import { fail } from '@sveltejs/kit';
import type { Actions } from './$types';

export const actions: Actions = {
  default: async ({ locals, request, url }) => {
    const data = await request.formData();
    const displayName = String(data.get('display_name') || '').trim();
    const email = String(data.get('email') || '').trim();
    const password = String(data.get('password') || '');
    const confirmation = String(data.get('password_confirmation') || '');

    if (displayName.length < 2 || !email) return fail(400, { message: 'Nom et email requis.', displayName, email });
    if (password.length < 10) return fail(400, { message: 'Utilise au moins 10 caractères.', displayName, email });
    if (password !== confirmation) return fail(400, { message: 'Les mots de passe ne correspondent pas.', displayName, email });

    const { data: authData, error } = await locals.supabase.auth.signUp({
      email,
      password,
      options: {
        data: { display_name: displayName },
        emailRedirectTo: `${url.origin}/auth/confirm`
      }
    });

    if (error) return fail(400, { message: error.message, displayName, email });
    return { success: true, needsEmail: !authData.session, email };
  }
};
