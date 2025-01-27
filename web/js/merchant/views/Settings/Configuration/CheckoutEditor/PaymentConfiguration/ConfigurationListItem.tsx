import React from 'react';
import { Badge, Link, Text } from '@razorpay/blade/components';
import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

export type ConfigurationListItemProps = {
  isActive: boolean;
  config: MerchantCheckoutPaymentConfig;
};

export function ConfigurationListItem({ isActive, config }: ConfigurationListItemProps) {
  const { handleSelectedConfigChange } = useCheckoutEditor();

  function constructConfigurationDescription(config: MerchantCheckoutPaymentConfig) {
    const numberOfBlocks = Object.keys(config?.checkout_config?.display?.blocks ?? {}).length;
    if (numberOfBlocks === 0) {
      return 'No custom blocks added';
    } else if (numberOfBlocks === 1) {
      return '1 custom block added';
    }
    return `${numberOfBlocks} custom blocks added`;
  }

  function handleConfigurationCTAClick(config: MerchantCheckoutPaymentConfig) {
    handleSelectedConfigChange(config);
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
        <Link variant="button" onClick={() => handleConfigurationCTAClick(config)}>
          Edit
        </Link>
      }
      Badge={
        config.is_default ? (
          <Badge color="primary" size="small" emphasis="subtle">
            Default
          </Badge>
        ) : undefined
      }
      isSelected={isActive}
      onItemClick={() => handleSelectedConfigChange(config)}
    />
  );
}
