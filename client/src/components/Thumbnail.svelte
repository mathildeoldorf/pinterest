<script>
  import { user } from "./../data/data-user";
  import { navigation } from "./../data/data-navigation";
  import { modal } from "../data/data-modal";
  import { message } from "../data/data-message";
  import { pins } from "../data/data-pins";
  import { loading } from "../data/data-loading";

  import ActionPanel from "./ActionPanel.svelte";
  import Image from "./Image.svelte";

  import { onMount } from "svelte";
  import { followerships } from "../data/data-followerships";

  export let context = "";
  export let data = {};

  $: following =
    $user.followerships && context === "followers"
      ? $user.followerships.aFollowing.find(
          (following) => following.nFolloweeID === data.nFollowerID
        )
      : $user.followerships && context === "following"
      ? $user.followerships.aFollowing.find(
          (following) => following.nFolloweeID === data.nFolloweeID
        )
      : $user.followerships && data.nCreatorID !== $user.nUserID
      ? $user.followerships.aFollowing.find(
          (following) => following.nFolloweeID === data.nCreatorID
        )
      : undefined;

  const handleSetMessage = (header, type) => {
    $message.type = "toast";
    $message.data.header = header;
    $message.data.type = type;
  };

  const handleGetUser = async () => {
    console.log(
      "getting public profile for" + context + $navigation.user.nUserID
    );
    try {
      let url;
      $navigation.user.nUserID === $user.nUserID
        ? (url = "server/api-get-user")
        : (url = `server/api-get-user?user_ID=${$navigation.user.nUserID}`);

      let response = await fetch(url);

      let data = await response.json();

      if (response.status !== 200) {
        console.log(data.info);
        // TODO: ADD message/error handling
        return;
      }

      console.log(data.data);
      $navigation.user ? ($navigation.user = data.data) : null;

      // Push a history entry for the current page
      history.pushState(
        { href_to_show: $navigation.currentPage, user: $navigation.user },
        "",
        $navigation.currentPage
      );
      console.log(history.state);
    } catch (error) {
      console.log(error);
    }
  };

  const handleNavigate = () => {
    // Resetting $navigation.user
    $navigation.user = {};

    // Resetting pins when navigating
    $pins.data = false;
    $pins.start = false;
    $loading.done = false;
    $pins.info = false;

    // Set current page to profile
    $navigation.currentPage = "profile";

    // If modal is open, close it
    $modal.open ? ($modal.open = false) : null;

    // If you are viewing a pin, and pressing the thumbnail of the creator, save that user into $navigation.user
    if (data.nCreatorID) {
      if (data.nCreatorID !== $user.nUserID) {
        $navigation.user.nUserID = data.nCreatorID;
        handleGetUser(context);
        return;
      }
      $navigation.user = $user;
    }

    // If you are viewing the list of followers and pressing the thumbnail, save that user into $navigation.user
    if (context === "followers") {
      $navigation.user.nUserID = data.nFollowerID;
      handleGetUser(context);
      return;
    }

    // If you are viewing the list of following and pressing the thumbnail, save that user into $navigation.user
    if (context === "following") {
      $navigation.user.nUserID = data.nFolloweeID;
      handleGetUser(context);
      return;
    }
  };

  const handleFollow = async () => {
    try {
      let followee_ID;

      let formData = new FormData();

      if (context === "followers") {
        console.log("Following from list of followers");
        // I want to follow the follower: Append the nFollowerID
        followee_ID = data.nFollowerID;
      }
      if (context === "following") {
        console.log("Following from list of following");
        // I want to follow the followee: Append the nFolloweeID
        if ($navigation.user.nUserID === $user.nUserID) {
          console.log("From the logged user's profile");
        } else {
          console.log("From another user's profile");
        }
        followee_ID = data.nFolloweeID;
      }
      if (data.nCreatorID) {
        console.log("Following from a pin");
        followee_ID = data.nCreatorID;
        console.log(followee_ID);
      }

      console.log(data);

      formData.append("csrf_token", $user.csrf_token);
      formData.append("followee_ID", followee_ID);

      const url = "server/api-post-followership";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: formData,
      });

      let status = response.status;
      response = await response.json();

      let followership = {};
      followership.nFolloweeID = followee_ID;
      followership.nFollowerID = $user.nUserID;
      followership.cUsername = data.cUsername;
      followership.cImage = data.cImage;

      if (status !== 200) {
        console.log(response.info);
        handleSetMessage(response.info, "error");
        return;
      }

      // console.log($user.followerships.aFollowing);

      // Making sure the store contaning the data for the followee's followerships is updated
      $followerships
        ? ($followerships.aFollowers = [
            followership,
            ...$followerships.aFollowers,
          ])
        : null;

      // Making sure the store contaning the data for the user's followerships is updated
      $user.followerships.aFollowing = [
        followership,
        ...$user.followerships.aFollowing,
      ];

      console.log($user.followerships.aFollowing);

      handleSetMessage(response.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleUnfollow = async () => {
    try {
      let formData = new FormData();
      let followee_ID;

      if (context === "followers") {
        console.log("Unfollowing from list of followers");
        // I want to unfollow the follower: Append the nFollowerID
        if ($navigation.user.nUserID === $user.nUserID) {
          console.log("From the logged user's profile");
          followee_ID = data.nFollowerID;
        } else {
          console.log("From another user's profile");
        }
        followee_ID = data.nFollowerID;
        console.log(followee_ID);
      }
      if (context === "following") {
        console.log("Unfollowing from list of following");
        // I want to unfollow the followee: Append the nFollowerID
        if ($navigation.user.nUserID === $user.nUserID) {
          console.log("From the logged user's profile");
        } else {
          console.log("From another user's profile");
        }
        followee_ID = data.nFolloweeID;
        console.log(followee_ID);
      }

      console.log(data);

      if (data.nCreatorID) {
        console.log("Unfollowing from a pin");
        followee_ID = data.nCreatorID;
        console.log(followee_ID);
      }

      formData.append("csrf_token", $user.csrf_token);
      formData.append("followee_ID", followee_ID);

      const url = "server/api-delete-followership";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: formData,
      });

      let status = response.status;
      response = await response.json();

      if (status !== 200) {
        console.log(response.info);
        handleSetMessage(response.info, "error");
        return;
      }

      console.log($user.followerships.aFollowing);

      $followerships
        ? ($followerships.aFollowers = $followerships.aFollowers.filter(
            (follower) => follower.nFollowerID !== $user.nUserID
          ))
        : null;

      // // Making sure the store contaning the data for the user's followerships is updated
      $user.followerships.aFollowing = $user.followerships.aFollowing.filter(
        (followership) => followership.nFolloweeID !== followee_ID
      );

      console.log($user.followerships.aFollowing);

      // Making sure the $modal.data is consistent
      if (
        (context === "following" || context === "followers") &&
        $navigation.user.nUserID === $user.nUserID
      ) {
        console.log("Updating data. We are in the logged user's profile");
        if (context === "following") {
          console.log("Updating aFollowing");
          $modal.data = $user.followerships.aFollowing;
        }
        if (context === "followers") {
          console.log("Updating aFollowers");
          $modal.data = $user.followerships.aFollowers;
        }
      }

      handleSetMessage(response.info, "success");
    } catch (error) {
      console.log(error);
    }
  };

  const handleGetFollowerships = async (type) => {
    console.log("getting followerships");
    let userID;

    // Setting the value of the userID parameter depending on which context
    type === "user modal" && data.nCreatorID !== $user.nUserID
      ? (userID = data.nCreatorID)
      : null;
    type === "profile" && $navigation.user.nUserID !== $user.nUserID
      ? (userID = data.nUserID)
      : null;

    try {
      let data = new FormData();
      let url = `server/api-get-followerships`;

      (type === "user modal" && userID && userID !== $user.nUserID) ||
      (type === "profile" && $navigation.user.nUserID !== $user.nUserID)
        ? (url += `?user_ID=${userID}`)
        : null;

      // // FETCH
      let response = await fetch(url);
      let status = response.status;

      response = await response.json();

      if (status !== 200) {
        console.log(response.info);
        return;
      }

      // If we are viewing a modal, where the creator is not the logged user OR a profile which is not the logged user's
      // then set $followerships
      // Else we are either in the logged user's profile or viewing the logged users pin in a modal
      // then set $user.followerships
      (type === "user modal" && userID && userID !== $user.nUserID) ||
      (type === "profile" && $navigation.user.nUserID !== $user.nUserID)
        ? ($followerships = response.data)
        : ($user.followerships = response.data);

      if ($followerships) console.log($followerships);
      if ($user.followerships) console.log($user.followerships);
    } catch (error) {
      console.log(error);
    }
  };

  const handleShowFollowers = () => {
    console.log("showing followers");
    $modal.open = true;
    $modal.context = "followers";
    $modal.data =
      $navigation.user.nUserID && $user.nUserID === $navigation.user.nUserID
        ? $user.followerships.aFollowers
        : $followerships.aFollowers;
  };

  const handleShowFollowees = () => {
    console.log("showing followees");
    $modal.open = true;
    $modal.context = "following";

    $modal.data =
      $navigation.user.nUserID && $navigation.user.nUserID === $user.nUserID
        ? $user.followerships.aFollowing
        : $followerships.aFollowing;
  };

  onMount(() => {
    if (context === "navigation" && !$user.followerships) {
      handleGetFollowerships("logged user");
    }

    // If we are seeing a thumbnail from a modal or in the profile, then get followerships of that user
    context === "user modal" ||
    (context === "profile" && $navigation.user.nUserID)
      ? handleGetFollowerships(context)
      : null;
  });
</script>

<!-- ############################## -->

<div
  class="user-thumbnail grid {context} {context === 'user modal' ||
  context.includes('follow')
    ? 'grid-cols-2'
    : context === 'profile' || context === 'settings'
    ? 'pb-5 md:pb-20'
    : context === 'user'
    ? 'md:pt-2'
    : ''}"
>
  <button
    type="button"
    on:click={context !== "profile" && context !== "settings"
      ? handleNavigate
      : null}
    class="grid items-center gap-2 {context === 'profile' ||
    context === 'settings'
      ? 'justify-self-center cursor-default'
      : context !== 'navigation'
      ? 'justify-self-start'
      : ''}"
  >
    <Image {context} {data} />
    {#if context.includes("user") || context.includes("profile") || context === "comment" || context.includes("follow")}
      <div
        class="content-description grid {context === 'profile' ||
        context === 'settings'
          ? 'text-center'
          : 'col-start-2 col-end-4 text-left'}"
      >
        {#if context === "profile"}
          <h1 class="font-bold text-4xl">{data.cUsername}</h1>
        {/if}
        <p
          class="text-xs {context === 'user'
            ? 'capitalize'
            : context === 'profile'
            ? 'font-light lowercase'
            : context === 'comment'
            ? 'text-2xs font-semibold capitalize'
            : 'font-semibold capitalize'}"
        >
          {#if context !== "comment" && context !== "user saved you"}
            {context === "profile" ? "@" : ""}{data.cUsername}
          {/if}
          {#if context === "user saved you"}
            you
          {/if}
        </p>
        {#if context === "profile" && $navigation.user.cDescription}
          <div class="grid items-center justify-self-center md:w-1/2">
            <p class="text-xs font-light italic grid justify-self-center py-3">
              {$navigation.user.cDescription}
            </p>
          </div>
        {/if}
        {#if context === "profile icon"}
          <p class="text-2xs font-light">{data.cEmail}</p>
        {/if}
        {#if context === "user modal" || context === "profile"}
          <div
            class="grid gap-3 {context === 'profile'
              ? 'grid-cols-2 justify-self-center'
              : ''}"
          >
            {#if $followerships || $user.followerships}
              <button
                disabled={$navigation.user.nUserID !== $user.nUserID &&
                $followerships
                  ? $followerships.aFollowers.length === 0
                  : $user.followerships.aFollowers.length === 0}
                type="button"
                class="grid text-xs {context === 'user modal'
                  ? 'font-light text-left cursor-default'
                  : ($navigation.user.nUserID !== $user.nUserID &&
                      $followerships &&
                      $followerships.aFollowers.length === 0) ||
                    ($navigation.user.nUserID === $user.nUserID &&
                      $user.followerships.aFollowers.length === 0)
                  ? 'cursor-default font-semibold'
                  : 'font-semibold'}"
                on:click={context === "profile" ? handleShowFollowers : null}
              >
                <!-- If we are viewing a pin in a modal that belongs to the logged user OR we are viewing the logged user's profile -->
                {#if (context === "user modal" && (data.nCreatorID === $user.nUserID || !data.nCreatorID)) || (context === "profile" && $navigation.user.nUserID === $user.nUserID)}
                  {$user.followerships.aFollowers.length} followers
                {/if}
                <!-- If we a viewing a pin in a modal that belongs to another user that has followerships -->
                {#if context === "user modal" && data.nCreatorID && data.nCreatorID !== $user.nUserID && $followerships}
                  {$followerships.aFollowers.length} followers
                {/if}
                <!-- If we are viewing a profile of another user that has followerships -->
                {#if context === "profile" && $navigation.user.nUserID !== $user.nUserID && $followerships}
                  {$followerships.aFollowers.length} followers
                {/if}
              </button>

              {#if context === "profile"}
                <button
                  disabled={$navigation.user.nUserID !== $user.nUserID &&
                  $followerships
                    ? $followerships.aFollowing.length === 0
                    : $navigation.user.nUserID === $user.nUserID &&
                      $user.followerships.aFollowing.length === 0}
                  type="button"
                  class="font-semibold text-xs {($navigation.user.nUserID !==
                    $user.nUserID &&
                    $followerships &&
                    $followerships.aFollowing.length === 0) ||
                  ($navigation.user.nUserID === $user.nUserID &&
                    $user.followerships.aFollowing.length === 0)
                    ? 'cursor-default font-semibold'
                    : ''}"
                  on:click={handleShowFollowees}
                >
                  {#if context === "profile" && $navigation.user.nUserID === $user.nUserID}
                    {$user.followerships.aFollowing.length} following
                  {/if}
                  {#if context === "profile" && $navigation.user.nUserID !== $user.nUserID && $followerships}
                    {$followerships.aFollowing.length} following
                  {/if}
                </button>
              {/if}
            {/if}
          </div>
        {/if}
      </div>
    {/if}
  </button>
  <!-- Thumbnail for user in the context of the modal -->
  {#if context === "user modal" && $user.nUserID !== data.nCreatorID && $user.nUserID !== data.nUserID}
    <button
      type="button"
      class="grid rounded-full py-2 px-4 items-center justify-self-end font-semibold text-xs transition duration-300 {following
        ? 'bg-light text-dark hover:bg-light-hover'
        : 'bg-primary text-white hover:bg-primary-hover'}"
      on:click={following ? handleUnfollow : handleFollow}
      >{following ? "Unfollow" : "Follow"}
    </button>
  {/if}
  <!-- Thumbnail for followers and followees -->
  {#if context.includes("follow")}
    <!-- User profiles except the logged user -->
    {#if $navigation.user.nUserID !== $user.nUserID}
      {#if context === "followers" && data.nFollowerID !== $user.nUserID}
        <button
          type="button"
          class="grid rounded-full py-2 px-4 items-center justify-self-end  
                    font-semibold text-xs transition duration-300 min-w-1/2 {following
            ? 'bg-light text-dark hover:bg-light-hover'
            : 'bg-primary text-white hover:bg-primary-hover'}"
          on:click={following ? handleUnfollow : handleFollow}
          >{following ? "Unfollow" : "Follow"}
        </button>
      {/if}
      {#if context === "following" && data.nFollowerID !== $user.nUserID}
        <button
          type="button"
          class="grid min-w-1/2 rounded-full py-2 px-4 items-center justify-self-end  
                    font-semibold text-xs transition duration-300 {following
            ? 'bg-light text-dark hover:bg-light-hover'
            : 'bg-primary text-white hover:bg-primary-hover'}"
          on:click={following ? handleUnfollow : handleFollow}
          >{following ? "Unfollow" : "Follow"}
        </button>
      {/if}
    {/if}
    <!-- The logged user's profile -->
    {#if $navigation.user.nUserID === $user.nUserID}
      <button
        type="button"
        class="grid rounded-full py-2 px-4 items-center justify-self-end font-semibold text-xs 
                transition duration-300 {following
          ? 'bg-light text-dark hover:bg-light-hover'
          : 'bg-primary text-white hover:bg-primary-hover'}"
        on:click={following ? handleUnfollow : handleFollow}
        >{following ? "Unfollow" : "Follow"}</button
      >
    {/if}
  {/if}
  {#if context === "profile"}
    <ActionPanel type="top profile" pin={undefined} />
  {/if}
</div>

<!-- ############################## -->
<style>
</style>
