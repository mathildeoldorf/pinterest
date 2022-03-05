import { writable } from 'svelte/store';

// eslint-disable-next-line import/prefer-default-export
export const loading = writable({
  all: false,
  pins: false,
  done: false,
});
