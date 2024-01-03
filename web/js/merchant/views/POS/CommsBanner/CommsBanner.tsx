import React, { useEffect } from 'react';
import { Box } from '@razorpay/blade/components';
import { useLocation, useParams } from 'react-router-dom';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import CommsBannerImage from 'assets/pos/comms-banner-img.webp';
import { STATUS_ASSETS_MAPPING } from 'merchant/views/POS/constants';
import { useBladeBreakpoints, useLatestOrder } from 'merchant/views/POS/hooks';

import CommsBannerItem from './CommsBannerItem';
import { getMerchantComms } from './getMerchantComms';
import { StyledImage } from './styles';
import { User } from 'common/typings';
import { getCommsAnalytics } from 'merchant/views/POS/helpers';

type CommsBanner = {
  user: User;
  mode: 'test' | 'live';
};

const CommsBanner = ({ mode, user }: CommsBanner): JSX.Element | null => {
  const { latestOrder, isLatestOrderLoading, latestOrderFetchError } = useLatestOrder();

  const { isMobile, matchedBreakpoint } = useBladeBreakpoints();
  const isMobileOrTablet = isMobile || matchedBreakpoint === 'm';

  const { pathname } = useLocation();
  const { productName = '' } = useParams();

  useEffect(() => {
    const { pageType } = getCommsAnalytics({ pathname, productName });
    analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
      pageType,
      orderId: '',
    });
  }, []);

  if (!user || latestOrderFetchError || isLatestOrderLoading) return null;

  const stages = getMerchantComms({ user, mode, order: latestOrder, isMobileOrTablet });
  if (!stages.length) return null;

  return (
    <Box
      backgroundColor="surface.background.level2.lowContrast"
      marginX={{ base: 'spacing.4', xl: 'spacing.6' }}
      marginTop="75px"
      borderWidth="thin"
      borderColor="brand.gray.400.lowContrast"
      position="relative"
      display="flex"
    >
      <Box
        paddingX="spacing.7"
        paddingY={{ base: 'spacing.9', l: 'spacing.10' }}
        display="flex"
        flexDirection={{ base: 'column', l: 'row' }}
        width="100%"
      >
        {stages.map((stage, index) => {
          const isFirst = index === 0;
          const isLast = index === stages.length - 1;

          const leftConnector = {
            isHidden: isFirst,
            variant: isFirst
              ? STATUS_ASSETS_MAPPING.pending.variant
              : STATUS_ASSETS_MAPPING[stages[index - 1]?.status]?.variant,
          };

          const rightConnector = {
            isHidden: isLast,
            variant: STATUS_ASSETS_MAPPING[stages[index]?.status]?.variant,
          };

          return (
            <CommsBannerItem
              key={stage.title}
              commsItem={stage}
              leftConnector={leftConnector}
              rightConnector={rightConnector}
              isLast={isLast}
            />
          );
        })}
      </Box>
      <Box
        display={{ base: 'none', l: 'block' }}
        minHeight="250px"
        height="100%"
        top="0px"
        right="0px"
        maxHeight="380px"
        maxWidth="360px"
        marginLeft="auto"
        overflow="hidden"
      >
        <StyledImage src={CommsBannerImage} height="100%" />
      </Box>
    </Box>
  );
};

export default CommsBanner;
