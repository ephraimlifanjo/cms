<script lang="ts">
  import { formatDate, readingTime } from '$lib/utils';
  let { data } = $props();
</script>

<svelte:head><title>{data.post.title} — {data.showcase.title}</title><meta name="description" content={data.post.excerpt} /></svelte:head>

<main class="bg-[#f7f5ef]">
  <article class="mx-auto max-w-5xl px-5 py-20 lg:px-8 lg:py-28">
    <a href="/u/{data.showcase.slug}" class="text-xs font-black uppercase tracking-[.14em] text-black/45">← {data.showcase.title}</a>
    <div class="mt-10 text-xs font-black uppercase tracking-[.15em] text-[#635bff]">{data.post.kind} · {formatDate(data.post.published_at)} · {readingTime(data.post.content)} min</div>
    <h1 class="mt-5 text-[clamp(3.6rem,8vw,7rem)] font-black leading-[.9] tracking-[-.07em]">{data.post.title}</h1>
    {#if data.post.excerpt}<p class="mt-8 max-w-3xl text-xl leading-9 text-black/55">{data.post.excerpt}</p>{/if}
    {#if data.post.cover_url}<img src={data.post.cover_url} alt="" class="mt-12 aspect-[16/9] w-full rounded-[2.5rem] object-cover" />{/if}
    <div class="prose-nova mt-14 max-w-3xl">{data.post.content}</div>
    {#if data.post.tags?.length}<div class="mt-12 flex flex-wrap gap-2">{#each data.post.tags as tag}<span class="rounded-full border border-black/10 bg-white px-3 py-2 text-xs font-bold">#{tag}</span>{/each}</div>{/if}
  </article>
</main>
