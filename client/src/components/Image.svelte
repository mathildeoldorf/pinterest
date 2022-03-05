<script>
  import { user } from "./../data/data-user";
  import { navigation } from "./../data/data-navigation";
  import { modal } from "../data/data-modal";
  import { message } from "../data/data-message";
  import { valid } from "../data/data-form";
  import { formElement } from "../data/data-form";

  import CSRF from "./CSRF.svelte";

  export let context;
  export let data;

  let hiddenFile;
  let error;
  $: temporary = false;

  let hover;

  const handleSetMessage = (header, type) => {
    $message.type = "toast";
    $message.data.header = header;
    $message.data.type = type;
  };

  const handleToggleHover = () => {
    hover = !hover;
  };

  const handleRemoveImage = () => {
    temporary = false;
    error = true;
    console.log("removing image");
    if (context === "profile" || context === "settings") {
      $user.fileURL = false;
      $user.file = false;
    }
    if (context === "create" || context === "update") {
      $modal.fileURL = false;
      $modal.file = false;
      compValidator.validateElement(hiddenFile);
      $valid = compValidator.validateForm($formElement);
    }
  };

  const handleSelectImage = (e) => {
    console.log(e.target);
    temporary = true;
    error = false;

    if (context === "profile" || context === "settings") {
      console.log("selecting user file");
      $user.file = e.target.files[0];
      $user.fileURL = URL.createObjectURL(e.target.files[0]);
      compValidator.validateElement(e.target);
    }
    if (context === "create" || context === "update") {
      console.log("selecting pin file");
      $modal.file = e.target.files[0];
      $modal.fileURL = URL.createObjectURL(e.target.files[0]);

      console.log($modal.file);
      compValidator.validateElement(e.target);
      $valid = compValidator.validateForm($formElement);
    }
  };

  const handleSubmit = async () => {
    $valid = false;
    console.log("submitting in Image");
    try {
      let url;

      let data = new FormData();
      if (context === "settings" || context === "profile")
        $user.fileURL !== undefined || $user.fileURL !== false
          ? data.append("image", $user.file)
          : data.delete("image");
      data.append("image", $user.file);
      data.append("csrf_token", $user.csrf_token);

      if (context === "settings" || context === "profile")
        url = "server/api-patch-user";

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

      if (context === "settings" || context === "profile") {
        for (const property in data.data) {
          console.log(property);
          $user[property] = data.data[property];
        }
        $user.fileURL = false;
      }

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
      return;
    }
  };
</script>

{#if context === "profile" && $navigation.user.nUserID === $user.nUserID}
  {#if $user.fileURL}
    <form
      on:submit|preventDefault|stopPropagation={handleSubmit}
      class="grid"
      autocomplete="off"
    >
      <CSRF />
      <button
        type="button"
        class="content-visual relative grid-span-1 rounded-full bg-cover bg-center ring-muted ring-opacity-30 ring-1 grid justify-self-center items-center transition duration-300 w-60 h-60"
        style={temporary
          ? `background-image: url(${$user.fileURL})`
          : `url(server/images/pins/${$user.fileURL})`}
      >
        <div class="grid grid-cols-2 justify-self-center gap-10">
          <div>
            <span
              class="material-icons cursor-pointer"
              on:click={handleRemoveImage}
            >
              delete
            </span>
          </div>
          <div>
            <button type="submit">
              <span class="material-icons cursor-pointer"> check_circle </span>
            </button>
          </div>
        </div>
      </button>
    </form>
  {:else}
    <button
      type="button"
      class="content-visual relative grid-span-1 rounded-full bg-cover bg-center ring-muted ring-opacity-30 ring-1 grid justify-self-center items-center transition duration-300 w-60 h-60"
      style={$user.cImage
        ? `background-image: url('server/images/users/${$user.cImage}')`
        : ""}
      on:mouseenter={context === "profile" &&
      $navigation.user.nUserID === $user.nUserID
        ? handleToggleHover
        : null}
      on:mouseleave={context === "profile" &&
      $navigation.user.nUserID === $user.nUserID
        ? handleToggleHover
        : null}
      on:click={() => hiddenFile.click()}
    >
      {#if !$user.cImage}
        <span
          class="material-icons text-muted transition duration-300 md-100 {hover
            ? 'opacity-10'
            : ''}"
        >
          person
        </span>
      {/if}
      <span
        class="material-icons absolute w-full cursor-pointer transition duration-300 {hover &&
        $navigation.user.nUserID === $user.nUserID
          ? 'opacity-100'
          : 'opacity-0'}"
        on:click|stopPropagation={() => hiddenFile.click()}
      >
        add_a_photo
      </span>
      <input
        name="image"
        id="image"
        bind:this={hiddenFile}
        accept="image/*"
        type="file"
        data-validate="file"
        data-min="50000"
        data-max="10485760"
        data-error="Please provide an image"
        data-help="Upload an image"
        alt="hiddenFile"
        style="display: none;"
        on:change={(e) => handleSelectImage(e)}
      />
      <span class="message text-3xs col-start-2 h-6 pt-1" id="image-message" />
    </button>
  {/if}
{:else if context.includes("settings")}
  {#if $user.fileURL}
    <button
      type="button"
      class="content visual relative bg-cover bg-center items-center bg-light rounded-3xl md:rounded-bl-3xl md:rounded-tl-3xl h-40 md:h-full"
      style={temporary
        ? `background-image: url(${$user.fileURL})`
        : `url(server/images/pins/${$user.fileURL})`}
    >
      <span
        class="material-icons absolute top-2 left-3"
        on:click={handleRemoveImage}
      >
        delete
      </span>
      <span class="material-icons" on:click={() => hiddenFile.click()}>
        add_a_photo
      </span>
      <input
        name="image"
        id="image"
        bind:this={hiddenFile}
        accept="image/*"
        type="file"
        data-validate="file"
        data-min="50000"
        data-max="10485760"
        data-error="Please provide an image"
        data-help="Upload an image"
        alt="hiddenFile"
        style="display: none;"
        required
        on:change={(e) => handleSelectImage(e)}
      />
    </button>
  {:else}
    <button
      type="button"
      class="content visual bg-cover bg-center items-center bg-light rounded-3xl md:rounded-bl-3xl md:rounded-tl-3xl h-40 md:h-full"
      style={$user.cImage
        ? `background-image: url('server/images/users/${$user.cImage}')`
        : ""}
    >
      <span class="material-icons" on:click={() => hiddenFile.click()}>
        add_a_photo
      </span>
      <input
        name="image"
        id="image"
        bind:this={hiddenFile}
        accept="image/*"
        type="file"
        data-validate="file"
        data-min="50000"
        data-max="10485760"
        data-error="Please provide an image"
        data-help="Upload an image"
        alt="hiddenFile"
        style="display: none;"
        on:change={(e) => handleSelectImage(e)}
      />
    </button>
  {/if}
{:else if context === "create" || context === "update"}
  {#if $modal.fileURL}
    <button
      id="image-button"
      type="button"
      class="content visual items-center bg-light rounded-tr-3xl rounded-tl-3xl md:rounded-bl-3xl bg-cover bg-center relative h-72 md:h-full"
      style={temporary
        ? `background-image: url(${$modal.fileURL})`
        : `url(server/images/pins/${$modal.fileURL})`}
    >
      <span
        class="material-icons absolute top-2 left-3"
        on:click={handleRemoveImage}
      >
        delete
      </span>
      <span class="material-icons" on:click={() => hiddenFile.click()}>
        add_a_photo
      </span>
      <input
        id="image"
        name="image"
        bind:this={hiddenFile}
        accept="image/*"
        type="file"
        alt="hiddenFile"
        style="display: none;"
        required
        data-validate="file"
        data-min="50000"
        data-max="10485760"
        data-error="Please provide a valid image"
        data-help="Upload an image"
        on:change={(e) => handleSelectImage(e)}
      />
      <!-- <p class="message text-2xs h-4 py-1" id="image-message">
        Upload an image
      </p> -->
    </button>
  {:else}
    <button
      id="image-button"
      type="button"
      class="content visual rounded-tr-3xl rounded-tl-3xl md:rounded-bl-3xl h-72 md:h-full bg-cover bg-center items-center bg-light {error
        ? 'ring-1 ring-primary'
        : ''}"
      style={$modal.data && context === "update"
        ? `background-image: url('server/images/pins/${$modal.data.cFileName}.${$modal.data.cFileExtension}')`
        : ""}
    >
      <span class="material-icons" on:click={() => hiddenFile.click()}>
        add_a_photo
      </span>
      <input
        id="image"
        name="image"
        class={context === "create" ? "error" : ""}
        bind:this={hiddenFile}
        accept="image/*"
        type="file"
        alt="hiddenFile"
        style="display: none;"
        required
        data-validate="file"
        data-min="50000"
        data-max="10485760"
        data-error="Please provide an image"
        data-help="Upload an image"
        on:change={(e) => handleSelectImage(e)}
      />
      <!-- <p class="message text-2xs h-4 py-1" id="image-message">
        Upload an image
      </p> -->
    </button>
  {/if}
{:else}
  <button
    class="{context} content-visual relative grid-span-1 rounded-full bg-cover bg-center ring-muted ring-opacity-30 ring-1 grid justify-self-center items-center transition duration-300
    {context === 'user modal' || context.includes('follow')
      ? 'w-10 h-10'
      : context === 'profile'
      ? 'w-60 h-60'
      : 'w-7 h-7'}
    "
    style={context === "user" && data.nCreatorID === $user.nUserID
      ? `background-image: url('server/images/users/${$user.cImage}')`
      : data.cImage
      ? `background-image: url('server/images/users/${data.cImage}')`
      : null}
  >
    {#if !data.cImage}
      <span
        class="material-icons text-muted transition duration-300 {context ===
        'user modal'
          ? 'md-36'
          : context === 'profile'
          ? 'md-100'
          : ''}"
      >
        person
      </span>
    {/if}
  </button>
{/if}

<style>
  .material-icons.md-100 {
    font-size: 100px;
  }
</style>
