<script>
  import { onMount } from "svelte";

  import { user } from "./../data/data-user";
  import { modal } from "./../data/data-modal";
  import { navigation } from "./../data/data-navigation";
  import { options } from "./../data/data-options";
  import { pins } from "../data/data-pins";
  import { message } from "../data/data-message";
  import { followerships } from "../data/data-followerships";

  import OptionsMenu from "./OptionsMenu.svelte";
  import CSRF from "./CSRF.svelte";

  export let pin;
  export let type;
  let form;

  $: saved =
    $user.savedPins && pin
      ? $user.savedPins.find((savedPin) => savedPin.nPinID === pin.nPinID)
      : undefined;

  $: following =
    $user.followerships && $navigation.user.nUserID
      ? $user.followerships.aFollowing.find(
          (followee) => followee.nFolloweeID === $navigation.user.nUserID
        )
      : undefined;

  const handleToggleOptions = () => {
    $options = !$options;
  };

  const handleSetMessage = (header, type) => {
    $message.type = "toast";
    $message.data.header = header;
    $message.data.type = type;
  };

  const handleSave = async () => {
    console.log("saving pin", pin);

    try {
      let data = new FormData(form);
      data.append("pin_ID", pin.nPinID);
      const url = "server/api-post-save-pin";

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

      // Making sure the store contaning the data for the user's saved pins is updated
      $user.savedPins = [pin, ...$user.savedPins];

      // Making sure the store contaning the data for modal is updated with the new save
      $modal.data
        ? ($modal.data.aSaves = [...$modal.data.aSaves, data.data])
        : null;

      $modal.data ? console.log($modal.data.aSaves) : null;

      // If we are viewing the saved pins in the logged user profile
      $pins.context === "saved" ? ($pins.data = [pin, ...$pins.data]) : null;

      // Making sure the store containing the pins in the user's boards is up to date when on the profile
      $navigation.currentPage === "profile" &&
      $navigation.user.nUserID === $user.nUserID
        ? ($navigation.user.boards.saved.aPins = [
            pin,
            ...$navigation.user.boards.saved.aPins,
          ])
        : null;

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleUnsave = async () => {
    try {
      console.log("unsaving pin", pin);
      let data = new FormData(form);
      data.append("pin_ID", pin.nPinID);
      const url = "server/api-delete-save-pin";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        return;
      }

      // Making sure the store contaning the data for the user's saved pins is updated
      $user.savedPins = $user.savedPins.filter(
        (savedByUser) => savedByUser.nPinID !== pin.nPinID
      );

      // Making sure the store contaning the data for modal is updated
      $modal.data
        ? ($modal.data.aSaves = $modal.data.aSaves.filter(
            (currentSave) => currentSave.nUserID !== $user.nUserID
          ))
        : null;

      $modal.data ? console.log($modal.data.aSaves) : null;

      // If we are currently viewing the saved pins in the logged users profile
      $pins.context === "saved"
        ? ($pins.data = $pins.data.filter(
            (currentPin) => currentPin.nPinID !== pin.nPinID
          ))
        : null;

      // Making sure the store containing the pins in the user's boards is up to date when on the profile
      $navigation.currentPage === "profile" &&
      $navigation.user.nUserID === $user.nUserID
        ? ($navigation.user.boards.saved.aPins =
            $navigation.user.boards.saved.aPins.filter(
              (currentSave) => currentSave.nPinID !== pin.nPinID
            ))
        : null;

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleFollow = async () => {
    try {
      let data = new FormData(form);
      data.append("followee_ID", $navigation.user.nUserID);
      console.log("following user from profile");

      const url = "server/api-post-followership";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      let followership = {};
      followership.nFolloweeID = $navigation.user.nUserID;
      followership.nFollowerID = $user.nUserID;
      followership.cUsername = $user.cUsername;
      followership.cImage = $user.cImage;

      console.log(followership);

      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        return;
      }

      $followerships.aFollowers = [followership, ...$followerships.aFollowers];
      // // Making sure the store contaning the data for the user's followerships is updated
      $user.followerships.aFollowing = [
        followership,
        ...$user.followerships.aFollowing,
      ];

      console.log($user.followerships.aFollowing);

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleUnfollow = async () => {
    try {
      let data = new FormData(form);
      data.append("followee_ID", $navigation.user.nUserID);
      console.log("unfollowing user from pin");

      const url = "server/api-delete-followership";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        return;
      }

      console.log($user.followerships.aFollowing);

      $followerships.aFollowers = $followerships.aFollowers.filter(
        (follower) => follower.nFollowerID !== $user.nUserID
      );

      // // Making sure the store contaning the data for the user's followerships is updated
      $user.followerships.aFollowing = $user.followerships.aFollowing.filter(
        (followership) => followership.nFolloweeID !== $navigation.user.nUserID
      );

      console.log($user.followerships.aFollowing);

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleUpdateProfile = () => {
    $navigation.currentPage = "settings?option=public-information";
    $navigation.settingsOption = "public information";

    // Push a history entry for the current page
    history.pushState(
      { href_to_show: $navigation.currentPage, user: $navigation.user },
      "",
      $navigation.currentPage
    );
    console.log(history.state);
  };
</script>

{#if type.includes("top")}
  <form
    bind:this={form}
    on:submit|preventDefault
    class="top-panel grid self-start relative {type === 'top modal'
      ? 'grid-cols-2 gap-5'
      : type === 'top profile'
      ? 'grid-cols-2 gap-2 justify-self-center mt-3'
      : ''}"
  >
    <CSRF />
    {#if type === "top modal"}
      <div class="grid grid-cols-3 gap-3 justify-self-start items-center">
        {#if pin.nCreatorID === $user.nUserID}
          <button
            on:click={handleToggleOptions}
            class="grid items-center justify-center rounded-full w-7 h-7 bg-white"
          >
            <span class="material-icons md-18"> more_horiz </span>
          </button>
        {/if}
        <button
          class="grid items-center justify-center rounded-full w-7 h-7 bg-white"
        >
          <span class="material-icons md-18"> ios_share </span>
        </button>
        <button
          class="grid items-center justify-center rounded-full w-7 h-7 bg-white"
        >
          <span class="material-icons md-18"> link </span>
        </button>
      </div>
      {#if $options}
        <OptionsMenu {saved} type="menu" />
      {/if}
    {/if}
    {#if type === "top pin" || type === "top modal"}
      <div class="grid text-xs grid-cols-3">
        <button
          class="grid grid-cols-3 justify-items-start items-center gap-2 justify-self-start col-start-1 col-end-3"
          type="button"
          name="button"
        >
          <p class="font-semibold grid col-start-1 col-end-3">Saved pins</p>
          <i class="fas fa-angle-down " />
        </button>
        <button
          type="button"
          class="save grid rounded-full py-2 items-center w-full bg-primary font-semibold justify-self-end text-white hover:bg-primary-hover transition duration-300"
          on:click|stopPropagation={!saved ? handleSave : handleUnsave}
        >
          {!saved ? "Save" : "Unsave"}
        </button>
      </div>
    {/if}
    {#if type === "top profile"}
      <button
        class="grid w-full rounded-full py-2 px-4 items-center bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
      >
        Share
      </button>
      <button
        class="grid w-full rounded-full py-2 px-4 items-center bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
        on:click={$navigation.user.nUserID === $user.nUserID
          ? handleUpdateProfile
          : following
          ? handleUnfollow
          : handleFollow}
      >
        {$navigation.user.nUserID === $user.nUserID
          ? "Edit profile"
          : following
          ? "Unfollow"
          : "Follow"}
      </button>
    {/if}
  </form>
{/if}
{#if type === "bottom"}
  <div
    class="bottom-panel text-dark grid grid-cols-2 gap-2 self-end items-center"
  >
    {#if pin.cURL}
      <a
        href={pin.cURL}
        target="_blank"
        class="grid items-center justify-center h-7 col-start-1 col-end-2 rounded-full bg-white opacity-75 hover:opacity-100 transition duration-300"
      >
        <span class="material-icons md-18"> north_east </span>
      </a>
    {/if}
    <div class="grid col-start-2 col-end-2 grid-cols-2 ml-auto gap-1">
      <button
        class="grid items-center justify-center rounded-full w-8 h-8 bg-white opacity-75 hover:opacity-100 transition duration-300"
      >
        <span class="material-icons md-18"> ios_share </span>
      </button>
      <button
        class="grid items-center justify-center rounded-full w-8 h-8 bg-white opacity-75 hover:opacity-100 transition duration-300"
      >
        <span class="material-icons md-18"> more_horiz </span>
      </button>
    </div>
  </div>
{/if}

<style>
  .material-icons.md-18 {
    font-size: 18px;
  }
</style>
