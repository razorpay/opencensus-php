import { matchPath } from 'react-router-dom';
import { PRODUCT_PATH_MAP, PRODUCT_ALIAS_MAP } from './constants';
import { isPaymentsPath } from '@libs/shared-utils';
import { isOneHomeExperimentEnabled } from '../utils';
/**
 * Checks if a specific product path is active based on the current path
 * @param params - An object containing productAlias and currentPath
 * @returns boolean indicating if the product path is active
 */

type ProductPathParams = {
  productAlias: string;
  currentPath: string;
};

export const isProductPathActive = ({ productAlias, currentPath }: ProductPathParams): boolean => {
  const productPathMap = { ...PRODUCT_PATH_MAP };
  const isOneHomeEnabled = isOneHomeExperimentEnabled();

  // If one home is not enabled, remove the home path from the product path map because payments will be the default in that case
  if (!isOneHomeEnabled) {
    delete productPathMap[PRODUCT_ALIAS_MAP.HOME];
  }

  // For payments, make it the default if no other product matches
  if (productAlias === PRODUCT_ALIAS_MAP.PAYMENTS) {
    const otherProductMatches = Object.entries(productPathMap).some(([alias, path]) => {
      // We only need to check non-payment paths
      if (alias === PRODUCT_ALIAS_MAP.PAYMENTS) return false;
      return Boolean(matchPath({ path }, currentPath));
    });

    // If no other product matches, assume it's payments
    if (!otherProductMatches) {
      return true;
    }

    // If another product matched, payments is not active
    return false;
  }

  // For other products, use the path mapping

  const productPath = productPathMap[productAlias];

  if (!productPath) {
    return false;
  }

  return Boolean(matchPath({ path: productPath }, currentPath));
};

/**
 * Gets the active product alias based on the current path
 * @param currentPath - The current path from router
 * @returns The active product alias or undefined if no match
 */
export const getActiveProductAlias = (currentPath: string): string | undefined => {
  const productAliases = Object.values(PRODUCT_ALIAS_MAP);

  for (const alias of productAliases) {
    if (isProductPathActive({ productAlias: alias, currentPath })) {
      return alias;
    }
  }

  return undefined;
};
