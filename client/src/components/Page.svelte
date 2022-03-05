<script>
  import { onMount } from "svelte";

  import { loading } from "./../data/data-loading";
  import { user } from "./../data/data-user";
  import { navigation } from "./../data/data-navigation.js";
  import { modal } from "./../data/data-modal.js";
  import { message } from "../data/data-message";
  import { search } from "../data/data-search";
  import { URLparams } from "../data/data-URLparams";

  import Form from "./Form.svelte";
  import Pins from "./Pins.svelte";
  import Modal from "./Modal.svelte";
  import Message from "./Message.svelte";
  import Thumbnail from "./Thumbnail.svelte";
  import Settings from "./Settings.svelte";
  import Loader from "./Loader.svelte";
  import { pins } from "../data/data-pins";

  window.addEventListener("popstate", (e) => {
    $navigation.currentPage = e.state ? e.state.href_to_show : "home";
    if ($navigation.currentPage === "home") {
      console.log("showing home");
      $pins.context = "default";
      $pins.start = false;
    }
    if ($navigation.currentPage === "profile") {
      console.log(e.state.user);
      if (e.state && e.state.user) {
        $navigation.user = e.state.user;
      }
    }
    if (e.state && e.state.modal) {
      console.log(e.state.modal);
      if ($modal.data) {
        console.log("modal data and history data has been set");
        console.log("comparing");
        if ($modal.data !== e.state.modal.data) {
          console.log("Data does not match - setting modal data");
          $modal.data = e.state.modal.data;
        }
      }
    } else {
      $modal.open = false;
    }
  });

  const handleSetMessage = (header, type) => {
    $message.type = "toast";
    $message.data.header = header;
    $message.data.type = type;
  };

  const handleSetModalMessage = (data, context, options, action, type) => {
    $message.open = true;

    $message.context = context;
    $message.data = data;
    $message.options = options;
    $message.action = action;
    $message.type = type;
  };

  const handleActivateUser = async () => {
    try {
      let data = new FormData();
      data.append("user_ID", $URLparams.get("user_ID"));
      data.append("key", $URLparams.get("key"));
      let url = "server/api-post-activate-user";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        $navigation.currentPage = "log-in";
        history.pushState({ href_to_show: "log-in" }, "", "/log-in");
        return;
      }

      handleSetModalMessage(
        data.info,
        "user",
        ["log in", "close"],
        "activate",
        "success"
      );
    } catch (error) {
      console.log(error);
      return;
    }
  };

  onMount(() => {
    console.log($user);
    !$user ? ($URLparams = window.location.search) : null;
    if ($URLparams) {
      $URLparams = new URLSearchParams($URLparams);
      console.log($URLparams.get("action"));
      // Modifying the history entry to be "/" - params(stateObj, title, url)
      history.pushState({ href_to_show: "home" }, "", "/?" + $URLparams);
    } else {
      history.pushState({ href_to_show: "home" }, "", "/");
    }

    setTimeout(() => {
      !$user && $URLparams && $URLparams.get("action") === "activate"
        ? handleActivateUser()
        : null;
      // Modifying the history entry to be "/" - params(stateObj, title, url)
      if ($user) {
        history.pushState({ href_to_show: "home" }, "", "/");
      } else {
        history.pushState({ href_to_show: "log-in" }, "", "/log-in");
      }
    }, 100);
  });
</script>

<main
  id={$navigation.currentPage}
  class="page px-5 md:px-24 grid min-h-screen content-start {$user
    ? 'pt-20'
    : ''}"
  on:click={() =>
    $navigation.showSecondary ? ($navigation.showSecondary = false) : null}
>
  {#if $message.type === "toast"}
    <Message />
  {/if}
  {#if $message.open}
    <Message />
  {/if}
  {#if !$user}
    <Form
      context={$URLparams ? $URLparams.get("action") : $navigation.currentPage}
    />
  {:else}
    {#if $navigation.currentPage === "home"}
      {#if $search.info}
        <p class="text-xs font-light text-center pb-5">
          {$search.info}
        </p>
      {/if}
      {#if (!$search.active && !$search.results) || !$search}
        <Pins context="default" />
      {:else if $search.active && $search.results}
        <Pins context="search" />
      {:else if $search.active && !$search.results}
        <Pins context="default" />
      {/if}
    {/if}
    {#if $modal.open || (history.state && history.state.modal)}
      <Modal />
    {/if}
    {#if $navigation.currentPage === "profile" && $navigation.user.nUserID === $user.nUserID}
      <Thumbnail context="profile" data={$user} />
      <Pins context="owned" />
    {:else if $navigation.currentPage === "profile" && $navigation.user.nUserID !== $user.nUserID}
      <Thumbnail context="profile" data={$navigation.user} />
      <Pins context="owned" />
    {/if}
    {#if $navigation.currentPage.includes("settings")}
      <Settings context="settings" />
    {/if}
  {/if}
  {#if $loading.all}
    <Loader />
  {/if}
</main>
