import React from 'react';
import { Box, Card, CardBody } from '@razorpay/blade/components';

import {
  CONTACT_SCREEN_NAME,
  TAB_INDEX,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import ChangeColorInput from './ChangeColorInput';
import MessageTextInput from './MessageTextInput';
import track from './track';

const ExtraItems = () => {
  const { values, handleCustomMessageTextChange, handleCustomMessageBackgroundColorChange } =
    useCheckoutEditor();

  const allConfigs = values.customMessage.configs;

  const contactsScreenIndex =
    allConfigs.findIndex((config) => config.name === CONTACT_SCREEN_NAME) ?? TAB_INDEX;
  const currentConfig = allConfigs[contactsScreenIndex];

  const handleTextChange = ({ value }: { value?: string | undefined }) => {
    handleCustomMessageTextChange(contactsScreenIndex, value ?? '');
    track.handleMessageBannerTextInput(value);
  };

  const handleBackgroundColorChange = (evt: React.ChangeEvent) => {
    const { value } = evt.target as HTMLInputElement;
    handleCustomMessageBackgroundColorChange(contactsScreenIndex, value);
    track.handleMessageBannerThemeEdit();
  };

  return values.customMessage.isEnabled ? (
    <Card backgroundColor="surface.background.gray.intense">
      <CardBody>
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
      </CardBody>
    </Card>
  ) : null;
};

export default ExtraItems;
