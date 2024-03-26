import React from 'react';
import { Box } from '@razorpay/blade/components';

import imageFullLogo from 'assets/logo_full.png';

import { StyledTextHeading } from './styled';

const Frame: React.FC<{
  backgroundImageSrc: string;
  imgDimension: any;
}> = ({ children, backgroundImageSrc, imgDimension }) => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      alignItems="center"
      justifyContent="center"
      marginTop="spacing.5"
    >
      <Box display="flex" alignItems="center" justifyContent="center" padding="spacing.4">
        <StyledTextHeading>#RazorpayRewind</StyledTextHeading>
      </Box>
      <Box
        backgroundImage={`url(${backgroundImageSrc})`}
        minWidth={imgDimension}
        minHeight={imgDimension}
        maxHeight={imgDimension}
        maxWidth={imgDimension}
        backgroundRepeat="no-repeat"
        backgroundSize="contain"
        position="relative"
      >
        {children}
      </Box>
      <Box display="flex" alignItems="center" justifyContent="center" padding="spacing.4">
        <img src={imageFullLogo} width="100" role="img" aria-label="brand-logo" alt="brand-logo" />
      </Box>
    </Box>
  );
};

export default Frame;
