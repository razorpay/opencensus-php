import React, { useEffect, useMemo } from 'react';
import { Box, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { ANALYTICS_ONENAV_EXPERIMENT, ONE_NAV_MOBILE_PATH } from '../constants';
import { useLocation } from 'react-router-dom';
import TestModeHighlight from '../components/TestModeHighlight';
import useConnectedProducts from '../hooks/useConnectedProducts';
import useConnectedNavigationStore from '../navigationStore';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

const withConnectedLayout = (WrappedComponent: React.FC<any>) => {
  const LayoutWrappedComponent: React.FC<{
    isConnectedFullPageView?: boolean;
    [key: string]: any;
  }> = (props) => {
    const { selectedProduct } = useConnectedNavigationStore();
    const selectedProductAlias = selectedProduct?.product?.alias;
    const { getProductAction } = useConnectedProducts();

    const productAction = useMemo(
      () => getProductAction(selectedProductAlias),
      [getProductAction, selectedProductAlias],
    );

    const product = {
      alias: selectedProductAlias,
      type: productAction.type,
    };

    const { theme } = useTheme();
    const { matchedDeviceType } = useBreakpoint({
      breakpoints: theme.breakpoints,
    });
    const location = useLocation();

    const isMobile = matchedDeviceType === 'mobile';
    const { isConnectedFullPageView = false, ...restProps } = props;

    const getTopNavHeight = () => {
      // 110px is the height of the top navigation bar and mobile search
      const currentPath = location.pathname;
      const isConnectedMobileHome = currentPath === ONE_NAV_MOBILE_PATH; // Define ONE_NAV_MOBILE_PATH appropriately

      if (isMobile) {
        if (!isConnectedFullPageView && !isConnectedMobileHome) {
          return '110px';
        }
      }
      return '58px';
    };

    const marginLeftMediumValue = isConnectedFullPageView ? '0px' : '245px';
    const marginLeftXLValue = isConnectedFullPageView ? '0px' : '264px';

    useEffect(() => {
      let page = location.pathname?.replace(/[\/_-]/g, '');
      let analyticsInfo = {
        objectName: 'BU Page',
        actionName: 'Displayed',
        screen: page,
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
          version: 'v1',
          page: page,
          bu_title: selectedProduct?.product?.title,
          // TODO: check later with analytics team
          // error_reason: product.type === 'access_denied_page' ? 'access_denied_page' : 'no_error',
          experiment_name: ANALYTICS_ONENAV_EXPERIMENT,
        },
      };
      analyticsTrack(analyticsInfo);
    }, []);

    return (
      <Box
        marginLeft={{ base: '0px', m: marginLeftMediumValue, xl: marginLeftXLValue }}
        height={`calc(-${getTopNavHeight()} + 100vh)`}
      >
        <Box
          overflowY="scroll"
          height="100%"
          backgroundColor="surface.background.gray.intense"
          padding={{ base: 'spacing.3', m: 'spacing.5' }}
          position="relative"
        >
          {/* <TestModeHighlight product={product} /> */}
          <WrappedComponent {...restProps} />
        </Box>
      </Box>
    );
  };

  LayoutWrappedComponent.displayName = `withConnectedLayout(${
    WrappedComponent.displayName || WrappedComponent.name || 'Component'
  })`;

  return LayoutWrappedComponent;
};

export default withConnectedLayout;
