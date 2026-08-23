<script lang="ts">
  import { formatDate } from '$lib/utils';
  let { post, href, compact = false } = $props<{ post: any; href: string; compact?: boolean }>();
</script>

<a {href} class="group block overflow-hidden rounded-[2rem] border border-black/8 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/8">
  <div class={compact ? 'aspect-[16/10]' : 'aspect-[4/3]'}>
    {#if post.cover_url}
      <img src={post.cover_url} alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
    {:else}
      <div class="grid h-full place-items-center bg-gradient-to-br from-[#dcd7ff] via-[#f3d8ff] to-[#ffe1bb] text-5xl">✦</div>
    {/if}
  </div>
  <div class="p-6">
    <div class="flex items-center justify-between gap-3 text-[11px] font-bold uppercase tracking-[.15em] text-black/40">
      <span>{post.kind || 'article'}</span><span>{formatDate(post.published_at || post.created_at)}</span>
    </div>
    <h3 class="mt-4 text-2xl font-black leading-tight tracking-[-0.04em]">{post.title}</h3>
    {#if post.excerpt}<p class="mt-3 line-clamp-3 text-sm leading-6 text-black/55">{post.excerpt}</p>{/if}
  </div>
</a>
