/* eslint-disable @typescript-eslint/no-implicit-any-catch */
/* eslint-disable @typescript-eslint/prefer-nullish-coalescing */
import { RazorpayUser } from '@libs/shared-types';
import {
  getItemFromLocalStorage,
  removeItemFromLocalStorage,
  setItemInLocalStorage,
} from './localStorage';

/**
 * Defines the type for the mode. It can either be 'test' or 'live'.
 */
export type ModeT = 'test' | 'live';

/**
 * Default mode is set to 'test' initially.
 */
let mode: ModeT = 'test';

export const getMerchantModeKey = (mid: string) => {
  const modePrefixIdentifier = 'rzp_mode--';
  return window.rzp_user ? `${modePrefixIdentifier}${mid}` : '';
};

/**
 * Checks if user is activated
 * Equivalent to User.js isActivated getter
 */
const isUserActivated = (user: RazorpayUser): boolean => {
  return Boolean(user.activated) || user.pos_activation_status === 'activated';
};

/**
 * Retrieves the mode for a specific merchant or returns the default mode if not set.
 * Implements the same business logic as App.js for consistency.
 *
 * @param {string} [merchantId] - The merchant ID for which to retrieve the mode.
 * @returns {ModeT} - Returns the mode, either 'test' or 'live'.
 */
export const getMode = (merchantId?: string): ModeT => {
  if (merchantId) {
    const merchantModeKey = getMerchantModeKey(merchantId);
    let currentMode = getItemFromLocalStorage(merchantModeKey) as ModeT | null;

    const user: RazorpayUser = (window as any)?.rzp_user || {};

    if (!currentMode) {
      currentMode = isUserActivated(user) ? 'live' : 'test';
    } else if (!isUserActivated(user)) {
      currentMode = 'test';
    }

    return currentMode || mode;
  } else {
    return mode;
  }
};

/**
 * Sets the global mode to a specified value.
 *
 * @param {ModeT} value - The mode to set ('test' or 'live').
 * @returns {ModeT} - Returns the new mode.
 */
export function setMode(value: ModeT): ModeT {
  mode = value;
  return mode;
}

/**
 * Switches the mode for a specific merchant and stores it in localStorage.
 *
 * @param {string} merchantId - The ID of the merchant for which to switch the mode.
 * @param {ModeT} _mode - The mode to set for the merchant ('test' or 'live').
 *
 * @throws {Error} - Throws an error if setting the mode in localStorage fails.
 */
export const switchMode = (merchantId: string, _mode: ModeT) => {
  if (!['live', 'test'].includes(_mode)) {
    console.error(`Invalid Mode Passed: ${_mode}`);
  } else {
    try {
      const merchantModeKey = getMerchantModeKey(merchantId);
      setItemInLocalStorage(merchantModeKey, _mode);
    } catch (e) {
      console.error('Error Setting Mode:', e);
    }
  }
};

/**
 * Migrates old global mode tokens to merchant-specific tokens and initializes
 * the current mode token for the active merchant.
 *
 * This function handles the migration from the old 'rzp_mode' token format
 * to the new merchant-specific format 'rzp_mode--{merchantId}'.
 *
 * @returns {void} - Returns nothing.
 */
export const migrateGlobalModeTokensToMerchantModeTokens = (): void => {
  const oldModeToken = 'rzp_mode';
  const oldModeValue = getItemFromLocalStorage(oldModeToken);

  // localizing mode for each merchant so that different modes can be maintained
  // across logins/merchants
  if (oldModeValue) {
    window.rzp_user &&
      Object.keys(window.rzp_user.merchants).forEach((merchantId) => {
        setItemInLocalStorage(`${oldModeToken}--${merchantId}`, oldModeValue);
      });

    removeItemFromLocalStorage(oldModeToken);
  }
};
