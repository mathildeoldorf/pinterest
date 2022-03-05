<script>
  import { user } from "./../data/data-user";
  import { modal } from "./../data/data-modal";
  import { pins } from "./../data/data-pins";
  import { navigation } from "./../data/data-navigation";
  import { message } from "./../data/data-message";
  import { URLparams } from "./../data/data-URLparams";
  import { search } from "../data/data-search";
  import { valid } from "../data/data-form";
  import { formElement } from "../data/data-form";

  import Thumbnail from "./Thumbnail.svelte";
  import Image from "./Image.svelte";
  import CSRF from "./CSRF.svelte";

  export let context = "";

  let url;
  let data;
  let password;
  let old_password;
  let comment = "";
  let show;

  const handleValidateForm = () => {
    $valid = compValidator.validateForm($formElement);
  };

  const handleValidateElement = (element) => {
    compValidator.validateElement(element);
    handleValidateForm();
  };

  const handleInput = (element) => {
    compValidator.guideInput(element);
  };

  const handleSetMessage = (header, type) => {
    $message.type = "toast";
    $message.data.header = header;
    $message.data.type = type;
  };

  const handleSetModalMessage = (data, context, options, action, type) => {
    $message.open = true;

    if (context === "user" && action === "delete") {
      data = {
        header: "Delete your account",
        description:
          "Deleting your account means you won't be able to get your Pins or boards back. All your Pinterest account data will be deleted",
        question: "Are you sure you want to delete your account?",
      };
    }

    if (context === "pin" && action === "delete") {
      data = {
        header: `Delete your pin ${$modal.data.cTitle}`,
        description:
          "Deleting your pin means you won't be able to get the pin back. All the data about your pin will be deleted.",
        question: `Are you sure you want to delete your pin ${$modal.data.cTitle}?`,
      };
    }

    $message.context = context;
    $message.data = data;
    $message.options = options;
    $message.action = action;
    $message.type = type;
  };

  const handleChangePassword = () => {
    context = "settings-password";
    handleSubmit();
  };

  const handleSetUrl = () => {
    if (context === "log-in" || context === "activate")
      url = "server/api-post-log-in-user";
    if (context === "sign-up") url = "server/api-post-sign-up-user";
    if (context === "create") url = "server/api-post-pin";
    if (context === "update") url = "server/api-patch-pin";
    if (context === "delete") url = "server/api-delete-pin";
    if (context === "comment") url = "server/api-post-comment";
    if (context === "settings") url = "server/api-patch-user";
    if (context === "settings-password" || context === "change-password")
      url = "server/api-patch-password";
    if (context === "request-password")
      url = "server/api-post-request-password";
  };

  const handleAppendData = () => {
    console.log(context);
    data = new FormData($formElement);

    if (context === "create" || context === "update") {
      data.append("image", $modal.file);
    }
    if (context === "update" || context === "delete" || context === "comment") {
      data.append("pin_ID", $modal.data.nPinID);
    }
    if (context === "comment") {
      data.append("comment", comment);
      data.append("recipient_ID", $modal.data.nCreatorID);
    }
    if (context === "settings") {
      $user.fileURL !== undefined && $user.fileURL !== false
        ? data.append("image", $user.file)
        : data.delete("image");
      if (data.get("password")) data.delete("password");
      if (data.get("password")) data.delete("old_password");
    }

    if (context === "settings-password") {
      data = new FormData();
      data.append("csrf_token", $user.csrf_token);
      data.append("password", password);
      data.append("old_password", old_password);
    }

    if (context === "change-password") {
      data.append("key", $URLparams.get("key"));
    }
  };

  const handleAddPin = () => {
    // If in the home feed, then add the new pin to the array
    $navigation.currentPage === "home" && !$search.active
      ? ($pins.data = [data.data, ...$pins.data])
      : null;

    // If in the logged user's profile
    if (
      $navigation.currentPage === "profile" &&
      $navigation.user.nUserID === $user.nUserID
    ) {
      // Increment the count of owned pins
      $navigation.user.boards.owned.nPins++;

      // If viewing the user's owned pins, then add the new pin to the array
      if ($pins.context === "owned") $pins.data = [data.data, ...$pins.data];

      // If the board display is less than 3 pins, then add the new pin to it
      if ($navigation.user.boards.owned.aPins.length <= 3)
        $navigation.user.boards.owned.aPins = [
          data.data,
          ...$navigation.user.boards.owned.aPins,
        ];
    }
  };

  const handleUpdatePin = () => {
    for (const property in data.data) {
      $modal.data[property] = data.data[property];
    }
    $modal.context = "display";
    $modal.fileURL = false;
    console.log($modal.data);

    // If we are in the home feed OR the logged user's profile and viewing the user's pins
    if (
      $navigation.currentPage === "home" ||
      ($navigation.currentPage === "profile" &&
        $navigation.user.nUserID === $user.nUserID &&
        $pins.context === "owned")
    ) {
      $pins.data = $pins.data.map((pin) => {
        if (pin.nPinID === $modal.data.nPinID) {
          pin = $modal.data;
        }
        return pin;
      });

      console.log($pins.data);
    }
  };

  const handleCloseModal = () => {
    $modal.data = undefined;
    $modal.open = !$modal.open;
    $modal.fileURL = false;
    $modal.file = false;
  };

  const handleAddComment = () => {
    $modal.data.aComments = [data.data, ...$modal.data.aComments];
    comment = "";
    $modal.data.nComments++;
  };

  const handleCancelComment = () => {
    comment = "";
    show = false;
  };

  const handleUpdateUser = () => {
    for (const property in data.data) {
      console.log(property);
      $user[property] = data.data[property];
    }
    $user.fileURL = false;
  };

  const handleClosePasswordForm = () => {
    show = false;
    password = "";
    old_password = "";
  };

  const handleSubmit = async (e) => {
    $valid = false;
    try {
      console.log(context);
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
        context = "log-in";
        handleSetMessage(data.info, "error");
        return;
      }

      // Updating state
      if (context === "log-in") $user = data.data;
      if (context === "create") handleAddPin();
      if (context === "update") handleUpdatePin();
      if (context === "comment") handleAddComment();
      if (context === "settings" || context === "profile") handleUpdateUser();
      if (context === "settings-password") handleClosePasswordForm();

      // Sending feedback
      if (
        context === "request-password" ||
        context === "change-password" ||
        context === "sign-up"
      )
        handleSetModalMessage(
          data.info,
          "user",
          ["log in", "close"],
          context.replace("-", " "),
          "success"
        );

      if (context === "create") handleCloseModal();

      if (
        context === "create" ||
        context === "update" ||
        context === "delete" ||
        context === "comment" ||
        context.includes("settings")
      )
        handleSetMessage(data.info, "success");

      // Navigating
      if (context === "log-in" || context === "activate")
        handleNavigate("home");
      if (context === "settings") handleNavigate("profile");
      if (context === "settings-password") handleNavigate("settings");

      if (context === "change-password") $URLparams = false;
      if (
        context === "request-password" ||
        context === "activate" ||
        context === "change-password"
      ) {
        handleNavigate("log-in");
      }
    } catch (error) {
      console.log(error);
      return;
    }
  };

  const handleNavigate = (page) => {
    context = page;
    $navigation.currentPage = page;
    $navigation.user = $user;
    history.pushState(
      { href_to_show: page },
      "",
      `/${page !== "home" ? page : ""}`
    );
  };

  const handleForgottenPassword = () => {
    console.log("the user has forgotten their password");
    context = "request-password";
  };
</script>

<!-- ############################## -->
{#if $navigation.currentPage === "sign-up" || $navigation.currentPage === "log-in"}
  <form
    class="public {context} grid justify-items-center m-auto py-10 w-1/2 rounded-3xl shadow-custom z-10 relative top-52 min-h-4/5"
    autocomplete="off"
    bind:this={$formElement}
    on:submit|preventDefault={handleSubmit}
  >
    <i
      class="fab fa-pinterest text-primary text-2xl hover:text-primary-hover transition duration-300"
      alt="logo"
    />
    <h1 class="font-semibold text-3xl pb-2">Welcome to Pinterest</h1>
    <h2 class="text-xs font-light capitalize">
      {context.replace("-", " ")}
    </h2>

    <div class="w-3/5 grid">
      {#if context !== "change-password"}
        <input
          id="email"
          class="w-full ring-1 ring-light px-3 py-2 rounded-xl text-xs mt-2 focus:outline-none focus:ring-dark-opaque"
          type="email"
          name="email"
          placeholder="Email"
          data-validate="email"
          data-min="6"
          data-max="50"
          data-error="Please provide a valid e-mail"
          data-help="Example: a@a.com"
          required
          autocomplete="off"
          on:blur={(e) => handleValidateElement(e.target)}
          on:pointerdown={(e) => handleInput(e.target)}
        />
        <p class="message text-3xs h-4 py-1" id="email-message" />
      {/if}
      {#if ($navigation.currentPage === "log-in" && context !== "request-password") || context === "change-password" || $navigation.currentPage === "sign-up"}
        <input
          id="password"
          class="w-full ring-1 ring-light px-3 py-2 rounded-xl text-xs mt-2 focus:outline-none focus:ring-dark-opaque"
          type="password"
          name="password"
          placeholder="Password"
          data-validate="string"
          data-min="8"
          data-max="20"
          data-allowed="n.,- "
          data-error="Password must be between 8 and 20 characters"
          data-help="Password must be between 8 and 20 characters"
          required
          autocomplete="new-password"
          on:blur={(e) => handleValidateElement(e.target)}
          on:pointerdown={(e) => handleInput(e.target)}
        />
        <span class="message text-3xs h-4 py-1" id="password-message" />
      {/if}
      <input
        id="repeat-password"
        class="w-full ring-1 ring-light px-3 py-2 rounded-xl text-xs mt-2 focus:outline-none focus:ring-dark-opaque {$navigation.currentPage ===
          'sign-up' || context === 'change-password'
          ? 'visible'
          : 'invisible'}"
        type="password"
        name="repeat_password"
        placeholder="Repeat password"
        data-validate="repeat-password"
        data-min="8"
        data-max="20"
        data-allowed="n.,- "
        data-error="Your passwords must match"
        data-help="Type your password again"
        required={$navigation.currentPage === "sign-up" ||
        context === "change-password"
          ? true
          : false}
        autocomplete="new-password"
        on:blur={(e) => handleValidateElement(e.target)}
        on:pointerdown={(e) => handleInput(e.target)}
      />
      <span
        class="message text-3xs h-4 py-1 {$navigation.currentPage ===
          'sign-up' || context === 'change-password'
          ? 'visible'
          : 'invisible'}"
        id="repeat-password-message"
      />
      <button
        class="text-2xs font-semibold mt-3 {$navigation.currentPage ===
          'log-in' && !context.includes('password')
          ? 'visible'
          : 'invisible'}"
        type="button"
        on:click={handleForgottenPassword}
      >
        Forgotten your password?
      </button>
      <button
        type="submit"
        class="capitalize grid rounded-full p-2 mb-2 mt-3 w-full items-center justify-self-center  
        font-semibold text-xs transition duration-300 {!$valid
          ? 'bg-light hover:bg-light-hover text-dark cursor-default'
          : 'bg-primary hover:bg-primary-hover text-white'}"
        disabled={!$valid}
      >
        {!context.includes("password")
          ? $navigation.currentPage.replace("-", " ")
          : context.replace("-", " ")}
      </button>
      {#if !context.includes("password")}
        <button
          class="text-2xs"
          type="button"
          on:click={() =>
            handleNavigate(
              $navigation.currentPage === "sign-up" ? "log-in" : "sign-up"
            )}
        >
          {$navigation.currentPage === "sign-up"
            ? "Already a user? Log in"
            : "Not a user yet? Sign up"}
        </button>
      {/if}
    </div>
  </form>
  <div class="modal-overlay fixed top-0 left-0 w-full h-full bg-white" />
{:else if context === "create" || context === "update"}
  <form
    class="grid md:grid-cols-2 {context}"
    bind:this={$formElement}
    on:submit|preventDefault|stopPropagation={handleSubmit}
    autocomplete="off"
  >
    <CSRF />
    <Image {context} data={$modal.data} />
    <div
      class="content description whitespace-pre-wrap outline-none py-2 px-5 md:py-5 grid"
    >
      <div class="grid content-start">
        <div class="md:py-2">
          <Thumbnail
            context="user modal"
            data={context === "create" ? $user : $modal.data}
          />
        </div>
        <div
          class="text-xs leading-tight pt-1 md:pt-2 h-24 md:h-full overflow-scroll md:overflow-auto"
        >
          <p class="message h-4 py-1" id="image-message">
            {context === "create" ? "Start by uploading an image" : ""}
          </p>

          <label for="title" class="grid grid-cols-1/4-3/4 items-center gap-y-2"
            ><p>Title</p>
            <input
              id="title"
              class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
              type="text"
              name="title"
              required
              placeholder="Add your title"
              data-validate="string"
              data-min="2"
              data-max="100"
              data-allowed="n.,- "
              data-error="Please provide a title"
              value={$modal.context === "update" ? $modal.data.cTitle : null}
              on:blur={(e) => handleValidateElement(e.target)}
              on:pointerdown={(e) => handleInput(e.target)}
            />
            <span class="message text-3xs col-start-2" id="title-message" />
          </label>
          <label
            for="description"
            class="grid grid-cols-1/4-3/4 items-start content-start  gap-y-2"
            ><p>Description</p>
            <textarea
              id="description"
              rows="4"
              class="w-full ring-1 ring-light px-3 py-2 rounded-xl"
              type="text"
              name="description"
              placeholder="Add your description"
              data-validate="string"
              data-min="2"
              data-max="255"
              data-allowed="n.,- "
              data-error="Description has to be between 2 and 255 characters"
              value={$modal.context === "update" && $modal.data.cDescription
                ? $modal.data.cDescription
                : null}
              on:blur={(e) => handleValidateElement(e.target)}
              on:pointerdown={(e) => handleInput(e.target)}
            />
            <span class="message text-3xs" id="description-message" />
          </label>
          <label for="url" class="grid grid-cols-1/4-3/4 items-center gap-y-2"
            ><p class="grid">Website</p>
            <input
              id="url"
              class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
              type="text"
              name="url"
              placeholder="Add your URL for the website"
              data-validate="url"
              data-min="8"
              data-max="255"
              data-error="Please submit a valid URL"
              data-help="Example: https://www.pinterest.com"
              value={$modal.context === "update" && $modal.data.cURL
                ? $modal.data.cURL
                : null}
              on:blur={(e) => handleValidateElement(e.target)}
              on:pointerdown={(e) => handleInput(e.target)}
            />
            <span class="message text-3xs col-start-2" id="url-message" />
          </label>
        </div>
      </div>
      <div
        class="options grid {context === 'update'
          ? 'grid-cols-2'
          : ''} content-end"
      >
        {#if context === "update"}
          <button
            type="button"
            class="grid rounded-full py-2 px-2 items-center justify-self-start bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
            on:click={() =>
              handleSetModalMessage(
                {},
                "pin",
                ["delete", "cancel"],
                "delete",
                "warning"
              )}
          >
            Delete
          </button>
        {/if}
        <div class="grid grid-cols-2 justify-self-end gap-1">
          <button
            type="button"
            class="grid rounded-full py-2 px-2 items-center justify-self-end bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
            on:click={() =>
              context === "update"
                ? ($modal.context = "display")
                : ($modal.open = !$modal.open)}
          >
            Cancel
          </button>
          <button
            type="button"
            class="capitalize grid rounded-full py-2 px-2 items-center justify-self-end font-semibold text-xs 
            transition duration-300 {!$valid
              ? 'bg-light hover:bg-light-hover text-dark cursor-default'
              : 'bg-primary hover:bg-primary-hover text-white'}"
            disabled={!$valid}
            on:click={handleSubmit}
          >
            {$modal.context}
          </button>
        </div>
      </div>
    </div>
  </form>
{:else if context === "comment"}
  <!-- on:keypress|stopPropagation={(e) =>
          e.key === "Enter" || e.key === 13
            ? handleSubmit(e)
            : console.log(comment)} -->
  <form
    bind:this={$formElement}
    on:submit|preventDefault|stopPropagation={handleSubmit}
    class="grid"
    autocomplete="off"
  >
    <CSRF />
    <div class="grid grid-cols-10 py-3">
      <Thumbnail context="comment" data={$user} />
      <input
        bind:value={comment}
        id="comment"
        class="grid col-start-2 col-end-11 mr-1 items-center text-2xs w-full p-2 rounded-full ring-muted ring-opacity-30 ring-1 focus:outline-none focus:ring-dark-opaque"
        type="text"
        name="comment"
        placeholder="Add a comment"
        data-validate="string"
        data-min="2"
        data-max="255"
        data-allowed="n.,- "
        data-error="Your comment must be between 2 and 255 characters"
        required
        on:blur={(e) => handleValidateElement(e.target)}
        on:pointerdown={(e) => handleInput(e.target)}
        on:focus={() => {
          show = !show;
        }}
      />
      <span
        class="message text-3xs h-4 py-1 col-start-2 col-end-11 {show
          ? 'visible'
          : 'invisible'}"
        id="comment-message"
      />
    </div>
    <div
      class="options grid grid-cols-2 justify-self-end gap-1 {show
        ? 'visible'
        : 'hidden'}"
    >
      <button
        type="button"
        class="grid rounded-full py-2 px-2 items-center bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
        on:click={handleCancelComment}
      >
        Cancel
      </button>
      <button
        type="submit"
        class="grid rounded-full py-2 px-2 items-center font-semibold text-xs transition duration-300 {!$valid
          ? 'bg-light hover:bg-light-hover text-dark cursor-default'
          : 'bg-primary hover:bg-primary-hover text-white'}"
        disabled={!comment ? true : false}
        on:click|preventDefault|stopPropagation={handleSubmit}
      >
        Done
      </button>
    </div>
  </form>
{:else if context.includes("settings")}
  <form
    class="grid md:grid-cols-2 max-h-4/5 md:overflow-hidden relative {context}"
    bind:this={$formElement}
    on:submit|preventDefault={handleSubmit}
    autocomplete="off"
  >
    <CSRF />
    <p
      class="grid content-center message left-2 bottom-2 absolute rounded-xl bg-white p-4 z-30 text-2xs h-4"
      id="image-message"
    >
      You can also change your profile image
    </p>
    <Image {context} data={$user} />
    <div
      class="content description whitespace-pre-wrap outline-none grid md:px-5"
    >
      <div class="grid content-start">
        <h2
          class="capitalize font-semibold text-xl md:text-2xl pt-2 text-center md:pt-0 md:text-left"
        >
          {$navigation.settingsOption}
        </h2>
        <div
          class="text-xs items-center grid grid-cols-2-auto gap-x-1 justify-self-start pb-5 pt-2"
        >
          <span class="material-icons-outlined text-primary"> info </span>
          <p>
            {$navigation.settingsOption === "public information"
              ? "People visiting your profile will see the following info"
              : "Set your login preferences and manage your account here."}
          </p>
        </div>
        <div class="text-xs leading-tight grid min-h-full">
          {#if $navigation.settingsOption === "public information"}
            <label for="first_name" class="grid grid-cols-1/4-3/4 items-center">
              <p>First name</p>
              <input
                id="first-name"
                class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
                type="text"
                name="first_name"
                placeholder="Add your first name."
                data-validate="string"
                data-min="2"
                data-max="50"
                data-allowed="- "
                data-error="Please provide your first name with a maximum of 50 characters"
                value={$user.cFirstName}
                on:blur={(e) => handleValidateElement(e.target)}
                on:pointerdown={(e) => handleInput(e.target)}
              />
              <span
                class="message text-3xs col-start-2 h-6 pt-1"
                id="first-name-message"
              />
            </label>
            <label for="last_name" class="grid grid-cols-1/4-3/4 items-center">
              <p>Last name</p>
              <input
                id="last-name"
                class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
                type="text"
                name="last_name"
                placeholder="Add your last name."
                data-validate="string"
                data-min="2"
                data-max="50"
                data-allowed="- "
                data-error="Please provide your last name with a maximum of 50 characters"
                value={$user.cLastName}
                on:blur={(e) => handleValidateElement(e.target)}
                on:pointerdown={(e) => handleInput(e.target)}
              />
              <span
                class="message text-3xs col-start-2 h-6 pt-1"
                id="last-name-message"
              />
            </label>
            <label
              for="description"
              class="grid grid-cols-1/4-3/4 items-start content-start"
            >
              <p>Description</p>
              <textarea
                id="description"
                rows="4"
                class="w-full ring-1 ring-light px-3 py-2 rounded-xl"
                type="text"
                name="description"
                placeholder="Describe yourself in a few words to spark the interest of others."
                data-validate="string"
                data-min="2"
                data-max="255"
                data-allowed="n.,- "
                data-error="Description has to be between 2 and 255 characters"
                value={$user.cDescription ? $user.cDescription : null}
                on:blur={(e) => handleValidateElement(e.target)}
                on:pointerdown={(e) => handleInput(e.target)}
              />
              <span
                class="message text-3xs col-start-2 h-6 pt-1"
                id="description-message"
              />
            </label>
            <label for="url" class="grid grid-cols-1/4-3/4 items-center">
              <p class="grid">Website</p>
              <input
                id="url"
                class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
                type="text"
                name="url"
                placeholder="Add a link to drive traffic to your website."
                data-validate="url"
                data-min="8"
                data-max="255"
                data-allowed="n.,- "
                data-error="Please submit a valid URL"
                data-help="Example: https://www.pinterest.com"
                value={$user.cURL}
                on:blur={(e) => handleValidateElement(e.target)}
                on:pointerdown={(e) => handleInput(e.target)}
              />
              <span
                class="message text-3xs col-start-2 h-6 pt-1"
                id="url-message"
              />
            </label>
            <label for="username" class="grid grid-cols-1/4-3/4 items-center">
              <p>Username</p>
              <input
                id="username"
                class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
                type="text"
                name="username"
                required
                placeholder="Add your username. Choose wisely so others can find you."
                data-validate="string"
                data-min="2"
                data-max="50"
                data-error="Please provide your username"
                data-allowed="n.,- "
                data-help="Choose wisely and be original"
                value={$user.cUsername}
                on:blur={(e) => handleValidateElement(e.target)}
                on:pointerdown={(e) => handleInput(e.target)}
              />
              <span
                class="message text-3xs col-start-2 h-6 pt-1"
                id="username-message"
              />
            </label>
          {/if}
          {#if $navigation.settingsOption === "account information"}
            <label for="email" class="grid grid-cols-1/4-3/4 items-center">
              <p>Email</p>
              <input
                id="email"
                class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
                type="text"
                name="email"
                required
                placeholder="Add your email."
                data-validate="email"
                data-min="2"
                data-max="50"
                data-error="Please provide a valid email; a@a.com"
                data-help="Example: a@a.com"
                value={$user.cEmail}
                autocomplete="off"
                on:blur={(e) => handleValidateElement(e.target)}
                on:pointerdown={(e) => handleInput(e.target)}
              />
              <span
                class="message text-3xs col-start-2 h-6 pt-1"
                id="email-message"
              />
            </label>
            <label for="password" class="grid grid-cols-1/4-3/4 items-center">
              <p>{show ? "New password" : "Change password"}</p>
              <input
                id="password"
                bind:value={password}
                on:mousedown={() => (show = !show)}
                class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
                type="password"
                name="password"
                placeholder="Change password"
                data-validate="string"
                data-min="8"
                data-max="50"
                data-allowed="n.,- "
                data-error="Your password must be between 8 and 20 characters"
                data-help="Password must be between 8 and 20 characters"
                autocomplete="new-password"
                required={show}
                on:blur={(e) => handleValidateElement(e.target)}
                on:pointerdown={(e) => handleInput(e.target)}
              />
              <span
                class="message text-3xs col-start-2 h-6 pt-1 {!show
                  ? 'opacity-0'
                  : ''}"
                id="password-message"
              />
            </label>
            {#if show}
              <label
                for="repeat_password"
                class="grid grid-cols-1/4-3/4 items-center"
              >
                <p>Repeat password</p>
                <input
                  id="repeat_password"
                  class="w-full ring-1 ring-light px-3 py-2 rounded-xl text-xs mt-2 focus:outline-none focus:ring-dark-opaque"
                  type="password"
                  name="repeat_password"
                  placeholder="Repeat password"
                  data-validate="repeat-password"
                  data-min="8"
                  data-max="20"
                  data-allowed="n.,- "
                  data-error="Your passwords must match"
                  data-help="Type your password again"
                  required={show}
                  autocomplete="new-password"
                  on:blur={(e) => handleValidateElement(e.target)}
                  on:pointerdown={(e) => handleInput(e.target)}
                />
                <span
                  class="message text-3xs col-start-2 h-6 pt-1"
                  id="repeat_password-message"
                />
              </label>
              <label
                for="old_password"
                class="grid grid-cols-1/4-3/4 items-center"
              >
                <p>Old password</p>
                <input
                  id="old_password"
                  bind:value={old_password}
                  class="w-full ring-1 ring-light px-3 py-2 rounded-xl focus:outline-none focus:ring-dark-opaque"
                  type="password"
                  name="old_password"
                  placeholder="Type your old password"
                  required={show}
                  data-validate="string"
                  data-min="8"
                  data-max="50"
                  data-allowed="n.,- "
                  data-error="Your old password must be between 8 and 20 characters"
                  data-help="Password must be between 8 and 20 characters"
                  autocomplete="current-password"
                  on:blur={(e) => handleValidateElement(e.target)}
                  on:pointerdown={(e) => handleInput(e.target)}
                />
                <span
                  class="message text-3xs col-start-2 h-6 pt-1"
                  id="old_password-message"
                />
              </label>
              <label for="change-password" class="grid">
                <div class="grid grid-cols-2 gap-1 justify-self-end">
                  <button
                    type="button"
                    class="grid rounded-full py-2 px-2 items-center justify-self-end bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
                    on:click={() => (show = !show)}
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    class="grid rounded-full py-2 px-2 items-center justify-self-end
                  font-semibold text-xs transition duration-300 {!$valid
                      ? 'bg-light hover:bg-light-hover text-dark cursor-default'
                      : 'bg-primary hover:bg-primary-hover text-white'}"
                    disabled={!$valid}
                    on:click={handleChangePassword}
                  >
                    Change password
                  </button>
                </div>
              </label>
            {/if}
            <div class="md:pb-20 pb-6 pt-6 md:pt-0">
              <label
                for="delete-account"
                class="grid grid-cols-2 md:grid-cols-1/4-3/4 items-center gap-x-3"
              >
                <p>Do you want to delete your account?</p>
                <button
                  type="button"
                  class="grid rounded-full py-2 px-2 items-center md:justify-self-start bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
                  on:click={() =>
                    handleSetModalMessage(
                      {},
                      "user",
                      ["delete", "cancel"],
                      "delete",
                      "warning"
                    )}
                >
                  Delete account
                </button>
              </label>
            </div>
          {/if}
        </div>
      </div>
      <div class="options grid content-end md:justify-self-end">
        <div class="grid gap-1 pb-8 md:pb-0 {$valid ? 'grid-cols-2' : ''}">
          {#if $valid}
            <button
              type="button"
              class="grid rounded-full py-2 px-2 items-center justify-self-end bg-light hover:bg-light-hover font-semibold text-xs text-dark transition duration-300"
              on:click={() => handleNavigate("profile")}
            >
              Cancel
            </button>
          {/if}
          <button
            type="button"
            class="capitalize grid rounded-full py-2 px-2 items-center md:justify-self-end font-semibold text-xs 
            transition duration-300 md:col-start-2 {!$valid
              ? 'bg-light hover:bg-light-hover text-dark cursor-default'
              : 'bg-primary hover:bg-primary-hover text-white'}"
            disabled={!$valid}
            on:click={handleSubmit}
          >
            Update
          </button>
        </div>
      </div>
    </div>
  </form>
{/if}
