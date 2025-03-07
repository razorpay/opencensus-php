import React, { useState } from 'react';
import { Box, Button, Heading, Text, IconButton, CloseIcon } from '@razorpay/blade/components';
import { useTheme, useBreakpoint } from '@razorpay/blade/utils';
import datasyncicon from 'assets/DataSync.png';
import { useStore } from '@federated/apps/shell/commonStore';

import { useMobile } from 'common/hooks/useMobile';
import { analyticsTrack } from 'common/utils/analytics';
import { getItem, setItem } from 'common/utils/localStorage';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { BannerContainer } from './styled';

export const DataSyncAdvertisementBanner = () => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({ breakpoints: theme.breakpoints });
  const isMobile = useMobile();
  const session = useStore((state) => state.session);

  const [isVisible, setIsVisible] = useState(getItem('hideDataSyncBanner') !== 'true');

  const user = session?.user;

  const handleClick = (e, actionName: string) => {
    e.stopPropagation();
    analyticsTrack({
      objectName: 'DataSyncAdvertisementBanner',
      actionName,
      screen: 'reports',
      properties: {
        actionName,
        ...getCommonAnalyticsProperties(user),
      },
    });
    window.open(
      `https://razorpay.typeform.com/to/Y62qsGje#mid=${user?.current}&name=${user?.user?.name}&email=${user?.user?.email}`,
      '_blank',
    );
  };

  const handleClose = (e) => {
    e.stopPropagation();
    setIsVisible(false);
    setItem('hideDataSyncBanner', 'true');
  };

  if (!isVisible) return null;
  return (
    <BannerContainer onClick={(e) => handleClick(e, 'Data Sync Advertisement Banner Clicked')}>
      <Box
        backgroundColor="surface.background.gray.intense"
        display="flex"
        justifyContent="space-between"
        alignItems="center"
        height="77px"
        overflow="hidden"
      >
        <Box display="flex" alignItems="center">
          <Box marginRight="spacing.6">
            {matchedBreakpoint == 'l' || matchedBreakpoint == 'xl' ? (
              <img style={{ maxWidth: '200px' }} src={datasyncicon} alt="Razorpay DataSync" />
            ) : null}
          </Box>

          <Box>
            <Heading size={isMobile ? 'small' : 'medium'}>
              Unlock Real-Time Data Access with{' '}
              <Heading
                as="span"
                size={isMobile ? 'small' : 'medium'}
                color="surface.text.primary.normal"
              >
                Razorpay DataSync!
              </Heading>
            </Heading>

            {matchedBreakpoint === 'xl' ? (
              <Text
                variant="body"
                size="medium"
                weight="semibold"
                marginTop="spacing.2"
                color="surface.text.staticBlack.disabled"
              >
                Streamline your data management with real-time reporting straight to your data
                warehouse.
              </Text>
            ) : null}
          </Box>
        </Box>

        <Box display="flex" marginRight="spacing.5" justifyContent="space-between" gap="spacing.4">
          <Button
            size={isMobile ? 'small' : 'medium'}
            variant="primary"
            onClick={(e) => handleClick(e, 'Get Started Button Clicked')}
          >
            Get Started
          </Button>
          <IconButton
            icon={CloseIcon}
            size="medium"
            onClick={handleClose}
            accessibilityLabel="Close"
          />
        </Box>
      </Box>
    </BannerContainer>
  );
};
