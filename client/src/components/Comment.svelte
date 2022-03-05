<script>
  import { user } from "./../data/data-user";
  import { modal } from "./../data/data-modal";
  import { message } from "./../data/data-message";

  import Thumbnail from "./Thumbnail.svelte";
  import CSRF from "./CSRF.svelte";

  export let comment = {};

  let edit = false;

  let commentAfter;
  let commentBefore;

  const handleSetMessage = (header, type) => {
    $message.type = "toast";
    $message.data.header = header;
    $message.data.type = type;
  };

  const handleDeleteComment = async () => {
    try {
      let data = new FormData();

      data.append("csrf_token", $user.csrf_token);
      data.append("comment_ID", comment.nCommentID);
      data.append("pin_ID", $modal.data.nPinID);

      const url = "server/api-delete-comment";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      console.log(data.info);
      console.log(data);
      if (response.status !== 200) {
        handleSetMessage(data.info, "error");
        return;
      }

      edit = !edit;

      // Making sure the store contaning the data for modal is updated
      $modal.data.aComments = $modal.data.aComments.filter(
        (currentComment) => currentComment.nCommentID !== comment.nCommentID
      );

      $modal.data.nComments--;

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
      return;
    }
  };

  const handleUpdateComment = () => {
    edit = true;
    commentBefore = commentAfter.innerText;
    setTimeout(() => {
      open = false;
      commentAfter.focus();
      commentAfter.style.outline = "none";
    }, 200);
  };

  const handleCancelUpdate = () => {
    edit = !edit;
    comment.cComment = commentBefore;
  };

  const handleSaveUpdate = async (e) => {
    try {
      let data = new FormData();

      data.append("csrf_token", $user.csrf_token);
      data.append("comment_ID", comment.nCommentID);
      data.append("recipient_ID", comment.nRecipientID);
      data.append("comment", commentAfter.value);
      data.append("pin_ID", $modal.data.nPinID);

      const url = "server/api-patch-comment";

      // FETCH
      let response = await fetch(url, {
        method: "POST",
        body: data,
      });

      data = await response.json();

      edit = !edit;

      if (response.status !== 200) {
        comment.cComment = commentBefore;
        handleSetMessage(data.info, "error");
        return;
      }

      comment = data.data;

      // Making sure the store contaning the data for modal is updated with the new comment
      $modal.data.aComments = $modal.data.aComments.map((currentComment) => {
        if (currentComment.nCommentID === comment.nCommentID) {
          return comment;
        }
        return currentComment;
      });

      handleSetMessage(data.info, "success");
    } catch (error) {
      console.log(error);
      return;
    }
  };
</script>

<div class="comment">
  <div class="content grid grid-cols-10 py-1 justify-self-end">
    <Thumbnail context="comment" data={comment} />
    <div
      class="grid col-start-2 col-end-11 mr-1 items-center p-2 rounded-lg ring-muted ring-opacity-30 ring-1"
    >
      <p class="capitalize font-semibold text-2xs">
        {comment.cSenderUsername}
      </p>
      {#if !edit}
        <p class="text-3xs" bind:this={commentAfter}>
          {comment.cComment}
        </p>
      {/if}
      {#if edit}
        <form class="text-3xs" on:submit|preventDefault autocomplete="off">
          <CSRF />
          <input
            class="w-full"
            on:keypress={(e) =>
              e.key === "Enter" || e.key === 13 ? handleSaveUpdate(e) : null}
            bind:this={commentAfter}
            bind:value={comment.cComment}
            type="text"
            name="comment"
            id="comment"
            placeholder="Update your comment"
          />
        </form>
      {/if}
    </div>
  </div>

  {#if comment.nRecipientID === $user.nUserID || comment.nSenderID === $user.nUserID}
    <div class="options grid grid-cols-6 justify-end">
      {#if comment.nSenderID === $user.nUserID}
        <button
          class="col-start-5 col-end-6 text-2xs {!edit ? 'edit' : 'save'}"
          on:click={!edit ? handleUpdateComment : handleSaveUpdate}
        >
          {!edit ? "Edit" : "Save"}
        </button>
      {/if}
      <button
        class="col-start-6 col-end-7 text-2xs {!edit ? 'delete' : 'cancel'}"
        on:click={!edit ? handleDeleteComment : handleCancelUpdate}
      >
        {!edit ? "Delete" : "Cancel"}
      </button>
    </div>
  {/if}
</div>
