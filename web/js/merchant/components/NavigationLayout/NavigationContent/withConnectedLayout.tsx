import React, { useEffect, useMemo } from 'react';
import { Box, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { ONE_NAV_MOBILE_PATH } from '../constants';
import { useLocation } from 'react-router-dom';
import TestModeHighlight from '../components/TestModeHighlight';
import useConnectedProducts from '../hooks/useConnectedProducts';
import useConnectedNavigationStore from '../navigationStore';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { ANALYTICS_ONENAV } from '@libs/shared-utils';

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

    const { theme } = useTheme();
    const { matchedDeviceType } = useBreakpoint({
      breakpoints: theme.breakpoints,
    });
    const location = useLocation();

    const isMobile = matchedDeviceType === 'mobile';
    const { isConnectedFullPageView = false, ...restProps } = props;

    const marginLeftMediumValue = isConnectedFullPageView ? '0px' : '245px';
    const marginLeftXLValue = isConnectedFullPageView ? '0px' : '264px';

    useEffect(() => {
      const page = location.pathname?.replace(/[\/_-]/g, '');
      const analyticsInfo = {
        objectName: 'BU Page',
        actionName: 'Displayed',
        screen: ANALYTICS_ONENAV.SCREEN,
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
          version: 'v1',
          page,
          bu_title: selectedProduct?.product?.title,
          page_type: productAction?.type,
          experiment_name: ANALYTICS_ONENAV.EXPERIMENT_NAME,
        },
      };
      analyticsTrack(analyticsInfo);
    }, []);

    return (
      <Box
        marginLeft={{ base: '0px', m: marginLeftMediumValue, xl: marginLeftXLValue }}
        height="100%"
      >
        <Box
          overflowY="scroll"
          height="100%"
          backgroundColor="surface.background.gray.moderate"
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
