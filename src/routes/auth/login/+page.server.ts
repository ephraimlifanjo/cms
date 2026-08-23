import { fail, redirect } from '@sveltejs/kit';
import type { Actions } from './$types';

export const actions: Actions = {
  default: async ({ locals, request, url }) => {
    const data = await request.formData();
    const email = String(data.get('email') || '').trim();
    const password = String(data.get('password') || '');
    if (!email || !password) return fail(400, { message: 'Email et mot de passe requis.', email });

    const { error } = await locals.supabase.auth.signInWithPassword({ email, password });
    if (error) return fail(400, { message: 'Connexion impossible. Vérifie tes informations.', email });

    const next = url.searchParams.get('next');
    redirect(303, next?.startsWith('/') ? next : '/studio');
  }
};
