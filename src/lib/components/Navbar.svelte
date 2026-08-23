<script lang="ts">
  let { user = null, profile = null } = $props<{ user?: any; profile?: any }>();
</script>

<header class="sticky top-0 z-50 border-b border-black/5 bg-[#f6f4ee]/90 backdrop-blur-xl">
  <div class="mx-auto flex h-18 max-w-7xl items-center justify-between px-5 lg:px-8">
    <a href="/" class="flex items-center gap-3 font-black tracking-[-0.04em]">
      <span class="grid size-9 place-items-center rounded-2xl bg-black text-sm text-white">N</span>
      <span>Nova CMS</span>
    </a>
    <nav class="hidden items-center gap-7 text-sm font-semibold text-black/60 md:flex">
      <a class="hover:text-black" href="/#discover">Showcases</a>
      <a class="hover:text-black" href="/community">Community</a>
      <a class="hover:text-black" href="https://github.com/ephraimlifanjo/cms">Open source ↗</a>
    </nav>
    <div class="flex items-center gap-2">
      {#if user}
        <a class="rounded-full border border-black/10 px-4 py-2 text-sm font-bold hover:bg-white" href="/u/{profile?.username || ''}">{profile?.display_name || 'Profile'}</a>
        <a class="rounded-full bg-black px-4 py-2 text-sm font-bold text-white" href="/studio">Studio</a>
      {:else}
        <a class="hidden rounded-full px-4 py-2 text-sm font-bold sm:inline-flex" href="/auth/login">Connexion</a>
        <a class="rounded-full bg-black px-4 py-2 text-sm font-bold text-white" href="/auth/register">Créer mon espace</a>
      {/if}
    </div>
  </div>
</header>
