import React, { useEffect } from 'react';
import { Box, Text, Button, Heading, Divider } from '@razorpay/blade/components';
import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';
import { setItemInLocalStorage } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';

const FTUXBanner = ({ handleClose }) => {
  const userId = useStore((state) => state?.session?.user?.user?.id || '');

  useEffect(() => {
    setItemInLocalStorage(`showOnenavFtuxBanner_${userId}`, 'false');
  }, []);

  const FtuxBannerArrow = styled.div(
    ({ theme }: { theme: Theme }) => `
    top: -${theme.spacing[3]}px;
    left: 50%;
    transform: translateX(-50%);
    position: absolute;
    border-left: ${theme.border.radius['large']}px solid transparent;
    border-right: ${theme.border.radius['large']}px solid transparent;
    border-bottom: ${theme.border.radius['large']}px solid ${theme.colors.popup.background.intense};
    @media (max-width: ${theme.breakpoints.l}px) {
      left: 40%;
    }
  `,
  );

  const FtuxBannerWrapper = styled.div(
    ({ theme }: { theme: Theme }) => `
    padding: ${theme.spacing[8]}px ${theme.spacing[7]}px ${theme.spacing[7]}px;
    display: flex;
    margin: ${theme.spacing[5]}px ${theme.spacing[5]}px;
    align-items: center;
    background-color: ${theme.colors.popup.background.intense};
    border-radius: ${theme.border.radius['large']}px;
    position: relative;
    @media (max-width: ${theme.breakpoints.l}px) {
      flex-direction: column;
      align-items: flex-start;
      gap: ${theme.spacing[7]}px;
      padding-top: ${theme.spacing[7]}px;
    }
  `,
  );

  return (
    <FtuxBannerWrapper>
      <FtuxBannerArrow />
      <Box display="grid" paddingRight="spacing.8">
        <Heading weight="semibold" size="large" color="surface.text.gray.normal">
          The New, all-in-one
        </Heading>
        <Text variant="body" weight="medium" size="large" color="surface.text.gray.subtle">
          Razorpay Dashboard
        </Text>
      </Box>
      <Divider orientation="vertical" display={{ base: 'block', l: 'block', m: 'none' }} />
      <Box
        paddingLeft={{ base: 'spacing.5', l: 'spacing.5', m: 'spacing.0' }}
        display="flex"
        justifyContent="space-between"
        alignItems="flex-end"
        flexGrow="1"
        width={{ base: 'auto', l: 'auto', m: '100%' }}
      >
        <Text
          variant="body"
          weight="medium"
          size="large"
          color="surface.text.gray.subtle"
          marginRight="spacing.7"
        >
          All your Razorpay products are now in one place. <br />
          The tabs above will let you quickly switch between the different offerings by Razorpay.
        </Text>
        <Box flexShrink="0">
          <Button onClick={handleClose} variant="primary" color="white" size="medium">
            Got it
          </Button>
        </Box>
      </Box>
    </FtuxBannerWrapper>
  );
};

export default FTUXBanner;
