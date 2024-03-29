import React from 'react';
import { Box, useTheme } from '@razorpay/blade/components';

import RizeLogo from 'merchant/views/RizeMarketplace/common/components/RizeLogo';

const RizeTag = ({ showFlap = false }: { showFlap?: boolean }): JSX.Element => {
  const { theme } = useTheme();
  return (
    <Box
      backgroundImage="linear-gradient(91deg, #FE6650 -21.29%, #E13386 64.77%)"
      paddingX="spacing.5"
      paddingTop="6px"
      paddingBottom="spacing.1"
      position="relative"
      borderRadius={showFlap ? 'none' : 'medium'}
    >
      <RizeLogo
        color={theme.colors.interactive.icon.staticWhite.normal}
        height="18px"
        width="auto"
      />
      {showFlap ? (
        <Box display="inline-flex" position="absolute" top="100%" left="spacing.0" marginTop="-1px">
          <svg xmlns="http://www.w3.org/2000/svg" width="8" height="11" fill="none">
            <path fill="#F75960" d="M9 11 0 .5h9V11Z" />
          </svg>
        </Box>
      ) : null}
    </Box>
  );
};

export default RizeTag;
