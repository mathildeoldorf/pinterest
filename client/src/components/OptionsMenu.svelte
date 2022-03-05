<script>
  import { user } from "./../data/data-user";
  import { modal } from "./../data/data-modal";
  import { pins } from "./../data/data-pins";
  import { message } from "./../data/data-message";
  import { options } from "./../data/data-options";

  export let saved;
  export let type;

  const handleCloseOptionsMenu = () => {
    $options = false;
  };

  const handleUpdatePin = () => {
    $modal.context = "update";
    handleCloseOptionsMenu();
  };

  const handleSetModalMessage = (data, context, options, action, type) => {
    handleCloseOptionsMenu();

    $message.open = true;

    if (context === "pin" && action === "delete") {
      data = {
        header: "Delete your pin",
        subheader: $modal.data.cTitle,
        description:
          "Deleting your pin means you won't be able to get the pin back. All the data about your pin will be deleted.",
        question: `Are you sure you want to delete your pin ${$modal.data.cTitle}?`,
      };
    }

    $message.context = context;
    $message.data = data;
    $message.options = options;
    $message.action = action;
    $message.type = type;
  };
</script>

{#if type === "menu"}
  <div
    id="options"
    class="absolute z-10 grid rounded-xl ring-light ring-opacity-10 ring-1 shadow-options bg-white w-44 md:-left-17 top-8 px-4 py-3"
  >
    {#if ($user && $modal.data && $user.nUserID === $modal.data.nCreatorID) || saved}
      <button
        on:click={handleUpdatePin}
        class="font-semibold text-xs capitalize pt-2 text-left"
      >
        <p>Edit pin</p>
      </button>
    {/if}
    {#if $user && $modal.data && $user.nUserID === $modal.data.nCreatorID}
      <button
        on:click={() =>
          handleSetModalMessage(
            {},
            "pin",
            ["delete", "cancel"],
            "delete",
            "warning"
          )}
        class="font-semibold text-xs capitalize pt-2 text-left"
      >
        <p>Delete pin</p>
      </button>
    {/if}
  </div>
{/if}
