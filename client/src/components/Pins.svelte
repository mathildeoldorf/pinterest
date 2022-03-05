<script>
  import { loading } from "./../data/data-loading";
  import { navigation } from "./../data/data-navigation";
  import { pins } from "./../data/data-pins";
  import { user } from "../data/data-user";
  import { modal } from "../data/data-modal";
  import { search } from "../data/data-search";
  import { scrolledToBottom } from "../data/data-scroll";

  import { onMount, afterUpdate } from "svelte";

  import Pin from "./Pin.svelte";
  import LoaderPins from "./LoaderPins.svelte";

  export let context;

  let url;

  const handleSetLoading = (type) => {
    console.log("setting loading ", type);
    type === "all"
      ? ($loading.all = true)
      : type === "pins"
      ? ($loading.pins = true)
      : ($loading.done = true);
  };

  const handleStopLoading = (type) => {
    console.log("stopping loading ", type);
    type === "all"
      ? ($loading.all = false)
      : type === "pins"
      ? ($loading.pins = false)
      : null;
  };

  const handleResetLoading = () => {
    $loading.done = true;
  };

  const setContext = (value) => {
    $pins.context = value;
    $pins.limit = false;
    $pins.data = false;
    $pins.start = false;
    $loading.done = false;
    $pins.info = false;
    handleSetLoading("pins");

    handleGetPins($pins.context);
  };

  const handleSetUrl = () => {
    if ($pins.context === "default") {
      url = `server/api-get-pins?limit_start=${$pins.start}&limit_interval=${$pins.interval}`;
    }
    if ($pins.context === "saved") {
      url = `server/api-get-saved-pins?limit_start=${$pins.start}&limit_interval=${$pins.interval}`;
    }
    if ($pins.context === "liked") {
      url = `server/api-get-liked-pins?limit_start=${$pins.start}&limit_interval=${$pins.interval}`;
    }
    if ($pins.context === "owned") {
      url = `server/api-get-pins-user?limit_start=${$pins.start}&limit_interval=${$pins.interval}`;
    }

    // If the profile is public, append the id of the user to the query
    if (
      $navigation.user.nUserID &&
      $user.nUserID !== $navigation.user.nUserID &&
      ($pins.context === "owned" ||
        $pins.context === "liked" ||
        $pins.context === "saved")
    ) {
      console.log("Not the logged user" + $navigation.user.nUserID);
      url += `&user_ID=${$navigation.user.nUserID}`;
    }
  };

  const handleGetPins = async () => {
    console.log("Getting pins", $pins.context, $user);
    // If the context is search, then set the pins array equal to the search results
    if ($pins.context === "search") {
      $pins.data = $search.results;
      $loading.all ? handleStopLoading("all") : null;
      $loading.pins ? handleStopLoading("pins") : null;
      !$loading.done ? handleSetLoading("done") : null;
      return;
    }

    !$pins.data && !$loading.pins ? handleSetLoading("all") : null;
    !$loading.pins && !$loading.all ? handleSetLoading("pins") : null;

    if (!$pins.start && $pins.start !== 0) {
      $pins.start = 0;
    } else {
      $pins.data ? ($pins.start = $pins.data.length) : $pins.start;
    }

    console.log($pins.start);

    try {
      handleSetUrl();

      let response = await fetch(url);
      let data = await response.json();

      if (response.status !== 200) {
        console.log(data.info);
        return;
      }

      console.log(data.data);
      if (data.data.length === 0) {
        $pins.data = data.data;
        $pins.info = data.info;

        // Stop loading when $pins.data is populated
        $loading.all ? handleStopLoading("all") : null;
        $loading.pins ? handleStopLoading("pins") : null;
        !$loading.done ? handleSetLoading("done") : null;
        return;
      }

      // If there a no pins data or if the starting point of loading pins is 0, then reset the pins array
      if (!$pins.data || $pins.start === 0) {
        console.log("loading for the first time");
        $pins.data = data.data;
        $pins.limit = data.limit;
        console.log(data.limit);
      } else {
        $pins.data = [...$pins.data, ...data.data];
      }

      // Setting new starting point for the next query of pins
      $pins.start = $pins.data.length;

      // Stop loading when $pins.data is populated
      $pins.data && $loading.all ? handleStopLoading("all") : null;
      $pins.data && $loading.pins ? handleStopLoading("pins") : null;

      // If we have reached the limit of pins for the feed
      if ($pins.start >= $pins.limit) {
        $pins.info = "No more pins to load";
        !$loading.done ? handleSetLoading("done") : null;
      }

      // Save all pins into a default array of pins, to load whenever you navigate to the feed
      if ($pins.context === "default") $pins.default = $pins.data;

      // If there is a message from $search then reset it
      $search.info ? ($search.info = false) : null;
    } catch (error) {
      console.log(error);
    }
  };

  const handleOpenModal = () => {
    $modal.open = !$modal.open;
    $modal.context = "create";
  };

  const handleGetBoardsInfo = async () => {
    try {
      let url = `server/api-get-boards`;

      // If the profile is public, append the id of the user to the query
      if (
        $navigation.user.nUserID &&
        $user.nUserID !== $navigation.user.nUserID &&
        ($pins.context === "owned" ||
          $pins.context === "liked" ||
          $pins.context === "saved")
      ) {
        console.log("not the logged user" + $navigation.user.nUserID);
        url += "?user_ID=" + $navigation.user.nUserID;
      }

      let response = await fetch(url);
      let data = await response.json();

      if (response.status !== 200) {
        return;
      }

      // If the context is either of the user's boards, then save the data into $navigation.user.boards
      if (
        data.data.owned !== [] &&
        data.data.liked !== [] &&
        data.data.saved !== [] &&
        ($pins.context === "saved" ||
          $pins.context === "liked" ||
          $pins.context === "owned")
      )
        $navigation.user.boards = data.data;
    } catch (error) {
      console.log(error);
    }
  };

  const handleGetSavedPins = async () => {
    try {
      console.log("Getting saved pins");
      let start = 0;

      const url = `server/api-get-saved-pins?limit_start=${start}`;

      // FETCH
      let response = await fetch(url);

      let data = await response.json();

      $user.savedPins = data.data;

      if (response.status !== 200) {
        console.log(data.info);
        // TODO: ADD message/error handling
        return;
      }
    } catch (error) {
      console.log(error);
    }
  };

  onMount(() => {
    $pins.context = context;

    // Context is search the get pins
    $pins.context === "search" ? handleGetPins() : null;

    // When visiting a profile when there is a $navigation.user.nUserID - get pins for that profile
    !$pins.start &&
    $navigation.currentPage === "profile" &&
    $navigation.user.nUserID
      ? handleGetPins()
      : null;

    // When visiting home feed - get pins for the home feed -
    // refresh the state of $pins, to refresh the feed after the user has performed a search
    !$pins.start &&
    $navigation.currentPage === "home" &&
    $pins.context !== "search"
      ? setContext($pins.context)
      : null;

    // When visiting a profile - get the boards of that profile
    $navigation.user.nUserID ? handleGetBoardsInfo() : null;

    // If there is a logged user and no saved pins, then fetch that data
    $user && !$user.savedPins ? handleGetSavedPins() : null;
  });

  afterUpdate(() => {
    // If loading of pins is not done and the scroll position of the document
    // is at the bottom, then load more pins
    if (!$loading.done && $scrolledToBottom) {
      console.log("Not done loading at the bottom of the page");
      handleSetLoading("pins");
      setTimeout(() => {
        handleGetPins();
      }, 1000);
      $scrolledToBottom = false;
    }
  });
</script>

<!-- ############################## -->
<button
  class="fixed bottom-10 z-10 grid justify-self-center {$modal.open
    ? 'invisible'
    : ''} {!$navigation.currentPage.includes('profile') ||
  $user.nUserID !== $navigation.user.nUserID
    ? 'right-10'
    : ''}"
  on:click={handleOpenModal}
>
  <span
    class="material-icons text-dark shadow-add rounded-full p-1 ring-1 ring-muted ring-opacity-20 
            hover:bg-dark hover:text-white bg-white transition duration-300
            {$navigation.currentPage.includes('profile') &&
    $user.nUserID === $navigation.user.nUserID
      ? 'md-48'
      : 'md-36'}"
  >
    add
  </span>
</button>
{#if $navigation.currentPage.includes("profile")}
  <div class="grid gap-1 md:gap-3 grid-cols-3 mb-3">
    {#if $navigation.user.boards}
      <button on:click={() => setContext("owned")}>
        <div
          class="w-full grid h-24 sm:h-36 md:h-48 lg:h-52 xl:h-64 rounded-xl overflow-hidden 
                    {$navigation.user.boards.owned.aPins.length > 1
            ? 'grid-cols-2'
            : ''}"
        >
          {#if $navigation.user.boards.owned.aPins.length !== 0}
            {#each $navigation.user.boards.owned.aPins as pin, i}
              {#if i < 3}
                <div
                  style="background-image: url('server/images/pins/{pin.cFileName}.{pin.cFileExtension}');"
                  class="bg-cover h-full {i === 0
                    ? 'row-start-1 row-end-3'
                    : ''}"
                />
              {/if}
            {/each}
          {:else}
            <div class="bg-light h-full rounded-xl overflow-hidden" />
          {/if}
        </div>
        <p class="font-semibold text-3xs md:text-sm text-left">
          {$navigation.user.nUserID === $user.nUserID
            ? "My "
            : $navigation.user.cUsername + "'s"} pins
        </p>
        <p class="font-light text-3xs md:text-2xs text-left">
          {$navigation.user.boards.owned.aPins.length} pin{$navigation.user
            .boards.owned.aPins.length === 0 ||
          $navigation.user.boards.owned.aPins.length > 1
            ? "s"
            : ""}
        </p>
      </button>
      <button on:click={() => setContext("saved")}>
        <div
          class="w-full grid h-24 sm:h-36 md:h-48 lg:h-52 xl:h-64 rounded-xl overflow-hidden {$navigation
            .user.boards.saved.aPins.length > 1
            ? 'grid-cols-2'
            : ''}"
        >
          {#if $navigation.user.boards.saved.aPins.length !== 0}
            {#each $navigation.user.boards.saved.aPins as pin, i}
              {#if i < 3}
                <div
                  style="background-image: url('server/images/pins/{pin.cFileName}.{pin.cFileExtension}');"
                  class="bg-cover h-full {i === 0
                    ? 'row-start-1 row-end-3'
                    : ''}"
                />
              {/if}
            {/each}
          {:else}
            <div class="bg-light h-full rounded-xl overflow-hidden" />
          {/if}
        </div>
        <p class="font-semibold text-3xs md:text-sm text-left">Saved pins</p>
        <p class="font-light text-3xs md:text-2xs text-left">
          {$navigation.user.boards.saved.aPins.length} pin{$navigation.user
            .boards.saved.aPins.length === 0 ||
          $navigation.user.boards.saved.aPins.length > 1
            ? "s"
            : ""}
        </p>
      </button>
      <button on:click={() => setContext("liked")}>
        <div
          class="w-full grid h-24 sm:h-36 md:h-48 lg:h-52 xl:h-64 rounded-xl overflow-hidden {$navigation
            .user.boards.liked.aPins.length > 1
            ? 'grid-cols-2'
            : ''}"
        >
          {#if $navigation.user.boards.liked.aPins.length !== 0}
            {#each $navigation.user.boards.liked.aPins as pin, i}
              {#if i < 3}
                <div
                  style="background-image: url('server/images/pins/{pin.cFileName}.{pin.cFileExtension}');"
                  class="bg-cover h-full {i === 0
                    ? 'row-start-1 row-end-3'
                    : ''}"
                />
              {/if}
            {/each}
          {:else}
            <div class="bg-light h-full rounded-xl overflow-hidden" />
          {/if}
        </div>
        <p class="font-semibold text-3xs md:text-sm text-left">Liked pins</p>
        <p class="font-light text-3xs md:text-2xs text-left">
          {$navigation.user.boards.liked.aPins.length} pin{$navigation.user
            .boards.liked.aPins.length === 0 ||
          $navigation.user.boards.liked.aPins.length > 1
            ? "s"
            : ""}
        </p>
      </button>
    {/if}
  </div>
{/if}
<section id="pins" class="pb-20 grid">
  {#if $pins.context !== "default" && $pins.context !== "search"}
    <div
      class="grid grid-cols-2 md:grid-cols-1 self-start justify-self-start gap-1 text-sm"
    >
      <h2 class="font-semibold">
        {#if $pins.context === "owned"}
          <span class="capitalize ">
            {$navigation.user.nUserID === $user.nUserID
              ? "My "
              : $navigation.user.cUsername + "'s"}
          </span>
        {/if}
        {#if $pins.context !== "owned"}
          <span class="capitalize">
            {$pins.context}
          </span>
        {/if} pins
      </h2>
      {#if $pins.data && $pins.data.length !== 0 && $pins.context !== "default"}
        <p>
          {$pins.data.length}
          {$pins.data.length === 1 ? "pin" : "pins"}
        </p>
      {/if}
    </div>
  {/if}
  {#if $pins.data}
    <div
      class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-5 gap-x-3 gap-y-6 w-full items-start max-h-full"
    >
      {#each $pins.data as pin}
        <Pin {pin} />
      {/each}
    </div>
    {#if $loading.pins}
      <LoaderPins />
    {/if}
    {#if $pins.info}
      <div class="py-20">
        <i
          class="fab fa-pinterest text-primary text-5xl transition duration-300 w-full text-center"
          alt="logo"
        />
        <p class="info text-xs text-center mt-3 pb-20">{$pins.info}</p>
      </div>
    {/if}
  {/if}
</section>

<!-- ############################## -->
<style>
  .material-icons.md-36 {
    font-size: 36px;
  }
  .material-icons.md-48 {
    font-size: 48px;
  }
</style>
