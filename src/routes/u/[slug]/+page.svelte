<script lang="ts">
  import PostCard from '$lib/components/PostCard.svelte';
  let { data } = $props();
  let featured = $derived(data.posts.filter((post: any) => post.featured));
  let journal = $derived(data.posts.filter((post: any) => !post.featured));
</script>

<svelte:head><title>{data.showcase.title}</title><meta name="description" content={data.showcase.headline} /></svelte:head>

<main style={`--accent:${data.showcase.accent || '#635bff'}`} class="bg-[#f7f5ef]">
  <section class="mx-auto max-w-7xl px-5 pb-20 pt-16 lg:px-8 lg:pt-24">
    <div class="grid items-end gap-12 lg:grid-cols-[1.05fr_.95fr]">
      <div><div class="text-xs font-black uppercase tracking-[.16em] text-[var(--accent)]">@{data.showcase.slug}{#if data.showcase.location} · {data.showcase.location}{/if}</div><h1 class="mt-5 text-[clamp(4.5rem,9vw,9rem)] font-black leading-[.82] tracking-[-.075em]">{data.showcase.title}</h1><p class="mt-8 max-w-2xl text-xl leading-8 text-black/55">{data.showcase.headline}</p><div class="mt-8 flex flex-wrap gap-3">{#if data.showcase.profile?.github_url}<a class="rounded-full bg-black px-5 py-3 text-sm font-black text-white" href={data.showcase.profile.github_url}>GitHub ↗</a>{/if}{#if data.showcase.profile?.website_url}<a class="rounded-full border border-black/12 px-5 py-3 text-sm font-black" href={data.showcase.profile.website_url}>Website ↗</a>{/if}</div></div>
      <div class="overflow-hidden rounded-[2.5rem]">{#if data.showcase.cover_url}<img src={data.showcase.cover_url} alt="" class="aspect-[4/3] h-full w-full object-cover" />{:else}<div class="aspect-[4/3] bg-[radial-gradient(circle_at_20%_20%,var(--accent),transparent_35%),radial-gradient(circle_at_75%_35%,#f0a6d8,transparent_30%),#181818]"></div>{/if}</div>
    </div>
  </section>

  <section class="border-y border-black/8 bg-white/50 py-20"><div class="mx-auto max-w-7xl px-5 lg:px-8"><div class="text-xs font-black uppercase tracking-[.16em] text-[var(--accent)]">Selected work</div><h2 class="mt-3 text-5xl font-black tracking-[-.055em]">Projects & ideas.</h2><div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">{#each featured as post}<PostCard {post} href="/u/{data.showcase.slug}/{post.slug}" />{:else}<p class="text-black/45">Aucun projet mis en avant.</p>{/each}</div></div></section>

  <section class="mx-auto grid max-w-7xl gap-14 px-5 py-24 lg:grid-cols-[.7fr_1.3fr] lg:px-8"><div><div class="text-xs font-black uppercase tracking-[.16em] text-[var(--accent)]">About</div><h2 class="mt-4 text-5xl font-black tracking-[-.055em]">The person behind the work.</h2>{#if data.showcase.profile?.avatar_url}<img src={data.showcase.profile.avatar_url} alt="" class="mt-8 size-28 rounded-[2rem] object-cover" />{/if}</div><div><p class="max-w-3xl whitespace-pre-wrap text-lg leading-9 text-black/60">{data.showcase.about || data.showcase.profile?.bio || 'Ce créateur prépare encore sa présentation.'}</p></div></section>

  <section class="mx-auto max-w-7xl px-5 pb-28 lg:px-8"><div class="text-xs font-black uppercase tracking-[.16em] text-[var(--accent)]">Journal</div><div class="mt-5 divide-y divide-black/10 border-y border-black/10">{#each journal as post}<a href="/u/{data.showcase.slug}/{post.slug}" class="grid gap-3 py-6 md:grid-cols-[130px_1fr_100px]"><span class="text-xs font-bold uppercase tracking-[.12em] text-black/40">{post.kind}</span><strong class="text-2xl tracking-[-.035em]">{post.title}</strong><span class="text-right text-black/40">↗</span></a>{:else}<div class="py-10 text-black/45">Pas encore de notes publiques.</div>{/each}</div></section>
</main>
