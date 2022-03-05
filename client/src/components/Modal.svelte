<script>
  import { onMount } from "svelte";
  import { fade } from "svelte/transition";

  import { user } from "./../data/data-user";
  import { modal } from "./../data/data-modal";
  import { pins } from "../data/data-pins";
  import { message } from "../data/data-message";
  import { navigation } from "../data/data-navigation";

  import Thumbnail from "./Thumbnail.svelte";
  import Comments from "./Comments.svelte";
  import Form from "./Form.svelte";
  import ActionPanel from "./ActionPanel.svelte";

  $: like =
    $modal.data && $modal.data.aLikes
      ? $modal.data.aLikes.find(
          (currentLike) => currentLike.nUserID === $user.nUserID
        )
      : undefined;

  onMount(() => {
    console.log($modal.context);
    $modal.data && !$modal.context.includes("follow") ? handleGetPin() : null;

    // Push a history entry for the current page
    if ($modal.context === "display" || $modal.context === "update") {
      history.pushState(
        {
          href_to_show: $navigation.currentPage,
          modal: { open: $modal.open, data: $modal.data },
          user:
            $navigation.currentPage === "profile" ? $navigation.user : false,
        },
        "",
        $modal.data.nPinID ? `/pin=${$modal.data.nPinID}` : $modal.context
      );
    }
  });

  const handleSetMessage = (header, type) => {
    $message.type = "toast";
    $message.data.header = header;
    $message.data.type = type;
  };

  const handleGetPin = async () => {
    try {
      const url = `server/api-get-pin?pin_ID=${$modal.data.nPinID}`;
      let response = await fetch(url);

      let data = await response.json();

      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        $modal.data = data.data;
        $modal.open = !$modal.open;
        return;
      }

      $modal.data = data.data;

      handleGetComments();
      handleGetLikes();
      handleGetSaves();
    } catch (error) {
      console.log(error);
    }
  };

  const handleGetLikes = async () => {
    try {
      const url = `server/api-get-likes-pin?pin_ID=${$modal.data.nPinID}`;
      let response = await fetch(url);

      let data = await response.json();

      if (response.status !== 200) {
        return;
      }

      $modal.data.aLikes = data.data.aLikes;
      $modal.data.nLikes = data.data.nLikes;
    } catch (error) {
      console.log(error);
    }
  };

  const handlePostLike = async () => {
    try {
      let data = new FormData();
      data.append("csrf_token", $user.csrf_token);
      data.append("pin_ID", $modal.data.nPinID);
      const url = "server/api-post-like-pin";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      console.log(data.info);
      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        return;
      }

      $modal.data.aLikes = [
        ...$modal.data.aLikes,
        { nUserID: $user.nUserID, cUsername: $user.cCreatorUsername },
      ];

      $modal.data.nLikes++;

      // If we are currently viewing the liked pins in the logged users profile
      $pins.context === "liked"
        ? ($pins.data = [$modal.data, ...$pins.data])
        : null;

      // Making sure the store containing the pins in the user's boards is up to date when on the profile
      if (
        $navigation.currentPage === "profile" &&
        $navigation.user.nUserID === $user.nUserID
      ) {
        $navigation.user.boards.liked.aPins = [
          $modal.data,
          ...$navigation.user.boards.liked.aPins,
        ];
      }

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleDeleteLike = async () => {
    try {
      let data = new FormData();
      data.append("csrf_token", $user.csrf_token);
      data.append("pin_ID", $modal.data.nPinID);
      const url = "server/api-delete-like-pin";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      console.log(data.info);
      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        return;
      }

      $modal.data.aLikes = $modal.data.aLikes.filter(
        (likeByUser) => likeByUser.nUserID !== $user.nUserID
      );

      $modal.data.nLikes--;

      // If we are currently viewing the liked pins in the logged users profile
      $pins.context === "liked"
        ? ($pins.data = $pins.data.filter(
            (currentLike) => currentLike.nPinID !== $modal.data.nPinID
          ))
        : null;

      // Making sure the store containing the pins in the user's boards is up to date when on the profile
      $navigation.currentPage === "profile" &&
      $navigation.user.nUserID === $user.nUserID
        ? ($navigation.user.boards.liked.aPins =
            $navigation.user.boards.liked.aPins.filter(
              (currentLike) => currentLike.nPinID !== $modal.data.nPinID
            ))
        : null;

      $navigation.currentPage === "profile" &&
      $navigation.user.nUserID === $user.nUserID
        ? $navigation.user.boards.liked.nPins--
        : null;

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleGetComments = async () => {
    try {
      const url = `server/api-get-comments?pin_ID=${$modal.data.nPinID}`;
      let response = await fetch(url);

      let data = await response.json();

      if (response.status !== 200) {
        return;
      }

      $modal.data.aComments = data.data.aComments;
      $modal.data.nComments = data.data.nComments;
    } catch (error) {
      console.log(error);
    }
  };

  const handleCloseModal = () => {
    $modal.open = !$modal.open;
    // Push a history entry for the current page
    history.pushState(
      {
        href_to_show: $navigation.currentPage,
      },
      "",
      $navigation.currentPage === "home" ? "/" : $navigation.currentPage
    );
  };

  const handleGetSaves = async () => {
    try {
      const url = `server/api-get-saves-pin?pin_ID=${$modal.data.nPinID}`;
      let response = await fetch(url);

      let data = await response.json();

      if (response.status !== 200) {
        return;
      }

      $modal.data.aSaves = data.data.aSaves.filter(
        (save) => save.nUserID !== $user.nUserID
      );
    } catch (error) {
      console.log(error);
    }
  };
</script>

<div
  transition:fade={{ duration: 300 }}
  class="{$modal.context} modal fixed grid rounded-3xl bg-white w-full h-3/4 md:h-auto md:min-h-9/10 shadow-custom
    m-auto z-20 left-1/2 top-0 my-28 md:my-20 transform -translate-x-1/2 text-dark
    {$modal.context === 'display'
    ? 'grid md:grid-cols-2  max-w-screen-md'
    : $modal.context.includes('follow')
    ? 'max-w-screen-sm overflow-hidden'
    : 'max-w-screen-md'}"
  id="modal"
>
  {#if $modal.context === "display"}
    <div
      class="content visual rounded-tr-3xl rounded-tl-3xl md:rounded-bl-3xl bg-cover bg-center relative h-72 md:h-full"
      style={$modal.data
        ? `background-image: url('server/images/pins/${$modal.data.cFileName}.${$modal.data.cFileExtension}')`
        : ""}
    />
    <div
      class="content description py-2 px-4 md:py-5 md:px-5 overflow-scroll h-full"
    >
      <ActionPanel type="top modal" pin={$modal.data} />
      <div class="text-xs leading-tight md:pt-2">
        {#if $modal.data.cURL}
          <a
            class="text-2xs md:pt-2 underline"
            href={$modal.data.cURL}
            target="_blank"
            rel="noopener noreferrer"
          >
            {$modal.data.cURL.substring(
              $modal.data.cURL.indexOf(".") + 1,
              $modal.data.cURL.indexOf(".com" || ".dk")
            )}
          </a>
        {/if}
        <h2 class="text-2xl font-bold md:pt-4">{$modal.data.cTitle}</h2>
        {#if $modal.data.cDescription}
          <p class="text-xs">{$modal.data.cDescription}</p>
        {/if}
      </div>

      <div class="py-4">
        <Thumbnail context="user modal" data={$modal.data} />
      </div>

      <div
        class="grid justify-self-start justify-items-start items-center {$modal
          .data.nLikes <= 1
          ? 'w-1/2'
          : 'w-3/4'}"
      >
        <button
          class="grid justify-self-start p-1 items-center hover:bg-light rounded-full transition duration-300"
        >
          <span
            class="material-icons {like ? 'text-dark' : 'text-muted'}"
            on:click={like ? handleDeleteLike : handlePostLike}
          >
            {like ? "favorite" : "favorite_border"}
          </span>
        </button>
        {#if $modal.data.aLikes && $modal.data.aLikes.length !== 0}
          <p class="text-xs col-start-2 col-end-5">
            {like && $modal.data.nLikes > 2
              ? `You and ${$modal.data.nLikes - 1} other people`
              : like && $modal.data.nLikes === 2
              ? `You and ${$modal.data.nLikes - 1} other person`
              : like && $modal.data.nLikes === 1
              ? "You"
              : $modal.data.nLikes === 1
              ? $modal.data.aLikes[0].cUsername
              : $modal.data.nLikes === 2
              ? `${$modal.data.aLikes[0].cUsername} and ${
                  $modal.data.nLikes - 1
                } other person`
              : `${$modal.data.aLikes[0].cUsername} and ${
                  $modal.data.nLikes - 1
                } other people`} liked this pin
          </p>
        {/if}
      </div>

      {#if $modal.data.aComments}
        <Comments />
      {/if}
      <Form context="comment" />
      {#if ($modal.data.aSaves && $modal.data.aSaves.length !== 0) || $modal.saved}
        <div
          class="grid justify-self-start justify-items-start items-center pt-5 gap-1"
        >
          {#if $modal.data.aSaves && $modal.data.aSaves.length !== 0}
            {#if $modal.data.aSaves[0].nUserID === $user.nUserID}
              <Thumbnail context="user saved you" data={$user} />
            {:else}
              <Thumbnail context="user saved" data={$modal.data.aSaves[0]} />
            {/if}
          {:else}
            <Thumbnail context="user saved you" data={$user} />
          {/if}
          <p class="text-xs col-start-2 col-end-11">
            saved this pin to <span class="font-semibold">GRAPHIC DESIGN</span>
          </p>
        </div>
      {/if}
    </div>
  {/if}

  {#if $modal.context === "create" || $modal.context === "update"}
    {#if $modal.context === "update"}
      <Form context="update" />
    {:else}
      <Form context="create" />
    {/if}
  {/if}
  {#if $modal.context.includes("follow")}
    <div class="grid items-center pb-8 self-start gap-8 relative">
      <button
        on:click={handleCloseModal}
        class="absolute top-3 right-3 grid items-center justify-center text-dark rounded-full w-7 h-7 bg-white hover:bg-light-hover transition duration-300"
      >
        <span class="material-icons font-semibold md-18"> close </span>
      </button>
      <h2
        class="font-semibold text-lg grid capitalize bg-primary text-white text-center py-4"
      >
        {$modal.data.length}
        {$modal.context}
      </h2>
      <div
        class="grid overflow-scroll gap-3 justify-self-center max-h-screen-md"
      >
        {#if $modal.data && $modal.data.length !== 0}
          {#each $modal.data as followership}
            <Thumbnail context={$modal.context} data={followership} />
          {/each}
        {/if}
      </div>
    </div>
  {/if}
</div>
<div
  class="modal-overlay fixed top-0 left-0 w-full h-full cursor-zoom-out z-10 bg-dark-opaque "
  on:click={$modal.context === "update"
    ? () => ($modal.context = "display")
    : handleCloseModal}
>
  {#if !$modal.context.includes("follow")}
    <button
      class="fixed top-20 left-5 grid items-center justify-center text-dark rounded-full w-7 h-7 bg-white hover:bg-light-hover transition duration-300"
    >
      <span class="material-icons font-semibold md-18"> west </span>
    </button>
  {/if}
</div>

<style>
  .material-icons.md-18 {
    font-size: 18px;
  }
</style>
