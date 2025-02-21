import React, { useState } from 'react';
import { Box, Button, PlusIcon, Text } from '@razorpay/blade/components';
import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { DEFAULT_PAYMENT_CONFIG } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

import { CreateConfigModal } from './CreateConfigModal';
import { PointerDivWrapper } from './styled';

export type CreateNewConfigurationProps = {
  onCreateConfigCTAClick: (newConfig: MerchantCheckoutPaymentConfig) => void;
};

export function CreateNewConfiguration({ onCreateConfigCTAClick }: CreateNewConfigurationProps) {
  const [isConfigNameModalOpen, setIsConfigNameModalOpen] = useState(false);

  function handleCreateNewConfigClick() {
    setIsConfigNameModalOpen(true);
  }

  function handleConfigNameModalSave(name: string) {
    const newConfig: MerchantCheckoutPaymentConfig = {
      ...DEFAULT_PAYMENT_CONFIG,
      name,
      config_id: '',
    };
    setIsConfigNameModalOpen(false);
    onCreateConfigCTAClick(newConfig);
  }

  return (
    <>
      <PointerDivWrapper onClick={handleCreateNewConfigClick}>
        <Box
          display="flex"
          justifyContent="center"
          padding={['spacing.4', 'spacing.5']}
          alignItems="center"
          alignSelf="stretch"
          gap="spacing.4"
          borderRadius="large"
          borderStyle="dashed"
          borderColor="surface.border.gray.muted"
        >
          <Button
            variant="secondary"
            size="small"
            icon={PlusIcon}
            onClick={handleCreateNewConfigClick}
          />
          <Box flexGrow="1">
            <Text size="medium" weight="medium">
              Create a custom payment configuration
            </Text>
          </Box>
        </Box>
      </PointerDivWrapper>
      <CreateConfigModal
        isOpen={isConfigNameModalOpen}
        onClose={() => {
          setIsConfigNameModalOpen(false);
        }}
        onSave={handleConfigNameModalSave}
        ctaText="Create"
      />
    </>
  );
}
