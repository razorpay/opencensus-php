import React from 'react';

import { Box } from '@razorpay/blade/components';

import MessageTextInput from './MessageTextInput';
import ChangeColorInput from './ChangeColorInput';
import { ExtraItemsWrapper } from './styled';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { TAB_INDEX } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import track from './track';

const ExtraItems = () => {
  const { values, handleCustomMessageTextChange, handleCustomMessageBackgroundColorChange } =
    useCheckoutEditor();

  const allConfigs = values.customMessage.configs;
  const currentConfig = allConfigs[TAB_INDEX];

  const handleTextChange = ({ value }: { value?: string | undefined }) => {
    handleCustomMessageTextChange(TAB_INDEX, value ?? '');
    track.handleMessageBannerTextInput(value);
  };

  const handleBackgroundColorChange = (evt: React.ChangeEvent) => {
    const { value } = evt.target as HTMLInputElement;
    handleCustomMessageBackgroundColorChange(TAB_INDEX, value);
    track.handleMessageBannerThemeEdit();
  };

  return (
    <>
      {values.customMessage.isEnabled && (
        <ExtraItemsWrapper>
          <MessageTextInput
            message="Message on checkout"
            value={currentConfig.bannerMessageText}
            onChange={handleTextChange}
          />

          <Box
            display="flex"
            gap="spacing.5"
            marginTop="spacing.4"
            flexDirection="column"
            justifyContent="center"
            alignItems="flex-start"
          >
            <ChangeColorInput
              value={currentConfig.bannerBackgroundColor}
              onChange={handleBackgroundColorChange}
            />
          </Box>
        </ExtraItemsWrapper>
      )}
    </>
  );
};

export default ExtraItems;
