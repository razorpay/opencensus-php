import React from 'react';
import { Box, Button, Text } from '@razorpay/blade/components';
import ChatHelp from '@FTUX/assets/ChatHelp.svg';

const BrowseAllProducts = () => {
  return (
    <Box display="flex" flexDirection="row" alignItems="center">
      <Box display="flex" flexDirection="row" alignItems="center">
        <img width="24px" src={ChatHelp} alt="Help" />
        <Text marginLeft="spacing.4">
          Can't find the right product for you? Choose from 10 other no code products
        </Text>
      </Box>
      <Button variant="tertiary" onClick={() => {}} marginLeft="auto">
        Browse all products
      </Button>
    </Box>
  );
};

export default BrowseAllProducts;
