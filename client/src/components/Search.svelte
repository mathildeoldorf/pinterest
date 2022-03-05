<script>
  import { onMount } from "svelte";

  import { navigation } from "../data/data-navigation";
  import { user } from "./../data/data-user";
  import { search } from "./../data/data-search";
  import { pins } from "./../data/data-pins";
  import { loading } from "../data/data-loading";

  let form;
  let button;
  let timer;
  let typing;
  let message = {
    info: undefined,
    keywords: [],
  };

  onMount(() => {
    $user ? getRecentSearches() : null;
  });

  //************** Main functions
  const getRecentSearches = async () => {
    try {
      const url = "server/api-get-recent-searches-by-user";
      let response = await fetch(url);
      let data = await response.json();

      if (response.status !== 200) {
        return;
      }

      $search ? ($search.recent = data.data) : null;
    } catch (error) {
      console.log(error);
    }
  };

  const handleAwaitTerm = async (e) => {
    clearTimeout(timer);
    // console.log("Detecting new search, awaiting term", $search.term.value);
    // console.log($search.termBefore);
    if ($search.term.value.length >= 3) {
      if (e.type === "keyup") typing = false;
      if (e.type === "keypress") typing = true;

      // If there was a search term before recorded, then wait until the new search term does not inlude the old one
      if (
        $search.termBefore &&
        !$search.termBefore.includes($search.term.value)
      )
        typing = true;

      // If the user is still typing, then don't proceed to execute more code
      if (typing) {
        return;
      }

      // If the user is done typing
      timer = setTimeout(async () => {
        !typing ? handleResetProcessedKeywordsArray() : null;
        // Create array of keywords from term value
        !typing ? handleInitializeKeywordsArray() : null;
        !typing ? handleGetSuggestions() : null;
      }, 500);

      $search.suggestions ? handleResetSuggestions() : null;
      $search.keywords ? handleResetSuggestions() : null;
      $search.combinedKeyword ? handleResetCombinedKeyword() : null;
      $search.combinedSuggestion ? handleResetCombinedSuggestion() : null;
      return;
    }
  };

  const handleGetSuggestions = async () => {
    try {
      console.log("Getting suggestions for: ", $search.term.value);
      const url = `server/api-get-suggestions?keyword=${$search.term.value}`;
      let response = await fetch(url);
      let data = await response.json();

      // Initializing suggestions array
      !$search.suggestions ? handleResetSuggestions() : null;

      // If there is no suggestions for the search of this keyword
      if (response.status !== 200) {
        if (data.data.length === 0) {
          // First time the message is constructed add the info text to the message object
          handleSetMessage(data.info, data.keyword);
          // If no results from tKeyword, then evaluate the keywords, to see if they should be added
          handleEvaluateKeywords();
        }
        return;
      }

      // Reset message keywords when suggestions are found
      message.info ? handleResetMessage() : null;

      console.log("The suggestions are: ", data.data);

      // Loop the array of keywords from the database - which are automatically approved as suggestions
      data.data.forEach(async (keyword) => {
        // Add each keyword to the array of processed keywords
        handleAddProcessedKeyword(keyword.cKeyword);
        // Process each keyword to see if it should be added in the array of suggestions
        handleAddSuggestion(keyword);
      });

      // Loop the $search.keywords to see if some of them are not processed yet, meaning they didn't give any results in the database in tKeyword
      $search.keywords.forEach((keyword) => {
        let exists = $search.processedKeywords.some(
          (processedKeyword) => processedKeyword === keyword
        );

        if (exists === false) {
          console.log("The following keyword is not processed yet: ", keyword);
          // If the keyword is not processed, then evaluate it to see if it should be added to the suggestions
          handleEvaluateKeyword(keyword);
        }
      });

      // If there is more than one keyword in the suggestions array, then check if a combined suggestion can be made
      if ($search.suggestions.length > 1) {
        // Constructing a combined keyword whenever the suggestions from the search has more than one result,
        // meaning there is more than one word in the search term

        $search.suggestions.forEach((keyword, i) => {
          // Initialize the combined keyword as an empty string if it does not exist
          !$search.combinedKeyword ? ($search.combinedKeyword = "") : null;

          if (
            !$search.combinedKeyword.includes(keyword.cKeyword) &&
            keyword.cKeyword !== $search.combinedKeyword
          ) {
            handleConstructCombinedKeyword(keyword);
          }

          // Trim the last space in the string, when the end of the array has been reached
          if (i === $search.suggestions.length - 1) {
            handleTrimCombinedKeyword();
          }

          if (keyword.cKeyword === $search.combinedKeyword) {
            handleResetCombinedKeyword();
          }
        });

        // Evaluate the combined keyword to see if it
        if ($search.combinedKeyword) {
          handleEvaluateKeyword($search.combinedKeyword);
        }
      }
    } catch (error) {
      console.log(error);
    }
  };

  const handleSearch = async (e) => {
    // Reset results
    $search.results = false;
    $search.active = true;

    // If the recent searches array has not been initialized, then initiliaze it in order to save the search into the recent searches array
    !$search.recent ? ($search.recent = []) : null;

    // Navigate to the home page, if the current page is not home
    $navigation.currentPage !== "home"
      ? ($navigation.currentPage = "home")
      : null;

    // If the user doesn't submit the form, but triggers the function with the recent searches / suggestions buttons
    if (e.target !== form) {
      // If the button triggered the search, then update the value of the term accordingly
      $search.term.value = e.target.innerText;
      // Save the search term string as an array and replace the keywords array with that value, to loop the keywords for posting
      $search.keywords = $search.term.value.trim().split(" ");
    }

    // Save term for later comparison
    let term = $search.term.value;
    console.log("Performing search for: ", $search.term.value);

    try {
      const url = `server/api-get-search-pins?term=${$search.term.value}`;
      let response = await fetch(url);
      let data = await response.json();

      console.log(data.info);
      console.log(data.data);
      if (response.status !== 200) {
        // Close the search menu
        $search.open = false;
        // Set the info about the search
        $search.info = data.info;
        setTimeout(() => {
          $search.info = false;
        }, 2000);
        // Reset search term
        $search.term.value = "";
        // Reset pins data
        $pins.data = $pins.default;
        // Set loading to done to be able to fetch more pins
        $loading.done = false;
        return;
      }

      // Close the search menu
      $search.open = false;
      // Set the info about the search
      $search.info = data.info;
      // Set the results
      $search.results = data.data.aResults;

      // If there is results
      if ($search.results.length !== 0) {
        $search.keywords.forEach(async (keyword) => {
          // Check if the keyword exists in either the suggestions or recent array
          // If the keyword is found either place, then it has been approved for being saved in the DB
          let approved = $search.recent
            ? $search.recent.find((recent) => recent.cKeyword === keyword)
            : false;

          if (!approved) {
            approved = $search.suggestions.find(
              (suggestion) => suggestion.cKeyword === keyword
            );
          }

          // If the search term is approved
          if (approved) {
            console.log(
              "Search gave results and is approved for posting - Search term was: ",
              keyword
            );

            // Post each keyword to the DB
            keyword = await handlePostKeyword(keyword);

            // If something went wrong when posting the keyword, then do not proceed
            if (!keyword) return;

            // If the user doesn't submit the form, but triggers the function with the recent searches / suggestions buttons
            if (e.target !== form) {
              // Then adjust the keyword to be processed in the recent searches / suggestions buttons to have the full value of the button's innertext
              // to not add the individual keyword separately in recent
              keyword = { cKeyword: term };
            }

            // If there is no combined suggestion OR the search term is not equivalent to the combined suggestion
            // ... then add the keyword to recent
            if (
              !$search.combinedSuggestion ||
              ($search.combinedSuggestion &&
                term !== $search.combinedSuggestion.cKeyword)
            ) {
              handleRemoveFromRecentSearches(keyword);
              handleAddToRecentSearches(keyword);
            }
          } else {
            console.log("The keyword was not approved for posting: ", keyword);
          }
        });

        // If there is a combined suggestion and the search term is equivalent to the combined suggestion
        // Then add it to recent
        if (
          $search.combinedSuggestion &&
          term === $search.combinedSuggestion.cKeyword
        ) {
          handleRemoveFromRecentSearches($search.combinedSuggestion);
          handleAddToRecentSearches($search.combinedSuggestion);
        }
      }

      // In any case: Reset the value of the input field
      $search.term.value = "";
    } catch (error) {
      console.log(error);
    }
  };

  const handleEvaluateKeyword = async (keyword) => {
    console.log("Evaluating keyword: ", keyword);

    // When evaluating the keyword, the keyword can be characterized as processed - regardless of the result
    handleAddProcessedKeyword(keyword);

    try {
      const url = `server/api-get-search-pins?term=${keyword}`;
      let response = await fetch(url);
      let data = await response.json();

      // If no results
      if (response.status !== 200) {
        console.log(data.info);
        // Remove the keyword from the keywords array
        handleRemoveKeyword(keyword);
        return;
      }

      // If results
      console.log("found results when evaluating: ", data);

      if (keyword === $search.combinedKeyword) {
        keyword = await handleConstructCombinedSuggestion();
      }
      // Add the keyword to the suggestion array
      handleAddSuggestion(keyword);

      // If results then reset message
      handleResetMessage();
    } catch (error) {
      console.log(error);
    }
  };

  const handlePostKeyword = async (keyword) => {
    console.log("Posting new keyword");

    let data = new FormData();
    data.append("csrf_token", $user.csrf_token);
    data.append("keyword", keyword);

    try {
      const url = "server/api-post-keyword";
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      console.log(data.info);
      console.log(data);

      if (response.status !== 200) {
        return false;
      }

      return data.data.aKeyword;
    } catch (error) {
      console.log(error);
    }
  };

  //************** Util functions
  const handleToggleSearch = () => {
    // Toggle search state
    $search.open = !$search.open;
    // Reset term
    $search.open === false ? ($search.termBefore = false) : null;
    $search.open === false ? ($search.term.value = "") : null;
    // Reset message
    message.info ? handleResetMessage() : null;
    // Reset array with keywords and suggestions
    $search.keywords ? handleResetKeywords() : null;
    $search.suggestions ? handleResetSuggestions() : null;
    // Reset combined keywords and suggestions
    $search.combinedKeyword ? handleResetCombinedKeyword() : null;
    $search.combinedSuggestion ? handleResetCombinedSuggestion() : null;
  };
  // for recent
  const handleRemoveFromRecentSearches = (keyword) => {
    $search.recent = $search.recent.filter(
      (recent) => recent.cKeyword !== keyword.cKeyword
    );
    console.log("Removed ", keyword, ". Recent  is now: ", $search.recent);
  };
  const handleAddToRecentSearches = (keyword) => {
    $search.recent = [keyword, ...$search.recent];
    console.log("added ", keyword, ". Recent is now: ", $search.recent);
  };
  // for keywords
  const handleInitializeKeywordsArray = () => {
    $search.keywords = $search.term.value.trim().split(" ");
    console.log("initializing keywords array ", $search.keywords);
  };
  const handleResetKeywords = () => {
    $search.keywords = [];
  };
  const handleConstructCombinedKeyword = (keyword) => {
    console.log("constructing combined keyword");
    // OLD
    $search.combinedKeyword = keyword.cKeyword + " " + $search.combinedKeyword;
    // $search.combinedKeyword = $search.combinedKeyword.concat(
    //   " " + keyword.cKeyword
    // );
    console.log($search.combinedKeyword);
    return $search.combinedKeyword;
  };
  const handleResetCombinedKeyword = () => {
    $search.combinedKeyword = false;
  };
  const handleTrimCombinedKeyword = () => {
    $search.combinedKeyword = $search.combinedKeyword.trim();
  };
  const handleEvaluateKeywords = async () => {
    // Looping each keyword to evaluate if it should be added to tKeyword, skip evaluation if the keyword has been processed before
    console.log("Evaluating keywords ", $search.keywords);
    $search.keywords.forEach(async (keyword) => {
      let exists = $search.processedKeywords.some(
        (processedKeyword) => processedKeyword === keyword
      );
      if (exists === false && keyword !== "" && keyword.length > 3) {
        console.log(
          "The keyword does not exist in the processed keywords: ",
          keyword
        );
        handleEvaluateKeyword(keyword);
      }
    });
  };
  const handleRemoveKeyword = (keyword) => {
    $search.keywords = $search.keywords.filter(
      (current) => current !== keyword
    );
    console.log("Removed ", keyword, ". Keywords is now: ", $search.keywords);
  };
  // for processed keywords
  const handleResetProcessedKeywordsArray = () => {
    $search.processedKeywords = [];
    console.log($search.processedKeywords);
  };
  const handleAddProcessedKeyword = (keyword) => {
    let exists = $search.processedKeywords.some(
      (processedKeyword) => processedKeyword === keyword
    );

    if (exists === false) {
      $search.processedKeywords = [...$search.processedKeywords, keyword];
      console.log("Adding ", keyword, "Processed: ", $search.processedKeywords);
    } else {
      console.log("Processed keyword exists when evaluating: ", keyword);
    }
  };
  // for suggestions
  const handleResetSuggestions = () => {
    $search.suggestions = [];
  };
  const handleAddSuggestion = (keyword) => {
    console.log(keyword);
    let object = false;
    // If the keyword recieved is an object, it exists in tKeyword - then add the object as is in the suggestions array
    if (
      typeof keyword === "object" &&
      !Array.isArray(keyword) &&
      keyword !== null
    ) {
      object = true;
    }
    // If the keyword is not an object, it is a match when searching tPin - then construct an object to add in the suggestions array
    if (!object) {
      keyword = {
        cKeyword: keyword,
      };
    }
    // Check if the keyword exists in the suggestions array
    let exists = $search.suggestions.some((suggestion) =>
      suggestion.cKeyword.includes(keyword.cKeyword)
    );
    // If the keyword doesn't exist in the suggestion array, then add it to the suggestions array
    if (exists === false) {
      $search.suggestions = [...$search.suggestions, keyword];
      console.log("adding ", keyword, "Suggestions: ", $search.suggestions);
    }
  };
  const handleResetCombinedSuggestion = () => {
    $search.combinedSuggestion = false;
  };
  const handleConstructCombinedSuggestion = () => {
    console.log("constructing combined suggestion");
    $search.combinedSuggestion = {
      cKeyword: $search.combinedKeyword,
    };
    return $search.combinedSuggestion;
  };
  // for message
  const handleSetMessage = (info, keyword) => {
    message.info = info;
    message.keywords = keyword;
  };
  const handleResetMessage = () => {
    message.info = undefined;
    message.keywords = [];
  };
</script>

<div class="relative w-full pl-2.5">
  <span
    class="material-icons absolute mt-2.5 ml-5 text-muted md-18 font-semibold "
  >
    search
  </span>
  <form
    bind:this={form}
    on:submit|preventDefault|stopPropagation={handleSearch}
    autocomplete="off"
  >
    <input
      on:keyup|stopPropagation={(e) =>
        e.code != "Enter" ? handleAwaitTerm(e) : null}
      on:keypress|stopPropagation={(e) =>
        e.code != "Enter" ? handleAwaitTerm(e) : null}
      on:click={handleToggleSearch}
      bind:this={$search.term}
      class="rounded-full bg-light hover:bg-light-hover pl-10 pr-3.5 py-2.5 w-full text-xs text-muted transition duration-300"
      placeholder="Search"
      type="text"
    />
  </form>
  <div
    class="absolute md:w-full grid rounded-b-2xl md:mx-10 -left-20 md:-left-10 ring-light ring-opacity-10 ring-1 shadow-md bg-white top-12 px-4 py-3
        {$search && $search.open
      ? 'opacity-100 visible'
      : 'opacity-0 invisible'}"
  >
    {#if $search && $search.suggestions && $search.suggestions.length !== 0}
      <div class="suggestions mb-3 grid justify-self-start justify-items-start">
        {#each $search.suggestions as suggestion, key}
          <button
            bind:this={button}
            type="button"
            id={suggestion.nKeywordID}
            class="text-xs font-semibold"
            on:click|stopPropagation={(e) => handleSearch(e)}
          >
            {#if $search.combinedSuggestion && suggestion.cKeyword === $search.combinedSuggestion.cKeyword}
              {suggestion.cKeyword}
            {/if}
            {#if ($search.combinedSuggestion && suggestion.cKeyword !== $search.combinedSuggestion.cKeyword) || !$search.combinedSuggestion}
              {$search.keywords[key]}<span
                on:click|stopPropagation={button.click()}
                class="font-light"
                >{suggestion.cKeyword.includes($search.keywords[key])
                  ? suggestion.cKeyword.replace($search.keywords[key], "")
                  : ""}
              </span>
            {/if}
          </button>
        {/each}
      </div>
    {/if}
    {#if message.info}
      <p class="text-xs pb-2">
        {message.info}
        {#each message.keywords as keyword}
          {keyword}
        {/each}
      </p>
    {/if}
    <p class="text-xs">Recent searches</p>
    <div
      class="grid pt-1 justify-items-start grid-cols-16-auto justify-start gap-2"
    >
      {#if $search && $search.recent}
        {#each $search.recent as search}
          <button
            id={search.nKeywordID}
            type="button"
            on:click={(e) => handleSearch(e)}
            class="grid rounded-full py-1 px-2 items-center justify-self-start bg-light hover:bg-light-hover font-bold text-xs text-dark transition duration-300"
          >
            {search.cKeyword}
          </button>
        {/each}
      {:else}
        <p class="text-3xs">You have no recent searches yet</p>
      {/if}
    </div>
  </div>
</div>

<style>
  .material-icons.md-18 {
    font-size: 18px;
  }
</style>
