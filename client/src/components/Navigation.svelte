<script>
    import { navigation } from "./../data/data-navigation";
    import { user } from "./../data/data-user";
    import { search } from "./../data/data-search";

    import NavigationOption from "./NavigationOption.svelte";

    const handleToggleSearch = () => {
        $search.open = !$search.open;
        if (!$search.open) {
            $search.termBefore = false;
            $search.term.value = "";
            $search.suggestions = [];
            $search.keywords = [];
            $search.combinedKeyword = false;
            $search.combinedSuggestion = false;
        }
    };
</script>

<div
    class="modal-overlay z-10 fixed top-0 left-0 w-full h-full bg-dark-opaque {$user &&
    $search &&
    $search.open
        ? 'visible'
        : 'invisible'}"
    on:click={handleToggleSearch}
/>
<nav
    id="main"
    class="fixed w-full bg-white grid justify-items-center items-center gap-1 px-3 py-3 z-20 shadow-md
    {$user ? 'grid-cols-navigation' : 'grid-cols-navigation-public'}"
>
    {#if !$user}
        {#each $navigation.public as option}
            {#if option !== "log in" && option !== "sign up"}
                <NavigationOption context="main" {option} />
            {/if}
        {/each}
        <div class="grid grid-cols-2 justify-self-end gap-3">
            {#each $navigation.public as option}
                {#if option === "log in" || option === "sign up"}
                    <NavigationOption context="main" {option} />
                {/if}
            {/each}
        </div>
    {/if}
    {#if $user}
        {#each $navigation.main as option}
            <NavigationOption context="main" {option} />
        {/each}
        {#if $navigation.showSecondary === true}
            <nav
                id="secondary"
                class="absolute z-10 grid rounded-2xl ring-light ring-opacity-10 ring-1 shadow-md bg-white w-56 right-0 top-12 px-4 py-3"
            >
                <p class="text-3xs font-light">Currently in</p>
                {#each $navigation.secondary as option}
                    {#if option === "profile"}
                        <NavigationOption context="secondary" {option} />
                    {/if}
                {/each}
                <p class="text-3xs font-light pt-4">More options</p>
                {#each $navigation.secondary as option}
                    {#if option !== "profile"}
                        <NavigationOption context="secondary" {option} />
                    {/if}
                {/each}
            </nav>
        {/if}
    {/if}
</nav>
