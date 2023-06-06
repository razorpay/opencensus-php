export const getButtonThemes = (businessName = 'Razorpay') => {
  const buttonThemes = {
    BTN_DARK_STANDARD: {
      label: `${businessName} Dark`,
      value: 'rzp-dark-standard',
    },

    BTN_LIGHT_STANDARD: {
      label: `${businessName} Light`,
      value: 'rzp-light-standard',
    },

    BTN_OUTLINE_STANDARD: {
      label: `${businessName} Outline`,
      value: 'rzp-outline-standard',
    },

    BRAND_COLOR: {
      label: 'Brand Color',
      value: 'brand-color',
    },
  };
  return buttonThemes;
};
const buttonThemes = getButtonThemes();
export const buttonThemesList = [
  buttonThemes.BTN_DARK_STANDARD,
  buttonThemes.BTN_LIGHT_STANDARD,
  buttonThemes.BTN_OUTLINE_STANDARD,
  buttonThemes.BRAND_COLOR,
];
