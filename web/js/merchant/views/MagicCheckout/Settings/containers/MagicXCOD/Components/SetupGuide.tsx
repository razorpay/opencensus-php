import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import { makeSize } from '@razorpay/blade/utils';
import styled from 'styled-components';

import codSetupGuideThumbnail from 'assets/magic_checkout/cod-setup-guide-thumbnail.png';

import { SETUP_GUIDE_VIDEO_HREF } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/constants';

const StyledLink = styled.a`
  position: relative;
  border: 0;
  outline: 0;
  margin: -1px 0 -1px -1px;
  padding: 0;
  width: ${makeSize(285)};
  min-height: ${makeSize(178)};
  border-radius: ${({ theme }) => makeSize(theme.border.radius.medium)};
  overflow: hidden;
`;

const StyledThumbnail = styled.div`
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
    url('${(props) => props.src}') no-repeat center center/cover;
  width: 285px;
  height: 100%;
  min-height: 178px;
`;

const PlayIcon = styled.i`
  position: absolute;
  top: 50%;
  left: 50%;
  font-size: 400%;
  margin: -24px 0 0 -24px;
  color: #fff;
  opacity: 0.7;
  width: 64px;
  height: 64px;
`;

export const SetupGuide = () => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <Heading size="medium">Setup Guide</Heading>
      <Box
        display="flex"
        gap="spacing.3"
        borderColor="surface.border.gray.muted"
        borderRadius="medium"
        borderWidth="thin"
        backgroundColor="surface.background.gray.moderate"
      >
        <StyledLink
          data-test-id="video-play-btn"
          aria-label="Go to COD setup guide video"
          href={SETUP_GUIDE_VIDEO_HREF}
          target="_blank"
        >
          <StyledThumbnail src={codSetupGuideThumbnail} role="presentation" />
          <PlayIcon className="i i-play-filled-circle" />
        </StyledLink>
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          maxWidth="555px"
          padding="spacing.7"
          paddingLeft="spacing.8"
        >
          <Heading size="medium" weight="semibold">
            How to setup COD configurations for store?
          </Heading>
          <Text color="surface.text.gray.subtle" weight="regular">
            All shipping profiles (product groups) and shipping methods have been synced from
            Shopify. You can use the Configure COD button above to enable/disable COD for your
            respective shipping methods on Shopify. You can also limit COD availability by cart
            amount and disable prepaid options using the configurations above.
          </Text>
        </Box>
      </Box>
    </Box>
  );
};
