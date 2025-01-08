// @ts-nocheck
import React, { useEffect } from 'react';
import {
  Box,
  ChevronDownIcon,
  Menu,
  MenuItem,
  MenuOverlay,
  TabNav,
  TabNavItem,
  TabNavItems,
  TopNav,
  TopNavActions,
  TopNavBrand,
  TopNavContent,
  IconComponent,
  Text,
  MenuHeader,
  Badge,
  useTheme,
  Link,
  HomeIcon,
  Heading,
  RazorpayIcon,
  Divider,
  Button,
  MenuIcon,
} from '@razorpay/blade/components';

import { useLocation, matchPath, useNavigate } from 'react-router-dom';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useStore } from 'shell/commonStore';
import { withI18Service } from 'common/i18';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import ErrorFallbackComponent from 'common/ui/WhatsNew/ErrorFallbackComponent';
import NotificationIcon from 'common/ui/WhatsNew/Icon';
import ProfileDropdown from 'merchant/components/HeaderNav/ProfileDropdown';
import UniversalSearch from 'merchant/components/HeaderNav/UniversalSearch';
import { ONE_NAV_MOBILE_PATH } from 'merchant/components/NavigationLayout/constants';
import ShowWhen from 'merchant/components/ShowWhen';
import { useIsRTUXHomepageEnabled } from 'merchant/containers/Home/RTUX/utils';
import lazyLoader from 'merchant/routes/LazyLoader';
import { productConfigMap } from 'merchant/views/ConnectedHome/config';
import EcosystemDowntimes from 'merchant/views/EcosystemDowntimes';
import { TopNavigationSkeleton } from 'merchant/components/NavigationLayout/NavigationContent/Skeleton/Skeleton';
import ExploreItem from 'merchant/components/NavigationLayout/TopNavigation/components/ExploreItem';

import RazorpayLogo from './RazorpayLogo';
import isPaymentsPath from './utils/isPaymentsPath';
import openProductModal from './utils/openProductModal';
import useConnectedProducts from '../hooks/useConnectedProducts';
import useConnectedNavigation from '../navigationStore';
import { productIconsMap, ExtendedListItems } from '../utils';
import { useNavigationLayoutContext } from '../context';

const WhatsNew = lazyLoader(
  () => import(/* webpackChunkName: 'merchantWhatsNew' */ 'common/ui/WhatsNew/Old'),
);

export const RZP_LOGO_URL_DARK = 'https://cdn.razorpay.com/logo.svg';

//TODO: Add types
const TopNavigation = ({
  user,
  org,
  mode,
  showMobileNav,
  i18,
  showGSTModal,
  onSwitchMode,
  onSwitchMerchant,
  modeFormatted,
  isSidebarV2,
  renderFullPageView,
}) => {
  const isRTUXHomepageEnabled = useIsRTUXHomepageEnabled();
  const { selectedProduct, setProduct } = useConnectedNavigation();
  const isRTUXHomepage = isSidebarV2 && isRTUXHomepageEnabled;
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  const { isConfigTagEnabled } = i18;

  const { setIsSideNavOpenOnMobile } = useNavigationLayoutContext();

  const commonProps = {
    user,
    showGSTModal,
    modeFormatted,
    onSwitchMode,
    onSwitchMerchant,
    isRTUXHomepage,
    isConnectedNavigation: true,
    selectedProduct,
  };

  const showEcosystemDowntimeButton =
    user?.isOrgRZP && user?.isEcosystemDowntimeEnabled && user.isCountryIndia;

  const showAnnouncementButton =
    user.isOrgAllowedFunctionality('external_links') &&
    !isConfigTagEnabled('announcements.announcements');

  function getProductFromPath(currentPath) {
    // Check Banking
    if (matchPath({ path: '/banking/*', exact: false }, currentPath)) {
      return {
        productAlias: 'banking_top_navigation_item',
        path: productConfigMap['banking_top_navigation_item'].defaultPath,
      };
    }

    // Check Partners
    if (matchPath({ path: '/partners/*', exact: false }, currentPath)) {
      return {
        productAlias: 'partners_top_navigation_item',
        path: productConfigMap['partners_top_navigation_item'].defaultPath,
      };
    }

    // Check Payroll
    if (matchPath({ path: '/payroll/*', exact: false }, currentPath)) {
      return {
        productAlias: 'payroll_top_navigation_item',
        path: productConfigMap['payroll_top_navigation_item'].defaultPath,
      };
    }

    // Check BillMe
    if (matchPath({ path: '/billme/*', exact: false }, currentPath)) {
      return {
        productAlias: 'billme_top_navigation_item',
        path: productConfigMap['billme_top_navigation_item'].defaultPath,
      };
    }

    // Check Rize
    if (matchPath({ path: '/rize/*', exact: false }, currentPath)) {
      return {
        productAlias: 'rize_top_navigation_item',
        path: productConfigMap['rize_top_navigation_item'].defaultPath,
      };
    }

    // Check Payments via regex-based route matching
    if (isPaymentsPath(currentPath)) {
      return {
        productAlias: 'payments_top_navigation_item',
        path: productConfigMap['payments_top_navigation_item'].defaultPath, // typically '/dashboard'
      };
    }

    // If no match found
    return { productAlias: '', path: '/' };
  }

  const openModal = useStore((state) => state.openModal);
  const closeModal = useStore((state) => state.closeModal);

  const location = useLocation();
  const navigate = useNavigate();
  const currentPath = location.pathname;
  const selectedProductAlias = selectedProduct.product?.alias;

  const {
    loading: connectedProductsLoading,
    listItems: connectedProductsListItems,
    getProductAction,
  } = useConnectedProducts();

  //TODO: Hide Search for growth, access and error pages
  const { type } = getProductAction(selectedProductAlias);

  const isTypeAccessGrowthOrErrorPage =
    type === 'growth_page' || type === 'access_denied_page' || type === 'error_page';

  const shouldShowSearch = user?.isUniversalSearchEnabled && !isTypeAccessGrowthOrErrorPage;

  useEffect(() => {
    // If no product is selected, set the default product
    if (!selectedProductAlias && connectedProductsListItems?.length > 0) {
      const activeProduct = connectedProductsListItems.find(
        (item) => item.datum.data?.navigation_data?.default_item,
      );

      if (activeProduct) {
        setProduct(activeProduct.datum);
      }
    }
  }, [connectedProductsListItems, selectedProductAlias, setProduct]);

  //Effect to set the product based on path
  useEffect(() => {
    if (!connectedProductsListItems || connectedProductsListItems.length === 0) return;

    const { productAlias } = getProductFromPath(currentPath);

    if (productAlias && productAlias !== selectedProductAlias) {
      const currentTopNavProductSelected = connectedProductsListItems.find(
        (item) => item.datum.alias === productAlias,
      );

      const { type, value } = getProductAction(productAlias);

      if (type == 'external') {
        window.open(value!, '_blank');
        const activeProduct = connectedProductsListItems.find((item) => {
          return item.datum?.data?.navigation_data?.default_item;
        });

        const { defaultPath } = productConfigMap[activeProduct?.datum?.alias!];
        setProduct(activeProduct?.datum);
        navigate(defaultPath);
        return;
      }

      if (currentTopNavProductSelected) {
        setProduct(currentTopNavProductSelected.datum);
      }
    }
  }, [currentPath, connectedProductsListItems, selectedProductAlias, setProduct]);

  if (
    connectedProductsLoading ||
    connectedProductsListItems.length === 0 ||
    !selectedProduct.product
  )
    return <TopNavigationSkeleton />;

  const handleProductItemClick = (productItem) => {
    const { datum: componentData } = productItem;

    const { type, value } = getProductAction(componentData.alias);
    if (type == 'external') {
      window.open(value!, '_blank');
      return;
    }
    if (type == 'modal') {
      openProductModal(value, openModal, closeModal, { deviceType: matchedDeviceType });
      return;
    }
    if (componentData.alias === selectedProduct.product.alias) return;

    const { defaultPath } = productConfigMap[componentData.alias];

    navigate(defaultPath);
  };

  const handleConnectedMobileHomeNavigate = () => {
    navigate(ONE_NAV_MOBILE_PATH);
  };

  if (renderFullPageView) return null; // hide Top Nav

  const isConnectedMobileHome = currentPath == ONE_NAV_MOBILE_PATH;

  if (isMobile) {
    if (isConnectedMobileHome) {
      return (
        <>
          <TopNav zIndex="101" backgroundColor="surface.background.gray.intense">
            <Box />
            <Heading textAlign="left" size="small" weight="semibold">
              Razorpay Home
            </Heading>
            <ProfileDropdown showMobileNav={showMobileNav} mode={mode} {...commonProps} />
          </TopNav>
          <Box paddingX={'spacing.3'} backgroundColor="surface.background.gray.intense">
            <Divider />
          </Box>
        </>
      );
    } else {
      return (
        <>
          <TopNav zIndex="101" backgroundColor="surface.background.gray.intense">
            <Box>
              {!isTypeAccessGrowthOrErrorPage && (
                <Button
                  size="medium"
                  variant="tertiary"
                  icon={MenuIcon}
                  onClick={() => {
                    setIsSideNavOpenOnMobile(true);
                  }}
                  marginRight="spacing.4"
                />
              )}
              <Link icon={HomeIcon} size="medium" onClick={handleConnectedMobileHomeNavigate}>
                Home
              </Link>
            </Box>

            <Box>
              {/* TODO: Will add after initial release */}
              {/* <Heading textAlign="center" size="small" weight="semibold">
                {selectedProduct.product.title}
              </Heading> */}
            </Box>
            <ProfileDropdown showMobileNav={showMobileNav} mode={mode} {...commonProps} />
          </TopNav>
          <Box paddingX={'spacing.3'} backgroundColor="surface.background.gray.intense">
            <Divider />
          </Box>

          {shouldShowSearch && (
            <Box
              position="sticky"
              top="56px"
              width="100%"
              zIndex="100"
              paddingX="spacing.3"
              paddingBottom="spacing.5"
              backgroundColor="surface.background.gray.intense"
            >
              <UniversalSearch isConnectedNavigation={true} />
            </Box>
          )}
        </>
      );
    }
  }

  return (
    <TopNav zIndex="101">
      <TopNavBrand>
        <RazorpayLogo />
      </TopNavBrand>
      <TopNavContent>
        <TabNav items={connectedProductsListItems}>
          {({ items, overflowingItems }) => {
            const isOverFlowingProductActive = overflowingItems.find((overflowingItem) => {
              return overflowingItem.id == selectedProduct?.product?.id;
            });

            return (
              <>
                <TabNavItems>
                  {items.map((item) => {
                    const Icon = item?.trailing ? productIconsMap[item.trailing] : null;
                    return (
                      <TabNavItem
                        key={item.id}
                        title={item.title}
                        isActive={selectedProduct?.product?.id == item.id}
                        icon={item.icon}
                        onClick={() => handleProductItemClick(item)}
                        trailing={Icon ? <Icon /> : undefined} // Fix: Change `null` to `undefined`
                      />
                    );
                  })}
                </TabNavItems>
                {overflowingItems.length > 0 ? (
                  <Menu openInteraction="hover">
                    <TabNavItem
                      title={
                        isOverFlowingProductActive
                          ? isOverFlowingProductActive?.id === selectedProduct?.product.id
                            ? `More: ${selectedProduct?.product.title}`
                            : `More`
                          : `More`
                      }
                      trailing={<ChevronDownIcon />}
                      isActive={isOverFlowingProductActive?.id !== undefined}
                    />

                    <MenuOverlay>
                      <MenuHeader title="Products for you" />
                      {overflowingItems.map((item) => {
                        return (
                          <MenuItem
                            key={item.id}
                            title={item.title}
                            onClick={() => handleProductItemClick(item)}
                          >
                            <ExploreItem
                              icon={item.icon ?? RazorpayIcon}
                              title={item.datum.title}
                              description={item.datum.description!}
                            />
                          </MenuItem>
                        );
                      })}
                    </MenuOverlay>
                  </Menu>
                ) : null}
              </>
            );
          }}
        </TabNav>
      </TopNavContent>
      <TopNavActions>
        {shouldShowSearch && <UniversalSearch isConnectedNavigation={true} />}

        {/* TODO: Check how to ecosystem downtime on mobile post initlal release */}
        {showEcosystemDowntimeButton && (
          <EcosystemDowntimes
            mode={mode}
            showMobileNav={showMobileNav}
            isRTUXHomepage={false}
            isConnectedNavigation={true}
          />
        )}

        {/* TODO: Check how to handle announcement on mobile post initlal release */}
        {showAnnouncementButton && (
          <GrowthAssetEB FallbackComponent={ErrorFallbackComponent}>
            {user.isWhatsNewLazyEnabled ? (
              <NotificationIcon showMobileNav={showMobileNav} {...commonProps} />
            ) : (
              <ShowWhen
                additionalCondition={
                  () => !org.features.includes('disable_announcements') // If the org features array include "disable_announcements" then we hide "Announcement Tab".
                }
              >
                <SuspenseWithLoader type="default">
                  <WhatsNew showMobileNav={showMobileNav} {...commonProps} />
                </SuspenseWithLoader>
              </ShowWhen>
            )}
          </GrowthAssetEB>
        )}
        <ProfileDropdown showMobileNav={showMobileNav} mode={mode} {...commonProps} />
      </TopNavActions>
    </TopNav>
  );
};

export default withI18Service(TopNavigation);
