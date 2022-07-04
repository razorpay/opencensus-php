/* eslint valid-jsdoc: 0 */
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { BANK_NAMES } from '../utils';

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
  [BANK_NAMES.AXIS]: 'rgb(151, 20, 77)',
  [BANK_NAMES.BOB]: 'rgb(240, 78, 0)',
  [BANK_NAMES.ICICI]: 'rgb(240, 243, 244)',
  [BANK_NAMES.HDFC]: 'rgb(8, 76, 141)',
};

const OLD_BLUE_GRADIENT = 'linear-gradient(314deg, #54a5ff -40%, #03299C)';

const bankBackgroundColors = {
  [BANK_NAMES.BOB]: '#FF5D27',
  [BANK_NAMES.ICICI]: theme.colors.background[600],
  [BANK_NAMES.AXIS]: theme.colors.background[600],
  [BANK_NAMES.KKBK]: theme.colors.background[600],
  [BANK_NAMES.AXIS_EASY_PAY]: '#97144d',
  [BANK_NAMES.JKB]: OLD_BLUE_GRADIENT,
  [BANK_NAMES.HDFC_COLLECT_NOW]: '#004b8e',
  [BANK_NAMES.YES_BANK]: '#ffffff',
  [BANK_NAMES.HDFC_GIG]: '#f0f3f4',
  [BANK_NAMES.SIB]: '#c4161b',
  [BANK_NAMES.CITI]: OLD_BLUE_GRADIENT,
};

const createPrimaryColors = (color) => {
  const obj = {};
  for (let i = 0; i < colorScale.length; i++) {
    obj[colorScale[i]] = color;
  }
  return obj;
};

/**
 * @param {import("./types").TransformedOrgData} orgData
 * @returns {boolean}
 */
const hasThemeing = (orgData) => {
  if (orgData.isOrgRZP) return false;
  return orgData?.styles?.primary || bankPrimaryColors[orgData.orgName];
};

const gradient =
  'linear-gradient(0deg, rgba(2, 42, 156, 0.3), rgba(2, 42, 156, 0.3)), linear-gradient(232.85deg, #020529 -52%, #000B8E 198.1%);';

/**
 * @param {string} org
 * @returns {string}
 */
export const getPageBackgroundColor = (org) => {
  const bg = bankBackgroundColors[org];
  return bg || gradient;
};

/**
 * @param {import("./types").TransformedOrgData} orgData
 */
export const getTheme = (orgData) => {
  const customTheme = theme;

  // if both primary color from api & bankingPrimaryColors doesn't exist
  // means that we cannot theme these page
  if (!hasThemeing(orgData)) return theme;

  // check for primary color
  // if it's not there fallback to hard coded banking colors
  const colors = orgData?.styles?.primary
    ? createPrimaryColors(orgData?.styles?.primary)
    : createPrimaryColors(bankPrimaryColors[orgData.orgName]);

  customTheme.colors.primary = colors;
  customTheme.colors.primary['920'] = theme.colors.sapphire[920];
  customTheme.colors.primary['930'] = theme.colors.sapphire[930];
  customTheme.colors.primary['950'] = theme.colors.sapphire[950];

  return customTheme;
};

export const getBankingCaptchaColor = (org) => {
  switch (org) {
    // works for white/light bg
    case BANK_NAMES.ICICI:
    case BANK_NAMES.AXIS:
    case BANK_NAMES.KKBK:
    case BANK_NAMES.YES_BANK:
    case BANK_NAMES.HDFC_GIG:
      return {
        primary: { desktop: 'dark.970', mobile: 'dark.970' },
        secondary: { desktop: 'primary.900', mobile: 'primary.900' },
      };

    case BANK_NAMES.BOB:
    case BANK_NAMES.SIB:
      return {
        primary: { desktop: 'light.900', mobile: 'dark.900' },
        secondary: { desktop: 'light.970', mobile: 'dark.970' },
      };

    case BANK_NAMES.JKB:
    case BANK_NAMES.AXIS_EASY_PAY:
    case BANK_NAMES.HDFC_COLLECT_NOW:
    case BANK_NAMES.HDFC:
    case BANK_NAMES.CITI:
      return {
        primary: { desktop: 'light.900', mobile: 'dark.900' },
        secondary: { desktop: 'light.900', mobile: 'dark.900' },
      };

    default:
      return {
        primary: { desktop: 'light.970', mobile: 'dark.970' },
        secondary: { desktop: 'primary.900', mobile: 'primary.900' },
      };
  }
};
