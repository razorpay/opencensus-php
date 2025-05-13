import React from 'react';
import { Box, Text, Heading, CheckIcon, Avatar, Button, Link } from '@razorpay/blade/components';
import { SuccessContentProps } from './types';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import { useNavigate } from 'react-router-dom';

export const SuccessContent = ({
  activationLink = '',
  onDismiss,
}: SuccessContentProps & { onDismiss?: () => void }) => {
  const { isSSOEnabled } = useSSOContext();

  const navigate = useNavigate();

  return (
    <>
      <Avatar size="large" icon={CheckIcon} color="positive" />
      {activationLink ? (
        <>
          <Box display="flex" flexDirection="column" alignItems="center">
            <Heading>Almost there!</Heading>
            <Text textAlign="center">We need to add a custom block to your Shopify theme</Text>
          </Box>
          <img
            src="https://betacdn.np.razorpay.in/static/e46d3a5ee89865fb51b735306dd910aba036da87/assets/razorpay-sso/save-theme-editor.gif"
            alt="SSO"
            width="100%"
          />
          <Box width="100%">
            <Button
              isFullWidth
              variant="primary"
              onClick={() => window.open(activationLink, '_blank', 'noopener noreferrer')}
            >
              Go to shopify theme editor
            </Button>
            <Text
              color="surface.text.gray.muted"
              textAlign="center"
              size="small"
              marginTop="spacing.1"
            >
              You'll just need to click 'Save' in the theme editor
            </Text>
          </Box>
        </>
      ) : (
        <>
          <Heading textAlign="center">Razorpay Login configuration has been saved</Heading>
          {isSSOEnabled && (
            <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.2">
              <Text textAlign="center">
                <Link
                  onClick={() => {
                    navigate('/magic/settings/sso/settings');
                    onDismiss?.();
                  }}
                >
                  Setup Razorpay Login
                </Link>
              </Text>
              <Text textAlign="center">
                <Link
                  onClick={() => {
                    navigate('/magic/settings/sso/customize');
                    onDismiss?.();
                  }}
                >
                  Customize the login widget as per your store
                </Link>
              </Text>
            </Box>
          )}
        </>
      )}
    </>
  );
};
