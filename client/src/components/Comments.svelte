<script>
  import { modal } from "./../data/data-modal";
  import Comment from "./Comment.svelte";

  $: open = true;
  let expanded = false;
</script>

<div class="comments grid md:py-3">
  <div class="grid grid-cols-2 gap-1 items-center justify-self-start py-2">
    <h3 class="font-semibold text-sm">
      {$modal.data.nComments} Comment{$modal.data.nComments > 1 ? "s" : ""}
    </h3>
    {#if $modal.data.nComments >= 1}
      <button
        class="grid justify-self-start items-center hover:bg-light rounded-full transition duration-300"
      >
        <span class="material-icons" on:click={() => (open = !open)}>
          {open ? "keyboard_arrow_down" : "keyboard_arrow_right"}
        </span>
      </button>
    {/if}
  </div>
  <div
    class="comments overflow-scroll max-h-32 {open ? 'visible' : 'invisible'}"
  >
    {#if $modal.data.nComments > 2 && !expanded}
      {#each $modal.data.aComments as comment, index}
        {#if index <= 1}
          <Comment {comment} />
        {/if}
      {/each}
    {:else}
      {#each $modal.data.aComments as comment}
        <Comment {comment} />
      {/each}
    {/if}
  </div>
  {#if $modal.data.nComments > 2}
    <button
      on:click={() => (expanded = !expanded)}
      class="text-3xs text-right pt-1 {open ? 'visible' : 'invisible'}"
    >
      {!expanded ? "View all" : "View less"}
    </button>
  {/if}
</div>
