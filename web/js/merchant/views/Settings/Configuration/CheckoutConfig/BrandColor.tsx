import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { ColorTextInput } from './ColorTextInput';
import { useCheckoutConfig } from './context';

const BrandColor = (): JSX.Element => {
  const { values, handleBrandColorChange } = useCheckoutConfig();

  return (
    <Box display="flex" flexDirection="column" gap="spacing.3">
      <Text weight="semibold" color="surface.text.gray.subtle">
        Brand Color
      </Text>
      <ColorTextInput
        name="brand_color"
        value={values.color}
        helpText={
          <>
            Choose a theme color for your brand.
            <br />
            The default theme color will be used if none is specified.
          </>
        }
        onChange={handleBrandColorChange}
      />
    </Box>
  );
};

export default BrandColor;
