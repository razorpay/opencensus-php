import React from 'react';
import { Box, Text, Divider } from '@razorpay/blade/components';

// TODO: Add video source and unhide in phase II
const SSOVideoGuide: React.FC = () => {
  return (
    <Box
      display="none"
      flexDirection="column"
      padding="spacing.6"
      paddingTop="spacing.0"
      textAlign="center"
    >
      <Divider margin="spacing.0" orientation="horizontal" width="100%" marginBottom="spacing.5" />
      <Box marginTop="spacing.8" display="flex" backgroundColor="surface.background.gray.moderate">
        <Box flex="1" display="flex" alignItems="center" justifyContent="center">
          <video
            controls
            style={{
              width: '100%',
              maxWidth: '600px',
              borderRadius: '8px',
              boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)',
            }}
          >
            <source src="path/to/video.mp4" type="video/mp4" />
            Please refresh the page to view the video
          </video>
        </Box>
        <Box flex="2" padding="spacing.6" display="flex" flexDirection="column">
          <Text marginBottom="spacing.4" size="large" weight="medium">
            How to setup Razorpay login for your customers?
          </Text>
          <Text color="surface.text.gray.muted" marginBottom="spacing.6">
            Edit a profile above OR Create a new profile on Shopify (set the rate as required -
            Shipping + COD charges) and sync here. You can edit the profile here to enable just COD
            only and disable prepaid for that specific plan. Then you can set specific COD slabs for
            that profile.
          </Text>
        </Box>
      </Box>
    </Box>
  );
};

export default SSOVideoGuide;
