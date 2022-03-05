<script>
  import { onMount } from "svelte";
  import { user } from "./data/data-user";
  import { navigation } from "./data/data-navigation";
  import { scroll, scrolledToBottom } from "./data/data-scroll";
  import { loading } from "./data/data-loading";

  import Navigation from "./components/Navigation.svelte";
  import Page from "./components/Page.svelte";

  let scrollOffset;
  let innerHeight;
  let cached = null;

  const handleAuth = async () => {
    try {
      const url = "server/api-get-authentication";
      const response = await fetch(url);
      const data = await response.json();
      $user = data.data;

      if (!$user) {
        handleNavigate("log-in");
        return;
      }

      handleNavigate("home");
    } catch (error) {
      console.log(error);
    }
  };

  const handleNavigate = (page) => {
    $navigation.currentPage = page;
    history.pushState(
      { href_to_show: page },
      "",
      `/${page !== "home" ? page : ""}`
    );
  };

  const handleScroll = (e) => {
    if (!cached) {
      setTimeout(() => {
        // Updating scroll to be equal to the innerheight of the window and the scroll offset
        scroll.updateScroll(innerHeight + scrollOffset);

        // Comparing the inner height of the window combined with the scroll offset
        // If it is bigger than or equal to the height of the document body, then we are in the bottom of the page
        if ($scroll >= document.body.offsetHeight) {
          console.log("You're at the bottom of the page");
          $scrolledToBottom = true;

          console.log($scrolledToBottom);
        } else {
          $scrolledToBottom = false;
        }

        // If we reach the top of the document with a scroll offset of 0, then reset $scroll
        if (scrollOffset === 0) {
          console.log("resetting");
          scroll.reset();
        }

        cached = null;
      }, 1000);
    }
    cached = e;
  };

  onMount(() => {
    handleAuth();
  });
</script>

<!-- Tracking scroll height and saving it into store - but only if there is a logged user and loading is not done -->
<svelte:window
  bind:innerHeight
  bind:scrollY={scrollOffset}
  on:scroll|passive={$user && !$loading.done ? (e) => handleScroll(e) : null}
/>

<!-- ############################## -->
<Navigation />
<Page />
