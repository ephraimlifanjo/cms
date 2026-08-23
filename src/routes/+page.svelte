<script lang="ts">
  import PostCard from '$lib/components/PostCard.svelte';
  let { data } = $props();
</script>

<svelte:head><title>Nova CMS — Build. Publish. Belong.</title><meta name="description" content="Personal showcases, publishing and a developer community powered by SvelteKit and Supabase." /></svelte:head>

<main>
  <section class="mx-auto max-w-7xl px-5 pb-24 pt-16 lg:px-8 lg:pt-24">
    <div class="grid items-end gap-12 lg:grid-cols-[1.08fr_.92fr]">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/60 px-3 py-2 text-[11px] font-black uppercase tracking-[.15em] text-black/55"><span class="size-2 rounded-full bg-[#635bff]"></span> Nova Studio community · Open source</div>
        <h1 class="mt-8 max-w-5xl text-[clamp(4rem,9vw,8.8rem)] font-black leading-[.82] tracking-[-.075em]">Build things.<br/><span class="font-serif italic font-medium">Show them well.</span></h1>
        <p class="mt-8 max-w-2xl text-lg leading-8 text-black/55">Un vrai espace pour publier tes projets, écrire ce que tu apprends et discuter avec d’autres développeurs. Ton showcase t’appartient. La communauté reste simple.</p>
        <div class="mt-9 flex flex-wrap gap-3"><a href="/auth/register" class="rounded-full bg-black px-6 py-3.5 text-sm font-black text-white">Créer mon showcase →</a><a href="/community" class="rounded-full border border-black/12 bg-white px-6 py-3.5 text-sm font-black">Entrer dans la communauté</a></div>
      </div>
      <div class="relative overflow-hidden rounded-[2.5rem] bg-[#171717] p-5 text-white shadow-2xl shadow-black/15 lg:p-7">
        <div class="aspect-[4/3] rounded-[1.8rem] bg-[radial-gradient(circle_at_20%_20%,#7067ff,transparent_36%),radial-gradient(circle_at_78%_32%,#ff8cda,transparent_30%),radial-gradient(circle_at_54%_88%,#ffb657,transparent_35%),#111]"></div>
        <div class="mt-6 flex items-end justify-between gap-5"><div><div class="text-xs font-black uppercase tracking-[.16em] text-white/45">One account</div><div class="mt-2 text-3xl font-black tracking-[-.045em]">Showcase + Journal + Community</div></div><span class="grid size-12 shrink-0 place-items-center rounded-full bg-white text-xl text-black">↗</span></div>
      </div>
    </div>
  </section>

  <section id="discover" class="border-y border-black/8 bg-white/45 py-24">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end"><div><div class="text-xs font-black uppercase tracking-[.16em] text-[#635bff]">Discover</div><h2 class="mt-3 text-5xl font-black tracking-[-.055em] md:text-7xl">People building in public.</h2></div><p class="max-w-md text-sm leading-7 text-black/50">Chaque membre possède une vraie page personnelle, pas un profil enfermé dans un feed.</p></div>
      <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {#each data.showcases as showcase}
          <a href="/u/{showcase.slug}" class="group overflow-hidden rounded-[2rem] border border-black/8 bg-[#f8f7f3]">
            <div class="aspect-[16/10] overflow-hidden">{#if showcase.cover_url}<img src={showcase.cover_url} alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />{:else}<div class="h-full bg-gradient-to-br from-[#b9b0ff] via-[#ffd0ec] to-[#ffe6b4]"></div>{/if}</div>
            <div class="flex gap-4 p-6">{#if showcase.profile?.avatar_url}<img src={showcase.profile.avatar_url} alt="" class="size-12 rounded-2xl object-cover" />{:else}<div class="grid size-12 place-items-center rounded-2xl bg-black text-sm font-black text-white">{showcase.profile?.display_name?.slice(0,1) || 'N'}</div>{/if}<div><h3 class="font-black tracking-[-.03em]">{showcase.title}</h3><p class="mt-1 line-clamp-2 text-sm leading-6 text-black/50">{showcase.headline}</p><div class="mt-3 text-[11px] font-black uppercase tracking-[.13em] text-[#635bff]">@{showcase.slug}</div></div></div>
          </a>
        {:else}
          <div class="col-span-full rounded-[2rem] border border-dashed border-black/15 p-12 text-center text-black/45">Les premiers showcases apparaîtront ici après la connexion Supabase.</div>
        {/each}
      </div>
    </div>
  </section>

  <section class="mx-auto max-w-7xl px-5 py-24 lg:px-8">
    <div class="flex items-end justify-between gap-5"><div><div class="text-xs font-black uppercase tracking-[.16em] text-[#635bff]">Selected work</div><h2 class="mt-3 text-5xl font-black tracking-[-.055em]">Projects worth opening.</h2></div></div>
    <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">{#each data.featuredPosts as post}<PostCard post={post} href="/u/{post.showcase?.slug}/{post.slug}" />{:else}<p class="text-black/45">Aucun projet publié pour le moment.</p>{/each}</div>
  </section>

  <section class="bg-[#121212] py-24 text-white">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="grid gap-12 lg:grid-cols-[.8fr_1.2fr]"><div><div class="text-xs font-black uppercase tracking-[.16em] text-[#8e87ff]">Nova Studio Community</div><h2 class="mt-4 text-5xl font-black tracking-[-.055em] md:text-7xl">Come talk about the work.</h2><p class="mt-6 max-w-md leading-7 text-white/50">Questions, help, collaboration, launches and technical discussions without turning the product into another noisy social network.</p><a href="/community" class="mt-8 inline-flex rounded-full bg-white px-5 py-3 text-sm font-black text-black">Open community →</a></div><div class="divide-y divide-white/10 border-y border-white/10">{#each data.threads as thread}<a href="/community/{thread.id}" class="grid grid-cols-[1fr_auto] gap-5 py-5"><div><div class="text-xs font-black uppercase tracking-[.13em] text-[#8e87ff]">#{thread.channel_id}</div><h3 class="mt-2 text-xl font-bold">{thread.title}</h3><p class="mt-1 text-sm text-white/40">par {thread.author?.display_name || 'member'}</p></div><span class="text-white/40">↗</span></a>{:else}<div class="py-12 text-white/45">La communauté attend ses premiers messages.</div>{/each}</div></div>
    </div>
  </section>
</main>
