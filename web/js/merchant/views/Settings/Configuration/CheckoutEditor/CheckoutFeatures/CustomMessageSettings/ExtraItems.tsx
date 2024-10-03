import React from 'react';

import { Box, Link, MinusCircleIcon, TextArea } from '@razorpay/blade/components';
import { ColorTextInput } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ColorTextInput/index';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { TAB_INDEX } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';

const ExtraItems = () => {
  const {
    values,
    handleCustomMessageTextChange,
    handleCustomMessageBackgroundColorChange,
    handleCustomMessageTextColorChange,
  } = useCheckoutEditor();

  const allConfigs = values.customMessage.configs;
  const currentConfig = allConfigs[TAB_INDEX];

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
    <>
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
    </>
  );
};

export default ExtraItems;
