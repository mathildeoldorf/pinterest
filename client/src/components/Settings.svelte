<script>
  import { navigation } from "./../data/data-navigation";

  import SettingsMenu from "./SettingsMenu.svelte";
  import Form from "./Form.svelte";

  export let context;
</script>

{#if $navigation.currentPage.includes("settings")}
  <div class="grid grid-cols-2-auto mb-2">
    <h1 class="font-semibold text-2xl md:text-4xl w-full capitalize">
      {$navigation.currentPage.substr(0, $navigation.currentPage.indexOf("?"))}
    </h1>
    <SettingsMenu {context} />
  </div>
  <div class="grid md:h-screen">
    <Form {context} />
  </div>
{/if}
