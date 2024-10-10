import React from 'react';

import { Box, Text, TextInput } from '@razorpay/blade/components';

import { BrandNameTextInputProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';

const BrandNameTextInput: React.FC<BrandNameTextInputProps> = ({ brandName, setBrandName }) => {
  const handleChange = ({ value = '' }: { value?: string }) => {
    setBrandName(value);
  };

  return (
    <Box width="100%">
      <Text
        weight="semibold"
        size="medium"
        color="surface.text.gray.muted"
        marginBottom={'spacing.3'}
      >
        Brand name
      </Text>
      <TextInput
        label=""
        name="brand_name"
        onChange={handleChange}
        value={brandName}
        placeholder="Enter brand or business name"
      />
    </Box>
  );
};

export default BrandNameTextInput;
