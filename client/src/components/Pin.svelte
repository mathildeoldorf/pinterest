<script>
  import { modal } from "./../data/data-modal";
  import { pins } from "./../data/data-pins";

  import Thumbnail from "./Thumbnail.svelte";
  import ActionPanel from "./ActionPanel.svelte";

  export let pin = {};
  // export let context;

  const handleOpenModal = () => {
    $modal.open = !$modal.open;
    $modal.data = pin;
    $modal.context = "display";
  };
</script>

<!-- ############################## -->
<article id={pin.nPinID} class="pin h-full {$pins.context}">
  <div
    class="pin-thumbnail grid relative rounded-2xl bg-cover bg-no-repeat bg-center cursor-zoom-in h-80"
    style="background-image: url('server/images/pins/{pin.cFileName}.{pin.cFileExtension}')"
  >
    <div
      class="overlay relative md:bg-dark-opaque md:opacity-0 opacity-100 hover:opacity-100 text-white inset-0 w-full h-full 
      rounded-2xl transition duration-300"
    >
      <div
        class="actions absolute grid text-xs text-dark md:text-white top-0 left-0 w-full h-full p-3"
        on:click={handleOpenModal}
      >
        <ActionPanel type="top pin" {pin} />
        <ActionPanel type="bottom pin" {pin} />
      </div>
    </div>
  </div>
  <div class="text-sm leading-tight pt-2">
    <p class="text-sm md:text-2xs font-bold pb-2 md:pb-0">{pin.cTitle}</p>
    <Thumbnail context="user" data={pin} />
  </div>
</article>

<!-- ############################## -->
