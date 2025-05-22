import React, { useMemo } from 'react';
import {
  getFallbackProductsForConnectedNav,
  LOYALTY_PRODUCTS_SECTION,
} from 'merchant/components/SidebarV2/utils/Fallback';
import {
  PRODUCTS_DATA,
  CUSTOMERS_PRODUCTS,
  COMMON_PRODUCTS,
  ProductTypeProp,
  getL1ProductItems,
} from 'merchant/components/SidebarV2/utils/Products';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { useLocation } from 'react-router-dom';
import { useSplitzService } from 'common/splitz';
import { useI18Service } from 'common/i18';
import { useStore } from '@federated/apps/shell/commonStore';
import { initializeRoutes, ROUTE_REG } from 'merchant/components/SidebarV2/utils/href';
import SidebarFooter from './components/SidebarFooter';
import ActivationProgress from './components/ActivationProgress';

export const accountsAndSettingsIds = new Set(['settings', 'accountsettings', 'my_account']);

const result = COMMON_PRODUCTS.reduce(
  (
    acc: {
      ACCOUNTS_AND_SETTINGS_PRODUCTS: ProductTypeProp[];
      COMMON_PRODUCTS: ProductTypeProp[];
    },
    product,
  ) => {
    if (accountsAndSettingsIds.has(product.product_id)) {
      acc.ACCOUNTS_AND_SETTINGS_PRODUCTS.push(product);
    } else {
      acc.COMMON_PRODUCTS.push(product);
    }
    return acc;
  },
  { ACCOUNTS_AND_SETTINGS_PRODUCTS: [], COMMON_PRODUCTS: [] },
);

export const COMMON_SECTION = {
  section_name: '',
  section_id: 'common_products',
  product_options: result.COMMON_PRODUCTS,
};

export const CUSTOMERS_PRODUCTS_SECTION = {
  section_name: 'CUSTOMER PRODUCTS',
  section_id: 'customer_products',
  product_options: CUSTOMERS_PRODUCTS,
};

const FOOTER_PRODUCTS_SECTION = {
  section_name: '',
  section_id: 'footer_section',
  product_options: result.ACCOUNTS_AND_SETTINGS_PRODUCTS,
};

function transformDataWithRoutes(data, routes, { user, abExperiments, isConfigTagEnabled }) {
  const routeKeys = new Set(Object.keys(routes));

  return data
    .map((section) => {
      const productOptionsKey = 'product_options';
      const options = section[productOptionsKey] || [];

      const updatedOptions = options.reduce((acc, product) => {
        if (
          routeKeys.has(product.product_id) &&
          PRODUCTS_DATA[product.product_id] &&
          showWhenUtil({
            additionalCondition: (users) =>
              PRODUCTS_DATA[product.product_id].additionalCondition(users, {
                abExperiments,
                isConfigTagEnabled,
              }),
          })
        ) {
          if (PRODUCTS_DATA[product.product_id]?.items?.length > 0) {
            product.items = getL1ProductItems(product.product_id, user, abExperiments);
          }

          const titleSuffix = PRODUCTS_DATA[product.product_id].getTitleSuffix?.(user, {
            abExperiments,
            isConfigTagEnabled,
          });

          acc.push({
            ...product,
            href:
              typeof PRODUCTS_DATA[product.product_id]?.getHref === 'function'
                ? PRODUCTS_DATA[product.product_id]?.getHref({ user, routes })
                : routes[product.product_id],
            routeRegex: ROUTE_REG[product.product_id],
            icon: PRODUCTS_DATA[product.product_id].bladeIcon,
            titleSuffix: titleSuffix,
          });
        }
        return acc;
      }, []);

      // Return updated section only if there are matching options
      return updatedOptions.length > 0
        ? {
            ...section,
            [productOptionsKey]: updatedOptions, // Preserve the key used
          }
        : null;
    })
    .filter(Boolean); // Remove sections with no matching product options
}

const useSideNavHook = () => {
  const location = useLocation();
  const user = useStore((state) => state.session.user);
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();

  const [PAYMENTS_PRODUCTS_SECTION, BANKING_PRODUCTS_SECTION] =
    getFallbackProductsForConnectedNav(user); // v2 fallback products

  const listItemsV2 = [
    PAYMENTS_PRODUCTS_SECTION,
    BANKING_PRODUCTS_SECTION,
    LOYALTY_PRODUCTS_SECTION,
  ];

  const sideNavListItems = useMemo(
    () => [COMMON_SECTION, ...listItemsV2, CUSTOMERS_PRODUCTS_SECTION],
    [],
  );

  const sideNavFooterListItems = useMemo(() => [FOOTER_PRODUCTS_SECTION], []);

  const routes = useMemo(() => initializeRoutes(location, user), [location, user]);

  const transformedData = useMemo(
    () =>
      transformDataWithRoutes(sideNavListItems, routes, {
        user,
        abExperiments,
        isConfigTagEnabled,
      }),
    [sideNavListItems, routes, abExperiments, isConfigTagEnabled],
  );

  const footerSectionData = useMemo(
    () =>
      transformDataWithRoutes(sideNavFooterListItems, routes, {
        user,
        abExperiments,
        isConfigTagEnabled,
      }),
    [sideNavFooterListItems, routes, abExperiments, isConfigTagEnabled],
  );

  const footer = useMemo(
    () => <SidebarFooter listItems={footerSectionData} />,
    [footerSectionData],
  );

  const banner = useMemo(() => <ActivationProgress />, []);

  return {
    listItems: transformedData,
    footer,
    banner,
  };
};

export default useSideNavHook;
