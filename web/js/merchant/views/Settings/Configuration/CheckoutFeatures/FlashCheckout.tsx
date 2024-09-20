import React from 'react';
import { Box, Text, Switch } from '@razorpay/blade/components';

import TextHighlighter from 'common/ui/TextHighlighter';
import { FLASH_CHECKOUT } from 'merchant/views/Settings/Configuration/deeplink-constants';
import { flashCheckoutProps } from 'merchant/views/Settings/Configuration/settings-config-constants';

import { CHECKOUT_FEATURE_FIELDS } from './context/constants';
import { useCheckoutFeatures } from './context/createContext';

const FlashCheckout = () => {
  const { values, handleFlashCheckoutToggle } = useCheckoutFeatures();

  const handleSwitchChange = ({ isChecked }: { isChecked: boolean }) => {
    handleFlashCheckoutToggle(isChecked);
  };

  return (
    <Box display="flex" gap="spacing.5">
      <Box flex="1">
        <Text weight="semibold" color="surface.text.gray.subtle">
          <TextHighlighter hashedWith={FLASH_CHECKOUT}>{flashCheckoutProps.title}</TextHighlighter>
        </Text>
        <Text color="surface.text.gray.muted">{flashCheckoutProps.desc}</Text>
      </Box>
      <Switch
        accessibilityLabel="enable flash checkout"
        isChecked={values[CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]}
        onChange={handleSwitchChange}
      />
    </Box>
  );
};

export default FlashCheckout;
