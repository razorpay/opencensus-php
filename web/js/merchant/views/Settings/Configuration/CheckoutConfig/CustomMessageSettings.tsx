import React from 'react';
import { Box, Text, TextArea, MinusCircleIcon, Link, Switch } from '@razorpay/blade/components';

import IntoView from 'common/ui/IntoView';
import TextHighlighter from 'common/ui/TextHighlighter';
import { ColorTextInput } from 'merchant/views/Settings/Configuration/CheckoutConfig/ColorTextInput';
import { useCheckoutConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context';
import { CHECKOUT_CUSTOM_MESSAGE } from 'merchant/views/Settings/Configuration/deeplink-constants';

// hard coding the tab index to 0 for now
const TAB_INDEX = 0;

const CustomMessageSettings = () => {
  const {
    values,
    handleCustomMessageToggle,
    handleCustomMessageTextChange,
    handleCustomMessageBackgroundColorChange,
    handleCustomMessageTextColorChange,
  } = useCheckoutConfig();

  const allConfigs = values.customMessage.configs;

  const currentConfig = allConfigs[TAB_INDEX];

  const handleSwitchChange = ({ isChecked }: { isChecked: boolean }) => {
    handleCustomMessageToggle(isChecked);
  };

  const handleTextChange = ({ value }: { value?: string | undefined }) => {
    handleCustomMessageTextChange(TAB_INDEX, value ?? '');
  };

  const handleClearSection = () => {
    handleCustomMessageTextChange(TAB_INDEX, '');
  };

  const handleBackgroundColorChange = (evt: React.ChangeEvent) => {
    const { value } = evt.target as HTMLInputElement;

    handleCustomMessageBackgroundColorChange(TAB_INDEX, value);
  };

  const handleTextColorChange = (evt: React.ChangeEvent) => {
    const { value } = evt.target as HTMLInputElement;

    handleCustomMessageTextColorChange(TAB_INDEX, value);
  };

  return (
    <IntoView hashedWith={CHECKOUT_CUSTOM_MESSAGE}>
      <Box display="flex" gap="spacing.5">
        <Box flex="1">
          <Text weight="semibold" color="surface.text.gray.subtle">
            <TextHighlighter hashedWith={CHECKOUT_CUSTOM_MESSAGE}>
              Show a custom message to the user on your checkout
            </TextHighlighter>
          </Text>
          <Text color="surface.text.gray.muted">
            Choose a personalised banner message to customers at any checkout step
          </Text>
        </Box>
        <Switch
          accessibilityLabel="enable custom banner message"
          isChecked={values.customMessage.isEnabled}
          onChange={handleSwitchChange}
        />
      </Box>
      {values.customMessage.isEnabled && (
        <Box
          backgroundColor="surface.background.gray.moderate"
          paddingX="spacing.5"
          paddingTop="spacing.5"
          paddingBottom="spacing.7"
          borderRadius="large"
          marginTop="spacing.3"
        >
          <TextArea
            name="bannerMessageText"
            label="Banner Message Text"
            placeholder="Add banner message text here (max 45 words)"
            maxCharacters={45}
            value={currentConfig.bannerMessageText}
            onChange={handleTextChange}
          />

          {TAB_INDEX > 0 && (
            <Box display="flex" justifyContent="center" gap="spacing.7">
              <Link
                icon={MinusCircleIcon}
                variant="button"
                color="neutral"
                onClick={handleClearSection}
              >
                Clear section
              </Link>
            </Box>
          )}

          <Box display="flex" gap="spacing.9" marginTop="spacing.4">
            <ColorTextInput
              label="Banner Color"
              name="bannerBackgroundColor"
              value={currentConfig.bannerBackgroundColor}
              onChange={handleBackgroundColorChange}
            />
            <ColorTextInput
              label="Text Color"
              name="bannerTextColor"
              value={currentConfig.bannerTextColor}
              onChange={handleTextColorChange}
            />
          </Box>
        </Box>
      )}
    </IntoView>
  );
};

export default CustomMessageSettings;
