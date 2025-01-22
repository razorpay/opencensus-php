import graphicOne from 'assets/checkout-editor/sidebar-graphic/graphics-one.svg';
import graphicTwo from 'assets/checkout-editor/sidebar-graphic/graphics-two.svg';
import graphicThree from 'assets/checkout-editor/sidebar-graphic/graphics-three.svg';

import typeOne from 'assets/checkout-editor/title-style/type-one.png';
import typeTwo from 'assets/checkout-editor/title-style/type-two.png';
import typeThree from 'assets/checkout-editor/title-style/type-three.png';

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
  { name: 'Inter', code: 'Inter' },
  { name: 'Montserrat', code: 'Montserrat' },
  { name: 'Open Sans', code: 'Open Sans' },
  { name: 'Roboto', code: 'Roboto' },
  { name: 'Noto Sans', code: 'Noto Sans' },
  { name: 'Merriweather', code: 'Merriweather' },
  { name: 'Roboto Slab', code: 'Roboto Slab' },
  { name: 'Rokkitt', code: 'Rokkitt' },
  { name: 'Tasa (default)', code: 'Tasa' },
  { name: 'Space Grotesk', code: 'Space Grotesk' },
  { name: 'Syne', code: 'Syne' },
  { name: 'Familjen Grotesk', code: 'Familjen Grotesk' },
  { name: 'Playfair Display', code: 'Playfair Display' },
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

export const FESTIVAL_THEME_DEFAULT_VALUE = {
  title: 'Festive Theme',
  subTitle: 'Add cheer to your checkout with festive animations and graphics',
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

export const AVAILABLE_TITLE_STYLE = {
  LOGO_TEXT: 'logo_and_text',
  WORDMARK: 'wordmark',
  TEXT_ONLY: 'text',
};

export const DEFAULT_TITLE_TYPE = [
  {
    title: 'Logo & Text',
    description: 'Best for square logos',
    src: typeOne,
    alt: 'Razorpay',
    value: AVAILABLE_TITLE_STYLE.LOGO_TEXT,
  },
  {
    title: 'Wordmark',
    description: 'Best for long word based logos',
    src: typeThree,
    alt: 'Razorpay',
    value: AVAILABLE_TITLE_STYLE.WORDMARK,
  },
  {
    title: 'Text only',
    description: 'Just text? We got you.',
    src: typeTwo,
    alt: 'Razorpay',
    value: AVAILABLE_TITLE_STYLE.TEXT_ONLY,
  },
];

export const EDIT_LOGO_TITLE_HEADER_VALUE = {
  [AVAILABLE_TITLE_STYLE.LOGO_TEXT]: {
    title: 'Add brand name and upload logo',
    subTitle: 'Choose an image from your device to upload',
  },
  [AVAILABLE_TITLE_STYLE.WORDMARK]: {
    title: 'Upload a wordmark logo',
    subTitle: 'Choose and image from your device to upload',
  },
  [AVAILABLE_TITLE_STYLE.TEXT_ONLY]: {
    title: 'Enter brand  name',
    subTitle: 'Choose brand name that your customers are familiar with',
  },
};

export const TITLE_DEFAULT_VALUE = {
  title: 'Brand name and logo',
  subTitle: 'Select a title style and include the brand name with logo, wordmark, or just the name',
};
