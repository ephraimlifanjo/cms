<script lang="ts">
  import { enhance } from '$app/forms';
  import { onMount } from 'svelte';
  import { clearDraft, loadDraft, saveDraft } from '$lib/client/drafts';
  let { form } = $props();
  const key = 'studio:new-post';
  let title = $state('');
  let kind = $state('project');
  let excerpt = $state('');
  let content = $state('');
  let coverUrl = $state('');
  let status = $state('draft');
  let featured = $state(false);
  let tags = $state('');
  let hydrated = $state(false);
  let timer: ReturnType<typeof setTimeout> | undefined;

  onMount(async () => {
    const failed = (form as any)?.values;
    const draft = await loadDraft<any>(key);
    const source = failed ?? draft;
    if (source) {
      title = source.title || '';
      kind = source.kind || 'project';
      excerpt = source.excerpt || '';
      content = source.content || '';
      coverUrl = source.coverUrl || '';
      status = source.status || 'draft';
      featured = Boolean(source.featured);
      tags = source.tags || '';
    }
    hydrated = true;
  });

  $effect(() => {
    const payload = { title, kind, excerpt, content, coverUrl, status, featured, tags };
    if (!hydrated) return;
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => void saveDraft(key, payload), 500);
  });

  const submitEnhance = () => async ({ result, update }: any) => {
    if (result.type === 'redirect') await clearDraft(key);
    await update();
  };
</script>
<svelte:head><title>Nouveau contenu — Studio</title></svelte:head>
<form method="POST" use:enhance={submitEnhance} class="rounded-[2rem] bg-white p-7">
  <div class="text-xs font-black uppercase tracking-[.14em] text-[#635bff]">Editor · autosave IndexedDB</div><h1 class="mt-2 text-4xl font-black tracking-[-.05em]">Nouveau contenu</h1><p class="mt-2 text-sm text-black/45">Le brouillon du navigateur est local. La publication finale va dans Supabase.</p>
  {#if form?.message}<div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm text-red-700">{form.message}</div>{/if}
  <div class="mt-7 grid gap-5">
    <label class="text-sm font-black">Titre<input bind:value={title} name="title" maxlength="180" class="mt-2 w-full rounded-2xl border border-black/10 bg-[#faf9f6] p-4 text-lg font-bold outline-none focus:border-[#635bff]" required /></label>
    <div class="grid gap-5 md:grid-cols-2"><label class="text-sm font-black">Type<select bind:value={kind} name="kind" class="mt-2 w-full rounded-2xl border border-black/10 bg-[#faf9f6] p-4"><option value="project">Projet</option><option value="article">Article</option><option value="note">Note</option></select></label><label class="text-sm font-black">Statut<select bind:value={status} name="status" class="mt-2 w-full rounded-2xl border border-black/10 bg-[#faf9f6] p-4"><option value="draft">Brouillon</option><option value="published">Publié</option></select></label></div>
    <label class="text-sm font-black">Résumé<textarea bind:value={excerpt} name="excerpt" rows="3" class="mt-2 w-full rounded-2xl border border-black/10 bg-[#faf9f6] p-4 leading-7 outline-none focus:border-[#635bff]"></textarea></label>
    <label class="text-sm font-black">Contenu<textarea bind:value={content} name="content" rows="16" class="mt-2 w-full rounded-2xl border border-black/10 bg-[#faf9f6] p-4 font-mono text-sm leading-7 outline-none focus:border-[#635bff]"></textarea></label>
    <label class="text-sm font-black">Image de couverture URL<input bind:value={coverUrl} name="cover_url" type="url" class="mt-2 w-full rounded-2xl border border-black/10 bg-[#faf9f6] p-4 outline-none focus:border-[#635bff]" /></label>
    <label class="text-sm font-black">Tags séparés par virgules<input bind:value={tags} name="tags" class="mt-2 w-full rounded-2xl border border-black/10 bg-[#faf9f6] p-4 outline-none focus:border-[#635bff]" /></label>
    <label class="flex items-center gap-3 text-sm font-black"><input bind:checked={featured} name="featured" type="checkbox" class="size-5" /> Mettre en avant dans Selected Work</label>
    <div class="flex flex-wrap gap-3"><button class="rounded-full bg-black px-6 py-3.5 text-sm font-black text-white">Enregistrer</button><button type="button" onclick={async () => { await clearDraft(key); title = ''; excerpt = ''; content = ''; coverUrl = ''; tags = ''; }} class="rounded-full border border-black/10 px-5 py-3.5 text-sm font-black">Effacer le brouillon local</button></div>
  </div>
</form>
