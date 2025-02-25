/* eslint-disable @typescript-eslint/no-implicit-any-catch */
/* eslint-disable @typescript-eslint/prefer-nullish-coalescing */
import { getItemFromLocalStorage, setItemInLocalStorage } from './localStorage';

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
  return `${modePrefixIdentifier}${mid}`;
};

/**
 * Retrieves the mode for a specific merchant or returns the default mode if not set.
 *
 * @param {string} [merchantId] - The merchant ID for which to retrieve the mode.
 * @returns {ModeT} - Returns the mode, either 'test' or 'live'.
 */
export const getMode = (merchantId?: string): ModeT => {
  if (merchantId) {
    const merchantModeKey = getMerchantModeKey(merchantId);
    const modeFromLocalStorage = getItemFromLocalStorage(merchantModeKey);
    return (modeFromLocalStorage || mode) as ModeT;
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
