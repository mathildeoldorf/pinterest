import { writable } from 'svelte/store';

// eslint-disable-next-line import/prefer-default-export
export const modal = writable({
  open: false,
  data: undefined,
  modified: false,
  context: undefined,
});
