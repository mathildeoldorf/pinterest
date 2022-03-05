import { writable } from 'svelte/store';

// eslint-disable-next-line import/prefer-default-export
export const pins = writable({
  context: undefined,
  data: undefined,
  interval: 10,
});
