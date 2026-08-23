import { fail } from '@sveltejs/kit';
import { uploadMedia } from '$lib/server/content';
import type { Actions } from './$types';

export const actions: Actions = {
  default: async ({ locals, request }) => {
    const { user } = await locals.safeGetSession(); if (!user) return fail(401, { message: 'Connexion requise.' });
    const data = await request.formData();
    const displayName=String(data.get('display_name')||'').trim(); const bio=String(data.get('bio')||'').trim(); const websiteUrl=String(data.get('website_url')||'').trim(); const githubUrl=String(data.get('github_url')||'').trim(); const title=String(data.get('title')||'').trim(); const headline=String(data.get('headline')||'').trim(); const about=String(data.get('about')||'').trim(); const location=String(data.get('location')||'').trim(); const accent=String(data.get('accent')||'#635bff');
    if (displayName.length < 2 || title.length < 2 || !/^#[0-9a-fA-F]{6}$/.test(accent)) return fail(400, { message: 'Nom, titre ou couleur invalide.' });
    try {
      const avatarFile = data.get('avatar') instanceof File ? data.get('avatar') as File : null;
      const coverFile = data.get('cover') instanceof File ? data.get('cover') as File : null;
      const avatarUrl = await uploadMedia(locals.supabase,user.id,'avatars',avatarFile,'avatar');
      const coverUrl = await uploadMedia(locals.supabase,user.id,'covers',coverFile,'cover');
      const profilePatch:any={display_name:displayName,bio,website_url:websiteUrl,github_url:githubUrl}; if(avatarUrl) profilePatch.avatar_url=avatarUrl;
      const showcasePatch:any={title,headline,about,location,accent}; if(coverUrl) showcasePatch.cover_url=coverUrl;
      const [{ error: profileError }, { error: showcaseError }] = await Promise.all([
        locals.supabase.from('profiles').update(profilePatch).eq('id',user.id),
        locals.supabase.from('showcases').update(showcasePatch).eq('owner_id',user.id)
      ]);
      if(profileError || showcaseError) return fail(400,{message:profileError?.message || showcaseError?.message});
      return { success: true };
    } catch (error:any) { return fail(400,{message:error?.message || 'Upload impossible.'}); }
  }
};
