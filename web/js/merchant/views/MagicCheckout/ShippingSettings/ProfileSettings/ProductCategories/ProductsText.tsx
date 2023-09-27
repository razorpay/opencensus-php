import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

const ProductsText = ({ text }: { text: string }): JSX.Element => {
  return (
    <Box
      paddingX="spacing.6"
      paddingY="spacing.5"
      backgroundColor="surface.background.level2.lowContrast"
    >
      <Text weight="bold">{text}</Text>
    </Box>
  );
};

export default ProductsText;
