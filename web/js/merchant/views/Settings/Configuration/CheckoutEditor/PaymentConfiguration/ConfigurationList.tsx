import React from 'react';
import { Text, Box } from '@razorpay/blade/components';

import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { ConfigurationListItem } from './ConfigurationListItem';
import { CreateNewConfiguration } from './CreateNewConfiguration';

export type Configuration = {
  name: string;
  description: string;
  onCTAClick: () => void;
  ctaText: string;
  isActive: boolean;
  isDefault: boolean;
};

export function ConfigurationList() {
  const { values } = useCheckoutEditor();
  const configs = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_CONFIGS];

  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];

  return (
    <Box display="flex" flexDirection="column" gap="spacing.7">
      <Box display="flex" flexDirection="column" gap="spacing.4">
        <Text size="small">Razorpay's payment configuration</Text>
      </Box>

      {configs.length > 0 && (
        <Box display="flex" flexDirection="column" gap="spacing.4">
          <Text size="small">Your saved payment configurations</Text>
          <Box display="flex" flexDirection="column" gap="spacing.5">
            {configs.map((config, index) => {
              return (
                <ConfigurationListItem
                  key={index}
                  isActive={config.config_id === selectedConfig?.config_id}
                  config={config}
                />
              );
            })}
          </Box>
        </Box>
      )}

      <Box display="flex" flexDirection="column" gap="spacing.4">
        <CreateNewConfiguration />
      </Box>
    </Box>
  );
}
