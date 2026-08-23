<script lang="ts">
  import '../app.css';
  import Navbar from '$lib/components/Navbar.svelte';
  import Footer from '$lib/components/Footer.svelte';
  let { data, children } = $props();
</script>

<Navbar user={data.user} profile={data.profile} />
{@render children()}
<Footer />
