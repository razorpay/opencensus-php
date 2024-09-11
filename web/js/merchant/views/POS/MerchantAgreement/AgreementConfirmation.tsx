import React, { useEffect } from 'react';
import { BladeProvider, Box, Button, Heading, Text, useTheme } from '@razorpay/blade/components';
import { StyledAgreementContainer, StyledBanner } from './styles';
import { bladeTheme } from '@razorpay/blade/tokens';
import CheckmarkCircle from 'assets/circle-check.svg';
import SuccessTick from 'assets/success-check.svg';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useNavigate } from 'react-router-dom';
import { POS_BENEFITS } from 'merchant/views/POS/constants';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const POSAgreementConfirmation = (): JSX.Element => {
  const { theme } = useTheme();
  const navigate = useNavigate();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isSmallMobile = ['xs', 'base'].includes(matchedBreakpoint as string);

  const checkmarkStyle = {
    position: 'relative',
    top: '4px',
  };

  const handleConfirmationClick = () => {
    analyticsTrack({
      objectName: 'Website CTA',
      actionName: 'Clicked',
      screen: 'Agreement signing',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        label: 'Okay, got it',
        section: 'Agreement Signing confirmation',
        subSection: ' Merchant Signing-online',
        l1FunnelStage: 'Agreement Signing confirmation',
        l2FunnelStage: 'Merchant Signing-online',
      },
    });
    navigate('/app/dashboard');
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'Page',
      actionName: 'Viewed',
      screen: 'Agreement signing',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        pageType: 'Merchant Signing-online',
        l1FunnelStage: 'Agreement Signing confirmation',
        l2FunnelStage: 'Merchant Signing-online',
      },
    });
  }, []);

  return (
    <BladeProvider themeTokens={bladeTheme} colorScheme="light">
      <StyledAgreementContainer padding="32px 20px">
        <StyledBanner>
          <Box maxWidth="350px" margin="auto">
            <Box textAlign="center" marginBottom="spacing.7">
              <img src={SuccessTick} alt="Success checkmark" />
            </Box>
            <Heading
              color="surface.text.staticWhite.normal"
              size="large"
              weight="semibold"
              marginBottom="spacing.6"
              textAlign="center"
            >
              Thank you for the confirmation!
            </Heading>
            <Text
              size="small"
              textAlign="center"
              marginBottom="spacing.6"
              color="surface.text.gray.muted"
            >
              You will receive a copy of the agreement on your registered email.
            </Text>
          </Box>
        </StyledBanner>
        <Box
          backgroundColor="surface.background.gray.intense"
          borderRadius="large"
          padding={['spacing.8', 'spacing.6']}
          marginTop="spacing.6"
        >
          <Box maxWidth="324px" margin="auto">
            <Box
              display="flex"
              flexDirection="column"
              gap={isSmallMobile ? 'spacing.6' : 'spacing.10'}
            >
              <Box>
                {POS_BENEFITS.map((benefit, index) => (
                  <Box display="flex" alignItems="flex-start" gap="spacing.6" key={index}>
                    {/* eslint-disable-next-line i18n-rules/no-region-specific-image */}
                    <img style={checkmarkStyle} src={CheckmarkCircle} alt="Small green checkmark" />
                    <Text
                      weight={isSmallMobile ? 'semibold' : 'regular'}
                      size="small"
                      marginBottom="spacing.7"
                      color="surface.text.gray.subtle"
                    >
                      {benefit}
                    </Text>
                  </Box>
                ))}
              </Box>
              <Button onClick={handleConfirmationClick} isFullWidth>
                Okay, got it
              </Button>
            </Box>
          </Box>
        </Box>
      </StyledAgreementContainer>
    </BladeProvider>
  );
};

export default POSAgreementConfirmation;
