import graphicOne from 'assets/checkout-editor/sidebar-graphic/graphics-one.svg';
import graphicTwo from 'assets/checkout-editor/sidebar-graphic/graphics-two.svg';
import graphicThree from 'assets/checkout-editor/sidebar-graphic/graphics-three.svg';

export const BRAND_COLOR_DEFAULT_VALUE = {
  title: 'Background color',
  subTitle: 'Customize with your brand color',
  color: '#2950DA',
};

export const FONT_STYLE_DEFAULT_VALUE = {
  title: 'Font',
  subTitle: 'Choose a font for your brand',
};

export const FONT_OPTIONS = [
  // Sans Serif
  { name: 'Inter (default)', code: 'inter' },
  { name: 'Montserrat', code: 'montserrat' },
  { name: 'Open Sans', code: 'open-sans' },
  { name: 'Roboto', code: 'roboto' },
  { name: 'Noto', code: 'noto' },
  { name: 'Poppins', code: 'poppins' },

  // Serif
  { name: 'Merriweather', code: 'merriweather' },
  { name: 'Georgia', code: 'georgia' },
  { name: 'Baskerville', code: 'baskerville' },

  // Slab Serif
  { name: 'Roboto Slab', code: 'roboto-slab' },
  { name: 'Sanchez', code: 'sanchez' },
  { name: 'Rokkitt', code: 'rokkitt' },

  // Display
  { name: 'TASA (default)', code: 'tasa' },
  { name: 'Bebas Neue', code: 'bebas-neue' },
  { name: 'Cera Pro', code: 'cera-pro' },
  { name: 'Modak', code: 'modak' },
  { name: 'Grotesque', code: 'grotesque' },
  { name: 'Space Grotesk', code: 'space-grotesk' },
  { name: 'Syne', code: 'syne' },
  { name: 'Familjen Grotesk', code: 'familjen-grotesk' },

  // Script Type
  { name: 'Playfair Display', code: 'playfair-display' },
  { name: 'Lobster', code: 'lobster' },
  { name: 'Pacifico', code: 'pacifico' },
];

export const AVAILABLE_BORDER_STYLE = {
  ROUNDED: 'rounded',
  SHARP: 'sharp',
};

export const AVAILABLE_GRAPHICS = {
  NONE: 'none',
  SIDEBAR: 'sidebar',
  ECOM_SIDEBAR: 'ecom-sidebar',
};

export const SIDEBAR_DEFAULT_VALUE = {
  title: 'Sidebar graphic',
  subTitle: 'Beautify your checkout sidebar with a decorative graphic',
};

export const SIDEBAR_GRAPHICS_ITEMS = [
  {
    src: graphicOne,
    value: AVAILABLE_GRAPHICS.NONE,
  },
  {
    src: graphicTwo,
    value: AVAILABLE_GRAPHICS.SIDEBAR,
  },

  {
    src: graphicThree,
    value: AVAILABLE_GRAPHICS.ECOM_SIDEBAR,
  },
];

export const getFontNameByCode = (code: string) => {
  const font = FONT_OPTIONS.find((font) => font.code === code);
  return font?.name;
};
