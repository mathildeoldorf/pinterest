<script>
  import { user } from "./../data/data-user";
  import { modal } from "./../data/data-modal";
  import { pins } from "./../data/data-pins";
  import { message } from "./../data/data-message";
  import { navigation } from "../data/data-navigation";

  import { onMount } from "svelte";

  let data;
  let url;

  const handleCloseMessage = () => {
    $message.open = !$message.open;
    $message.data = undefined;
  };

  const handleSetMessage = (data) => {
    if ($message.type !== "toast") {
      $message.data.description = data.description;
      $message.data.header = data.header;
      $message.data.question = undefined;
      $message.options = undefined;
    } else {
      $message.data.header = data.header;
      $message.data.type = data.type;
      setTimeout(() => {
        handleRemoveMessage();
      }, 3000);
    }
  };

  const handleRemovePin = () => {
    console.log("removing pin");
    console.log($modal.open);
    $pins.data = $pins.data.filter((pin) => pin.nPinID !== $modal.data.nPinID);
    $modal.open = !$modal.open;
    history.state.modal = false;

    // Making sure the store containing the pins in the user's boards is up to date when on the profile
    $navigation.currentPage === "profile" &&
    $navigation.user.nUserID === $user.nUserID
      ? ($navigation.user.boards.owned.aPins =
          $navigation.user.boards.owned.aPins.filter(
            (currentPin) => currentPin.nPinID !== $modal.data.nPinID
          ))
      : null;

    $navigation.currentPage === "profile" &&
    $navigation.user.nUserID === $user.nUserID
      ? $navigation.user.boards.owned.nPins--
      : null;
  };

  const handleSetUrl = () => {
    if ($message.context === "user" && $message.action === "delete")
      url = "server/api-delete-user";

    if ($message.context === "pin" && $message.action === "delete")
      url = "server/api-delete-pin";
  };

  const handleAppendData = () => {
    data = new FormData();
    data.append("csrf_token", $user.csrf_token);

    if ($message.context === "user" && $message.action === "delete")
      data.append("user_ID", $user.nUserID);

    if ($message.context === "pin" && $message.action === "delete")
      data.append("pin_ID", $modal.data.nPinID);
  };

  const handleSubmit = async () => {
    try {
      handleSetUrl();
      handleAppendData();

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();
      console.log(data.info);

      if (response.status !== 200) {
        $message.type = "toast";
        handleSetMessage(data.info, "error");
        return;
      }

      if ($message.context === "user" && $message.action === "delete") {
        setTimeout(() => {
          $user = data.data;
          handleNavigate("log-in");
          handleCloseMessage();
          return;
        }, 3000);
      }

      if ($message.context === "pin" && $message.action === "delete") {
        $message.type = "toast";
        handleRemovePin();
      }

      handleSetMessage(data.info, "success");

      setTimeout(() => {
        handleCloseMessage();
      }, 3000);
    } catch (error) {
      console.log(error);
      return;
    }
  };

  const handleNavigate = (page) => {
    $navigation.currentPage = page;
    history.pushState(
      { href_to_show: page },
      "",
      `/${page !== "home" ? page : ""}`
    );
    $message.open = false;
  };

  const handleRemoveMessage = () => {
    setTimeout(() => {
      $message.type = false;
    }, 2000);
  };

  onMount(() => {
    if ($message.type === "toast") handleRemoveMessage();
  });
</script>

{#if $message.type && $message.type !== "toast"}
  <div
    class="{$message.type} modal fixed grid rounded-3xl bg-white w-full md:w-1/2 max-w-screen-md min-h-4/5 md:min-h-1/2 shadow-custom
    m-auto z-30 left-1/2 top-1/5 md:top-1/4 transform -translate-x-1/2 text-dark"
    id="modal"
  >
    <div
      class="content description px-5 md:px-20 py-10 grid relative text-center self-center gap-3"
    >
      <i
        class="fab fa-pinterest text-primary text-2xl hover:text-primary-hover transition duration-300"
        alt="logo"
      />
      <h2 class="mb-5 text-3xl font-semibold">{$message.data.header}</h2>
      {#if $message.data.subheader}
        <h3 class="text-xl font-semibold">{$message.data.subheader}</h3>
      {/if}
      {#if $message.data.description}
        <p class="text-xs">
          {$message.data.description}
        </p>
      {/if}
      {#if $message.data.question}
        <p class="text-xs pt-6">
          {$message.data.question}
        </p>
      {/if}
      {#if $message.options}
        <div class="mt-5 options grid content-center justify-self-center">
          <div class="grid grid-cols-2 gap-1">
            {#each $message.options as option}
              <button
                type="button"
                class="grid capitalize rounded-full py-2 px-2 items-center justify-self-end {option !==
                  'cancel' && option !== 'close'
                  ? 'bg-primary hover:bg-primary-hover text-white'
                  : 'bg-light hover:bg-light-hover text-dark'}  font-semibold text-xs  transition duration-300"
                on:click={option === "delete"
                  ? handleSubmit
                  : option === "log in"
                  ? () => handleNavigate("log-in")
                  : () => ($message.open = !$message.open)}
              >
                {option}
              </button>
            {/each}
          </div>
        </div>
      {/if}
    </div>
  </div>
  <div
    class="modal-overlay fixed top-0 left-0 w-full h-full cursor-zoom-out z-20 bg-dark-opaque"
    on:click={handleCloseMessage}
  >
    <button
      class="fixed left-5 top-20 grid items-center justify-center text-dark rounded-full w-7 h-7 bg-white hover:bg-light-hover transition duration-300"
    >
      <span class="material-icons font-semibold md-18"> west </span>
    </button>
  </div>
{/if}
{#if $message.type && $message.type === "toast"}
  <div
    class="toast fixed animate-toast w-1/2 md:w-1/4 z-30 -top-10 right-5 md:right-10 rounded-xl p-1 {$message
      .data.type === 'error'
      ? 'bg-primary'
      : 'bg-green-500'}"
  >
    <p class="text-xs text-white p-2">
      {$message.data.header}
    </p>
  </div>
{/if}

<style>
  .material-icons.md-18 {
    font-size: 18px;
  }
</style>
