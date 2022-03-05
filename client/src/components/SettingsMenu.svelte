<script>
  import { navigation } from "./../data/data-navigation";

  import NavigationOption from "./NavigationOption.svelte";

  export let context;
</script>

<nav
  id="settings"
  class="grid grid-cols-2 self-center md:self-end justify-self-end"
>
  {#each $navigation.settings as option}
    <NavigationOption {context} {option} />
  {/each}
</nav>
