import { Box, IconComponent } from '@razorpay/blade/components';
import React from 'react';

interface IconWrapperType {
  Icon: IconComponent;
}

const IconWrapper = ({ Icon }: IconWrapperType) => {
  return (
    <Box
      display="flex"
      alignItems="center"
      justifyContent="center"
      backgroundColor="surface.background.gray.subtle"
      width="40px"
      height="40px"
      marginRight="spacing.3"
      borderRadius="medium"
    >
      <Icon color="surface.icon.gray.muted" />
    </Box>
  );
};

export default IconWrapper;
