import React from 'react';
import { Box, Text, IconButton, CloseIcon } from '@razorpay/blade/components';

import { QuickGuideStepProps } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

import { IMAGE_PATH } from './constant';

const QuickGuideStep = ({ title, onCloseClick, tiles = [] }: QuickGuideStepProps) => {
  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      display={{ base: 'none', m: 'flex' }}
      flexDirection="column"
      padding="spacing.7"
    >
      <Box
        display="flex"
        flexDirection="row"
        justifyContent="space-between"
        alignItems="flex-start"
      >
        <Text marginBottom="spacing.7" weight="semibold" size="large">
          {title}
        </Text>
        <IconButton
          accessibilityLabel="quick-guide-close"
          icon={() => <CloseIcon color="interactive.icon.gray.normal" />}
          onClick={onCloseClick}
        />
      </Box>
      <Box display="flex" flexDirection="row" paddingLeft="spacing.5" paddingRight="spacing.5">
        <img width="170px" height="auto" src={IMAGE_PATH.QUICK_GUIDE_BANNER} />
        <Box
          display="flex"
          flexDirection="row"
          justifyContent="space-between"
          width="100%"
          marginLeft="spacing.10"
        >
          {tiles.map(({ title, content }) => (
            <Box
              display="flex"
              flexDirection="column"
              alignItems="center"
              flex="1"
              maxWidth="280px"
              key={title}
            >
              <img height="37px" width="37px" src={IMAGE_PATH.QUICK_GUIDE_ICON} />
              <Text weight="semibold" marginBottom="spacing.4" marginTop="spacing.5">
                {title}
              </Text>
              <Text textAlign="center" size="small">
                {content}
              </Text>
            </Box>
          ))}
        </Box>
      </Box>
    </Box>
  );
};

export default QuickGuideStep;
