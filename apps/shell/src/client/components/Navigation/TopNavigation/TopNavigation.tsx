import {
  Box,
  Card,
  ChevronDownIcon,
  IconComponent,
  Menu,
  MenuHeader,
  MenuItem,
  MenuOverlay,
  RazorpayIcon,
  TabNav,
  TabNavItem,
  TabNavItems,
  TopNav,
  TopNavBrand,
  TopNavContent,
  Text,
  useTheme,
  Skeleton,
  Divider,
  HomeIcon,
  MenuIcon,
  Button,
  Link,
  Heading,
  CardBody,
} from '@razorpay/blade/components';
import React, { Suspense, useEffect } from 'react';
import { useLocation, useNavigate, matchPath, Navigate } from 'react-router-dom';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import { useTopNavigationData } from './hooks';
import { PRODUCT_PATH_MAP, PRODUCT_ALIAS_MAP } from '../constants';
import { HeaderActionsLoader } from '../HeaderActionsLoader';
import { PRODUCT_ICON_MAP } from './constants';
import { ProductTopNavBrand } from './ProductTopNavBrand';
import { useBreakpoint } from '@razorpay/blade/utils';
import { isProductPathActive } from '../utils';
import { useStore } from '@federated/apps/shell/commonStore';
import { ProductAlias } from '../types';
import { useGetActiveProduct } from '../hooks';
import RazorpayHome from '@apps/shell/src/assets/razorpay_home.svg';
import { analyticsTrack, getCommonAnalyticsProperties } from '@libs/shared-utils';
import errorService from '@razorpay/universe-cli/errorService';
import ModalsLoader from '../../ModalsLoader';
import { DASHBOARD_PRIORITY_RANKS } from '@libs/shared-types';
import { DASHBOARD_TEAMS } from '@libs/shared-types';

const PartnerOnboardingModal = React.lazy(
  () => import('@federated/dashboards/payments/components/PartnerOnboardingModal'),
);

//Move to a seperate component
const ExploreItem = ({
  icon: Icon,
  title,
  description,
}: {
  icon: IconComponent;
  title?: string;
  description: string;
}): React.ReactElement => {
  return (
    <Box display="flex" gap="spacing.4" maxWidth="300px">
      <Box
        borderRadius="medium"
        padding="spacing.5"
        backgroundColor="surface.background.gray.subtle"
        display="flex"
        alignSelf="start"
      >
        <Icon color="interactive.icon.neutral.subtle" size="medium" />
      </Box>
      <Box>
        <Text color="surface.text.gray.subtle" size="medium" weight="semibold">
          {title}
        </Text>
        <Text size="small" color="surface.text.gray.muted">
          {description}
        </Text>
      </Box>
    </Box>
  );
};

function TopNavigation() {
  const navigate = useNavigate();
  const location = useLocation();
  const currentPath = location.pathname;
  const {
    products: productsFromStore,
    setIsSideNavOpenOnMobile,
    showSearchOnMobile,
  } = useConnectedNavigationStore((state) => state);
  const showNotification = useStore((state) => state.showNotification);

  const { setColorScheme, theme } = useTheme();
  const { products, isLoading, isError } = useTopNavigationData();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const { isHomeActive, isBankingActive } = useGetActiveProduct();

  const isMobile = matchedDeviceType === 'mobile';

  const { activeProductAlias } = useGetActiveProduct();

  const actionType = productsFromStore?.selectedProduct?.selectAction?.actionType;
  const excludedActionTypes = ['growth_page', 'access_denied_page'];
  const shouldHideMobileMenu = !actionType || excludedActionTypes.includes(actionType);

  const isOneHomeEnabled = Boolean(window?.IS_ONE_HOME_ENABLED);

  const setThemeBasedOnSelectedProduct = () => {
    // TODO: Set color scheme to dark for banking once X is released in connected dashboard
    switch (activeProductAlias) {
      case PRODUCT_ALIAS_MAP.BANKING: {
        setColorScheme('light');
        break;
      }

      case PRODUCT_ALIAS_MAP.PAYMENTS:
        setColorScheme('light');
        break;
      default:
        setColorScheme('light');
    }
  };

  //Write UT for this
  useEffect(() => {
    if (productsFromStore?.selectedProduct) {
      const selectedProduct = productsFromStore.selectedProduct;

      if (
        selectedProduct.selectAction?.actionType === 'navigate' &&
        selectedProduct.selectAction?.value?.type === 'external_navigation' &&
        selectedProduct.selectAction?.value?.navigateTo
      ) {
        window.open(selectedProduct.selectAction.value.navigateTo, '_blank');

        if (isOneHomeEnabled) {
          navigate('/home', { replace: true });
        } else {
          navigate('/dashboard', { replace: true });
        }
      }
    }
  }, [productsFromStore, navigate, isOneHomeEnabled]);

  const handleProductSelect = ({ productAlias, isInsideMore } = {}) => {
    return () => {
      try {
        const selectedProduct = products.find((product: any) => product.alias === productAlias);
        const selectedProductIndex = products.findIndex(
          (product: any) => product.alias === productAlias,
        );

        // TODO: move this to a util
        analyticsTrack({
          objectName: 'L0 Main Frame Title',
          actionName: 'Clicked',
          screen: 'Top Navigation',
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
            version: 'v1',
            page: location.pathname?.replace('/app/', ''),
            bu_title: selectedProduct?.title,
            bu_title_rank: selectedProductIndex + 1,
            experiment_name: 'oneNavV1',
            is_inside_more: isInsideMore,
          },
        });

        //TODO: The right way to do this is based on routes
        setThemeBasedOnSelectedProduct();

        const selectedProductAction = selectedProduct?.selectAction;

        switch (selectedProductAction?.actionType) {
          // Grouped together as they require the same form of navigation.
          // UCS doesn't provide 'navigate' for Growth & Access Denied pages, so we handle internal navigation.
          case 'growth_page':
          case 'access_denied_page':
          case 'navigate': {
            const navigationValue = selectedProductAction?.value;
            switch (navigationValue?.type) {
              case 'internal_navigation': {
                navigate(navigationValue.navigateTo);
                break;
              }
              case 'external_navigation': {
                window.open(navigationValue.navigateTo, '_blank');
                break;
              }
              default: {
                throw new Error(
                  'Invalid navigation type received, user will not be able to perform any action on click of TopNav items',
                );
              }
            }
            break;
          }
          case 'modal': {
            const modalAlias = selectedProductAction?.value;
            if (modalAlias === 'partners_onboarding_modal') {
              navigate({
                pathname: '/dashboard',
                search: '?openModal=partners_onboarding_modal',
              });
            }
            break;
          }
          default: {
            throw new Error(
              'Invalid action type received, user will not be able to perform any action on click of TopNav items',
            );
          }
        }
      } catch (error) {
        errorService.captureError(error, {
          tags: {
            team: DASHBOARD_TEAMS.CROSS_SELL_EXPERIENCE,
            module: '[@shell]: TopNavigation - handleProductSelect',
          },
          rank: DASHBOARD_PRIORITY_RANKS.P0,
        });
        console.error(error);
        showNotification({
          type: 'error',
          message: 'Something went wrong while navigating. We are looking into it.',
        });
      }
    };
  };

  //TODO: Check for alias when partner is in more initially with more_partner_top_navigation_item
  const isAnyOverflowingProductActive = (overflowingItems) => {
    return overflowingItems.some((product) => {
      return isProductPathActive({ productAlias: product.alias, currentPath });
    });
  };

  const getTopNavContent = () => {
    if (isLoading) {
      return (
        <Box display="flex" gap="spacing.4" paddingY="spacing.3" alignItems="center">
          <Skeleton width="100px" height="32px" borderRadius="medium" />
          <Skeleton width="120px" height="32px" borderRadius="medium" />
          <Skeleton width="110px" height="32px" borderRadius="medium" />
          <Skeleton width="110px" height="32px" borderRadius="medium" />
        </Box>
      );
    }

    return (
      <TabNav items={products}>
        {({ items, overflowingItems }) => {
          return (
            <>
              <TabNavItems>
                {items.map(({ id, alias, title, icon, trailingIcon }, index) => {
                  return (
                    <TabNavItem
                      key={id}
                      title={title}
                      icon={icon}
                      onClick={handleProductSelect({ productAlias: alias })}
                      isActive={isProductPathActive({ productAlias: alias, currentPath })}
                      trailing={trailingIcon ? React.createElement(trailingIcon) : undefined}
                    />
                  );
                })}
              </TabNavItems>
              {overflowingItems.length > 0 ? (
                <Menu openInteraction="hover">
                  <TabNavItem
                    title="More"
                    trailing={<ChevronDownIcon />}
                    isActive={isAnyOverflowingProductActive(overflowingItems)}
                  />

                  <MenuOverlay>
                    <MenuHeader title="Products for you" />
                    {overflowingItems.map(({ id, alias, icon, title, description }, index) => {
                      return (
                        <MenuItem
                          key={id}
                          onClick={handleProductSelect({ productAlias: alias, isInsideMore: true })}
                        >
                          <ExploreItem
                            icon={icon ?? RazorpayIcon}
                            title={title}
                            description={description!}
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
    );
  };

  if (isMobile) {
    if (isHomeActive || currentPath.startsWith('/home')) {
      return (
        <Box zIndex="101" flexShrink={0}>
          <Card elevation="lowRaised" padding="spacing.0" height="100%">
            <TopNav zIndex="101" backgroundColor="surface.background.gray.intense">
              <Box display="flex" alignItems="center" gap="spacing.3">
                <img src={RazorpayHome} alt="company-logo" />
                <Heading
                  textAlign="left"
                  size="medium"
                  weight="semibold"
                  color="surface.text.gray.normal"
                >
                  Razorpay Home
                </Heading>
              </Box>
              <Box></Box>

              <HeaderActionsLoader />
            </TopNav>
          </Card>
        </Box>
      );
    }
    return (
      <Box zIndex="101" flexShrink={0}>
        <Card elevation={isMobile ? 'none' : 'lowRaised'} padding="spacing.0" height="100%">
          <TopNav zIndex="101" backgroundColor="surface.background.gray.intense">
            <Box>
              {!shouldHideMobileMenu && (
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
              <Link
                icon={HomeIcon}
                size="medium"
                onClick={() => {
                  navigate('/home');
                }}
              >
                Home
              </Link>
            </Box>

            <Box></Box>
            <HeaderActionsLoader />
          </TopNav>
          {showSearchOnMobile && <HeaderActionsLoader showOnlyMobileSearch={true} />}
        </Card>
      </Box>
    );
  }

  return (
    <TopNav zIndex="101" flexShrink={0}>
      <TopNavBrand>
        <ProductTopNavBrand />
      </TopNavBrand>
      <TopNavContent>{getTopNavContent()}</TopNavContent>
      <HeaderActionsLoader />
      {/* <ModalsLoader /> */}
    </TopNav>
  );
}

export default TopNavigation;
