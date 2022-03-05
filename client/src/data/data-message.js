/* eslint-disable import/prefer-default-export */
import { writable } from 'svelte/store';

export const message = writable({
  open: true,
  data: {},
  type: undefined,
  options: undefined,
  action: undefined,
});
