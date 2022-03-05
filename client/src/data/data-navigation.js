import { writable } from 'svelte/store';

// eslint-disable-next-line import/prefer-default-export
export const navigation = writable({
  currentPage: 'home',
  public: ['logo', 'home', 'log in', 'sign up'],
  main: ['logo', 'home', 'search', 'profile', 'secondary'],
  secondary: ['profile', 'settings', 'log out'],
  settings: ['public information', 'account information'],
  showSecondary: false,
  settingsOption: 'public information',
  user: {},
});
