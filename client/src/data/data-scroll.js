/* eslint-disable no-return-assign */
/* eslint-disable no-param-reassign */
/* eslint-disable no-unused-vars */
import { writable } from 'svelte/store';

function createScroll() {
  const { subscribe, set, update } = writable(0);

  return {
    subscribe,
    updateScroll: (y) => {
      update((n) => n = y);
    },
    reset: () => set(0),
  };
}

export const scroll = createScroll();

export const scrolledToBottom = writable(false);
