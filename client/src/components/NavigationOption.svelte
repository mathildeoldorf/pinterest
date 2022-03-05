<script>
  import { modal } from "../data/data-modal";
  import { navigation } from "./../data/data-navigation";
  import { user } from "./../data/data-user";
  import { pins } from "./../data/data-pins";
  import { search } from "./../data/data-search";
  import { URLparams } from "./../data/data-URLparams";
  import { loading } from "./../data/data-loading";

  import Search from "./Search.svelte";
  import Thumbnail from "./Thumbnail.svelte";

  export let option;
  export let context;

  const handleLogOut = async () => {
    try {
      let url = "server/api-get-log-out-user";
      let response = await fetch(url);
      let data = await response.json();
      console.log(data.info);

      $user = data.data;
      handleToggleSecondary();
      $search = {};
      $pins.start = false;

      $navigation.currentPage = "log-in";
      history.pushState({ href_to_show: "log-in" }, "", "/log-in");
    } catch (error) {
      console.log(error);
      return false;
    }
  };

  const handleToggleSecondary = () => {
    $navigation.showSecondary = !$navigation.showSecondary;
  };

  const handleNavigate = () => {
    // Resetting $navigation.user
    $navigation.user = {};

    // Resetting pins when navigating
    $pins.data = false;
    $pins.start = false;
    $loading.done = false;
    $pins.info = false;

    // If option is profile or settings, then save the logged user into $navigation.user
    if (option === "profile" || option === "settings") {
      $navigation.user = $user;
      console.log($navigation.user);
    }

    // If there are URLparams, then please reset them
    // $URLparams ? (window.location.search = "") : null;

    // Set current page to whatever option is chosen
    $navigation.currentPage = option;

    // If modal is open, close it
    $modal.open ? ($modal.open = false) : null;

    // If search is open, then reset state of $search
    $search ? ($search.open = false) : null;
    $search ? ($search.info = false) : null;
    $search ? ($search.results = false) : null;
    $search ? ($search.active = false) : null;
    $search ? ($search.termBefore = false) : null;
    $search && $search.term && $search.term.value
      ? ($search.term.value = "")
      : null;
    $search && $search.keywords ? ($search.keyword = []) : null;
    $search && $search.suggestions ? ($search.suggestions = []) : null;

    // If option includes information, then we are in one of the settings pages -
    // Then reset the current page to be settings and set $navigation.settingsOption to option, to display the relevant data
    if (option === "settings") {
      option = "public information";
      $navigation.settingsOption = "public information";
    }
    if (option.includes(" ")) {
      $navigation.currentPage = `${option.replace(/\s+/g, "-")}`;
    }
    if (option.includes("information")) {
      $navigation.settingsOption = option;
      $navigation.currentPage = `settings?option=${option.replace(
        /\s+/g,
        "-"
      )}`;
    }

    // If option is logo, then set current page to home
    if ($user && option === "logo") $navigation.currentPage = "home";

    // If option is logo or home, then set pins.data to default pins
    if (($user && option === "logo") || option === "home")
      $pins.data = $pins.default;

    if (!$user && (option === "logo" || option === "home"))
      $navigation.currentPage = "log-in";

    // If the secondary menu is open, then close it
    $navigation.showSecondary ? ($navigation.showSecondary = false) : null;

    // Push a history entry for the current page
    history.pushState(
      {
        href_to_show: $navigation.currentPage,
        user: $navigation.user ? $navigation.user : false,
      },
      "",
      $navigation.currentPage === "home" ? "/" : $navigation.currentPage
    );

    console.log(history.state);
  };
</script>

{#if context === "main"}
  {#if option === "logo"}
    <a
      class="cursor-pointer rounded-full w-8 h-8 grid items-center hover:bg-light justify-items-center transition duration-300"
      href={null}
      on:click={handleNavigate}
    >
      <i
        class="fab fa-pinterest text-primary text-2xl hover:text-primary-hover transition duration-300"
        alt="logo"
      />
    </a>
  {:else if option === "profile"}
    <a
      class="cursor-pointer rounded-full w-8 h-8 hover:bg-light grid justify-center items-center text-xl text-muted transition duration-300"
      href={null}
      on:click={handleNavigate}
    >
      <Thumbnail context="navigation" data={$user} />
    </a>
  {:else if option === "search"}
    <Search />
  {:else if option === "secondary"}
    <a
      class="cursor-pointer rounded-full w-6 h-6 hover:bg-light grid justify-center items-center text-muted transition duration-300"
      href={null}
      on:click={handleToggleSecondary}
    >
      <span class="material-icons md-18"> keyboard_arrow_down </span>
    </a>
  {:else if option === "log in" || option === "sign up"}
    <a
      class="cursor-pointer {$navigation.currentPage ===
      option.replace(/\s+/g, '-')
        ? 'bg-primary text-white'
        : 'text-dark hover:bg-light-hover transition duration-300 bg-light'}  text-center rounded-full px-3.5 py-2.5 font-semibold text-xs capitalize"
      href={null}
      on:click={handleNavigate}
    >
      {option}
    </a>
  {:else}
    <a
      class="cursor-pointer {$navigation.currentPage === option
        ? 'bg-dark text-white'
        : 'text-dark hover:bg-light transition duration-300'} rounded-full px-3.5 py-2.5 font-semibold text-xs capitalize"
      href={null}
      on:click={handleNavigate}
    >
      {option}
    </a>
  {/if}
{/if}
{#if context === "secondary"}
  {#if option === "profile"}
    <a class="cursor-pointer w-full" href={null} on:click={handleNavigate}>
      <Thumbnail context="profile icon" data={$user} />
    </a>
  {:else}
    <a
      class="cursor-pointer font-semibold text-xs capitalize pt-2"
      href={null}
      on:click={option === "log out" ? handleLogOut : handleNavigate}
    >
      {option}
    </a>
  {/if}
{/if}
{#if context === "settings"}
  <a
    class="cursor-pointer font-semibold text-3xs md:text-xs capitalize pt-2 {$navigation.settingsOption ===
    option
      ? 'underline'
      : ''}"
    href={null}
    on:click={handleNavigate}
  >
    {option}
  </a>
{/if}

<style>
  .material-icons.md-18 {
    font-size: 18px;
  }
</style>
