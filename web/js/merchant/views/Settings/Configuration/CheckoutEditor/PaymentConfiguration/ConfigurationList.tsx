import React from 'react';
import { Text, Box } from '@razorpay/blade/components';
import capitalize from 'lodash/capitalize';
import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { DEFAULT_PAYMENT_CONFIG } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { ConfigurationListItem } from './ConfigurationListItem';
import { CreateNewConfiguration } from './CreateNewConfiguration';
import _track from './track';

type ConfigurationListProps = {
  showConfigDetails: () => void;
  org: { business_name: string };
};

function ConfigurationList({ showConfigDetails, org }: ConfigurationListProps) {
  const { values, handleSelectedConfigChange, handleOriginalPaymentConfigChange } =
    useCheckoutEditor();
  const configs = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_CONFIGS];
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const allPaymentConfigs = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_CONFIGS];

  const isRazorpayConfigSelected = selectedConfig?.config_id === DEFAULT_PAYMENT_CONFIG.config_id;
  const isRazorpayConfigDefault = allPaymentConfigs.length === 0;
  const razorpayConfig = isRazorpayConfigDefault
    ? {
        ...DEFAULT_PAYMENT_CONFIG,
        is_default: true,
      }
    : DEFAULT_PAYMENT_CONFIG;

  function handleConfigurationCTAClick(config: MerchantCheckoutPaymentConfig) {
    showConfigDetails();
    handleSelectedConfigChange({ ...config });
    if (config.config_id) {
      handleOriginalPaymentConfigChange({ ...config });
      _track.paymentConfigEditClicked(config.config_id);
    } else {
      handleOriginalPaymentConfigChange({ ...DEFAULT_PAYMENT_CONFIG });
      _track.createNewPaymentConfigClicked();
    }
  }

  return (
    <Box display="flex" flexDirection="column" gap="spacing.7">
      <Box display="flex" flexDirection="column" gap="spacing.4">
        <Text size="small">{capitalize(org.business_name)}&apos;s payment configuration</Text>
        <ConfigurationListItem
          isActive={isRazorpayConfigSelected}
          config={razorpayConfig}
          onConfigItemCTAClick={() => handleConfigurationCTAClick(DEFAULT_PAYMENT_CONFIG)}
        />
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
                  onConfigItemCTAClick={() => handleConfigurationCTAClick(config)}
                />
              );
            })}
          </Box>
        </Box>
      )}

      <Box display="flex" flexDirection="column" gap="spacing.4">
        <CreateNewConfiguration
          onCreateConfigCTAClick={(config) => handleConfigurationCTAClick(config)}
        />
      </Box>
    </Box>
  );
}

export default compose(connect((state) => ({ org: state.session.org })))(ConfigurationList);
