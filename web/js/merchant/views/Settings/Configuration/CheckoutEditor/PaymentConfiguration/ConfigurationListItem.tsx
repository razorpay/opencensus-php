import React from 'react';
import { Badge, Box, CopyIcon, Link, Text, useToast } from '@razorpay/blade/components';
import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import copyToClipboard from 'common/utils/copyToClipboard';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { StyledButton } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/styles';
import { DEFAULT_PAYMENT_CONFIG } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

export type ConfigurationListItemProps = {
  isActive: boolean;
  config: MerchantCheckoutPaymentConfig;
  onConfigItemCTAClick: () => void;
};

function CopyConfigID({ configID }: { configID: string }) {
  const toast = useToast();
  function copyConfigIDToClipboard(configID: string) {
    toast.show({
      content: 'Configuration ID copied to clipboard',
      color: 'neutral',
      autoDismiss: true,
    });
    copyToClipboard(configID);
  }
  return (
    <StyledButton onClick={() => copyConfigIDToClipboard(configID)}>
      <Badge color="neutral" icon={CopyIcon}>
        {configID}
      </Badge>
    </StyledButton>
  );
}

export function ConfigurationListItem({
  isActive,
  config,
  onConfigItemCTAClick,
}: ConfigurationListItemProps) {
  const { handleSelectedConfigChange, handleOriginalPaymentConfigChange } = useCheckoutEditor();
  const isRazorpayConfig = config.config_id === DEFAULT_PAYMENT_CONFIG.config_id;

  function handleConfigItemClick(config) {
    handleSelectedConfigChange({ ...config });
    handleOriginalPaymentConfigChange({ ...config });
  }

  function constructConfigurationDescription(config: MerchantCheckoutPaymentConfig) {
    if (isRazorpayConfig) {
      return 'Cards, Netbanking, Wallets etc';
    }
    const numberOfBlocks = Object.keys(config?.checkout_config?.display?.blocks ?? {}).length;
    if (numberOfBlocks === 0) {
      return 'No custom blocks added';
    } else if (numberOfBlocks === 1) {
      return '1 custom block added';
    }
    return `${numberOfBlocks} custom blocks added`;
  }

  return (
    <ListItemCard
      accessibilityLabel="Configuration List Item"
      Title={
        <Text variant="body" weight="medium" size="medium" color="surface.text.gray.normal">
          {config?.name ?? ''}
        </Text>
      }
      Description={
        <Text variant="body" size="small" weight="regular" color="interactive.text.positive.normal">
          {constructConfigurationDescription(config)}
        </Text>
      }
      RightComponent={
        <Box display="flex" gap="spacing.4">
          {!isRazorpayConfig && <CopyConfigID configID={config.config_id ?? ''} />}
          <Link variant="button" onClick={onConfigItemCTAClick}>
            {isRazorpayConfig ? 'View' : 'Edit'}
          </Link>
        </Box>
      }
      Badge={
        config.is_default ? (
          <Badge color="primary" size="small" emphasis="subtle">
            Default
          </Badge>
        ) : undefined
      }
      isSelected={isActive}
      onItemClick={() => {
        handleConfigItemClick(config);
      }}
    />
  );
}
