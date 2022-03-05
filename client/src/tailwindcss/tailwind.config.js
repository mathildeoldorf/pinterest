module.exports = {
  purge: {
    mode: 'jit',
    content: [
      '../components/*.svelte',
      '../*.svelte',
    ],
  },
  future: {
    purgeLayersByDefault: true,
    removeDeprecatedGapUtilities: true,
  },
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      colors: {
        primary: '#E60023',
        'primary-hover': '#ad081b',
        dark: '#111111',
        'dark-opaque': 'rgba(17, 17, 17, 0.5)',
        light: '#EFEFEF',
        'light-hover': '#d7d7d7',
        muted: '#717171',
        red: '#e60023',
      },
      gridTemplateColumns: {
        // Simple 16 column grid
        16: 'repeat(16, minmax(0, 1fr))',

        // Complex site-specific column configuration
        navigation: '0.5fr 0.5fr 12fr 0.5fr 0.5fr',
        'navigation-public': '0.5fr 0.5fr 12fr',
        '1/3-2/3': '1fr 2fr',
        '1/4-3/4': '1fr 3fr',
        '1/5-4/5': '1fr 4fr',
        '16-auto': 'repeat(16, auto)',
        '2-auto': 'repeat(2, auto)',
      },
      fontSize: {
        '3xs': '.60rem',
        '2xs': '.70rem',
      },
      cursor: {
        'zoom-in': 'zoom-in',
        'zoom-out': 'zoom-out',
      },
      maxHeight: {
        0: '0',
        '1/4': '25%',
        '1/2': '50%',
        '3/4': '75%',
        '4/5': '80%',
        full: '100%',
        'screen-md': '640px',
      },
      minHeight: {
        0: '0',
        '1/4': '25%',
        '1/2': '50%',
        '3/4': '75%',
        '4/5': '80%',
        '9/10': '88%',
        full: '100%',
      },
      minWidth: {
        0: '0',
        '1/4': '25%',
        '1/2': '50%',
        '3/4': '75%',
        '4/5': '80%',
        '9/10': '88%',
        full: '100%',
      },
      boxShadow: {
        custom: '0 1px 20px 0 rgba(0, 0, 0, 0.1)',
        add: '0px 0px 20px rgb(0 0 0 / 10%)',
        options: '0 0 8px rgb(0 0 0 / 10%)',
      },
      zIndex: {
        '-10': '-10',
      },
      animation: {
        toast: 'toast 2s ease-in-out forwards',
        rotate: 'rotate 4s infinite linear',
      },
      keyframes: {
        toast: {
          '0%': {
            transform: 'translateY(-50px)',
          },
          '20%': {
            transform: 'translateY(110px)',
          },
          '80%': {
            transform: 'translateY(110px)',
          },
          '100%': {
            transform: 'translateY(-50px)',
          },
        },
        rotate: {
          '0%': {
            transform: 'translateX(120px) rotateY(0deg)',
          },
          '100%': {
            transform: 'translateX(120px) rotateY(360deg)',
          },
        },
      },
    },
  },
  variants: {
    extend: {
      visibility: ['hover', 'focus'],
    },
  },
  plugins: [],
};
