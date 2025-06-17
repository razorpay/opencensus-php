import React from 'react';
import { Box } from '@razorpay/blade/components';
import CommsBannerImage from 'assets/pos/comms-banner-img.webp';
import { STATUS_ASSETS_MAPPING } from 'apps/pos/src/app/views/SelfServe/constants';
import { useBladeBreakpoints, useLatestOrder } from 'apps/pos/src/app/views/SelfServe/hooks';
import CommsBannerItem from './CommsBannerItem';
import { getMerchantComms } from './getMerchantComms';
import { StyledImage } from './styles';
import { RazorpayUser as User } from '@libs/shared-types';

type CommsBanner = {
  user: User;
  mode: 'test' | 'live';
};

const CommsBanner = ({ mode, user }: CommsBanner): JSX.Element | null => {
  const { latestOrder, isLatestOrderLoading, latestOrderFetchError } = useLatestOrder();

  const { isMobile, matchedBreakpoint } = useBladeBreakpoints();
  const isMobileOrTablet = isMobile || matchedBreakpoint === 'm';

  if (!user || latestOrderFetchError || isLatestOrderLoading) return null;

  const stages = getMerchantComms({ user, mode, order: latestOrder, isMobileOrTablet });
  if (!stages.length) return null;

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      marginX={{ base: 'spacing.4', xl: 'spacing.6' }}
      marginTop="75px"
      borderWidth="thin"
      borderColor="surface.border.gray.muted"
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
