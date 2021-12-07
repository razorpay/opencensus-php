import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { BANK_NAMES } from '../utils';

const themeGray = theme.colors.background[600];

const colorScale = [
  '100',
  '200',
  '300',
  '400',
  '500',
  '600',
  '700',
  '800',
  '900',
  '910',
  '920',
  '930',
  '940',
  '950',
  '970',
];

const bankPrimaryColors = {
  axis: 'rgb(151,20,77)',
  bob: 'rgb(240,78,0)',
  icici: themeGray,
  hdfc: 'rgb(8,76,141)',
};

const createPrimaryColors = (org) => {
  const obj = {};
  for (let i = 0; i < colorScale.length; i++) {
    obj[colorScale[i]] = bankPrimaryColors[org];
  }
  return obj;
};

export const getTheme = (org) => {
  const customTheme = theme;
  switch (org) {
    case BANK_NAMES.AXIS:
      customTheme.colors.primary = createPrimaryColors(org);
      return customTheme;

    case BANK_NAMES.HDFC:
      customTheme.colors.primary = createPrimaryColors(org);
      customTheme.colors.primary['920'] = theme.colors.sapphire[920];
      customTheme.colors.primary['930'] = theme.colors.sapphire[930];
      customTheme.colors.primary['950'] = theme.colors.sapphire[950];
      return customTheme;

    case BANK_NAMES.BOB:
      customTheme.colors.primary = createPrimaryColors(org);
      customTheme.colors.primary['920'] = theme.colors.sapphire[920];
      customTheme.colors.primary['930'] = theme.colors.sapphire[930];
      customTheme.colors.primary['950'] = theme.colors.sapphire[950];
      return customTheme;

    case BANK_NAMES.ICICI:
      return theme;

    default:
      return theme;
  }
};

export const getBankingCaptchaColor = (org) => {
  switch (org) {
    case BANK_NAMES.ICICI:
    case BANK_NAMES.AXIS:
      return {
        primary: { desktop: 'dark.970', mobile: 'dark.970' },
        secondary: { desktop: 'primary.900', mobile: 'primary.900' },
      };

    case BANK_NAMES.HDFC:
      return {
        primary: { desktop: 'light.970', mobile: 'dark.970' },
        secondary: { desktop: 'red.900', mobile: 'red.900' },
      };

    case BANK_NAMES.BOB:
      return {
        primary: { desktop: 'light.970', mobile: 'dark.970' },
        secondary: { desktop: 'primary.900', mobile: 'primary.900' },
      };

    default:
      return {
        primary: { desktop: 'light.970', mobile: 'light.970' },
        secondary: { desktop: 'light.970', mobile: 'light.970' },
      };
  }
};
