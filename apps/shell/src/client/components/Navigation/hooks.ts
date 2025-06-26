import { matchPath, useLocation } from 'react-router-dom';
import { isProductPathActive as checkProductPathActive, getActiveProductAlias } from './utils';
import { PRODUCT_ALIAS_MAP, getAllFullScreenFlows } from './constants';

/**
 * Hook to get the active product based on the path
 * @returns An object with methods related to the active product
 */
export const useGetActiveProduct = () => {
  const location = useLocation();
  const currentPath = location.pathname;

  /**
   * Simplified function to check if a product is active
   * @param productAlias - The product alias to check
   * @returns boolean indicating if the product path is active
   */
  const isProductPathActive = (productAlias: string): boolean => {
    return checkProductPathActive({
      productAlias,
      currentPath,
    });
  };

  /**
   * Check if payments product is active
   * @returns boolean indicating if payments is active
   */
  const isPaymentsActive = checkProductPathActive({
    productAlias: PRODUCT_ALIAS_MAP.PAYMENTS,
    currentPath,
  });

  /**
   * Check if banking product is active
   * @returns boolean indicating if banking is active
   */
  const isBankingActive = checkProductPathActive({
    productAlias: PRODUCT_ALIAS_MAP.BANKING,
    currentPath,
  });

  /**
   * Check if partners product is active
   * @returns boolean indicating if partners is active
   */
  const isPartnersActive = checkProductPathActive({
    productAlias: PRODUCT_ALIAS_MAP.PARTNERS,
    currentPath,
  });

  /**
   * Check if home product is active
   * @returns boolean indicating if home is active
   */
  const isHomeActive = checkProductPathActive({
    productAlias: PRODUCT_ALIAS_MAP.HOME,
    currentPath,
  });

  /**
   * Check if company registration product is active
   * @returns boolean indicating if company registration is active
   */
  const isCompanyRegistrationActive = checkProductPathActive({
    productAlias: PRODUCT_ALIAS_MAP.COMPANY_REGISTRATION,
    currentPath,
  });

  const activeProductAlias = getActiveProductAlias(currentPath);

  return {
    isProductPathActive,
    isPaymentsActive,
    isBankingActive,
    isPartnersActive,
    isHomeActive,
    isCompanyRegistrationActive,
    activeProductAlias,
  };
};

/**
 * Hook to hide the top navigation based on the active route for hiding the top navigation for full screen flows
 * @returns boolean indicating if the top navigation should be hidden
 */
export const useHideTopNavigation = () => {
  const location = useLocation();
  const currentPath = location.pathname;

  const isFullPage = getAllFullScreenFlows().some((flowPath) =>
    Boolean(matchPath({ path: flowPath, exact: false }, currentPath)),
  );

  return isFullPage;
};
