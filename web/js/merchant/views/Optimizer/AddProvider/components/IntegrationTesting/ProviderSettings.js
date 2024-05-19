import React, { useState } from 'react';
import {
  Box,
  Heading,
  Text,
  Switch,
  Divider,
  CheckboxGroup,
  Checkbox,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
} from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import { METHODS_MAP } from 'merchant/views/Navigator/constants';

export const ProviderSettings = ({
  providerName,
  gatewayMetaData,
  methods,
  setMethods,
  integrationType,
}) => {
  const methodsList = [...gatewayMetaData?.['Payment Methods']?.data_value] || [];
  // Move wallet method at the end
  if (methodsList.includes('wallet')) {
    methodsList.splice(methodsList.indexOf('wallet'), 1);
    methodsList.push('wallet');
  }
  // Add sodexo method for PayU gateway
  if (gatewayMetaData?.Sodexo && !methodsList.includes('sodexo') && integrationType === 's2s') {
    methodsList.splice(methodsList.indexOf('card') + 1, 0, 'sodexo');
  }

  if (gatewayMetaData?.Recurring && !methodsList.includes('recurring')) {
    methodsList.splice(methodsList.indexOf('wallet'), 0, 'recurring');
  }

  const wallets = gatewayMetaData?.['Payment Methods']?.meta_data?.wallet_metadata?.wallets || [];
  const wallets1List = wallets?.slice(
    0,
    wallets?.length % 2 === 0 ? wallets?.length / 2 : wallets?.length / 2 + 1,
  );
  const wallets2List = wallets?.slice(
    wallets?.length % 2 === 0 ? wallets?.length / 2 : wallets?.length / 2 + 1,
  );

  // To show wallets in 2 different checkboxgroups in 2 columns
  const [wallets1, setWallets1] = useState(wallets1List);
  const [wallets2, setWallets2] = useState(wallets2List);

  const showWallets = !!methods.wallet;

  const changeMethods = ({ isChecked, value }) => {
    let wallet_metadata = {};
    if (value === 'wallet' && isChecked) {
      setWallets1(wallets1List);
      setWallets2(wallets2List);
      wallet_metadata = {
        wallets,
      };
    } else if (value === 'wallet' && !isChecked) {
      setWallets1([]);
      setWallets2([]);
      wallet_metadata = {};
    } else if (value !== 'wallet') {
      wallet_metadata = methods?.wallet_metadata;
    }
    setMethods({
      ...methods,
      [value]: isChecked,
      wallet_metadata,
    });
  };

  const changeWallets = ({ name, values }) => {
    let newWallets = [];
    if (name === 'wallets1') {
      setWallets1(values);
      newWallets = [...wallets2, ...values];
    } else if (name === 'wallets2') {
      setWallets2(values);
      newWallets = [...wallets1, ...values];
    }
    setMethods({
      ...methods,
      wallet_metadata: {
        wallets: newWallets,
      },
    });
  };

  const sodexoTooltip =
    'To activate the Sodexo feature, please make sure to enable the "card" method.';
  const recurringTooltip = 'Available for Card and UPI, coming soon for Netbanking.';

  return (
    <Box display="flex" flexDirection="column" padding="spacing.8">
      <Heading size="medium">Provider Settings</Heading>
      <Text as="p" marginTop="spacing.5" size="small" color="surface.text.gray.normal">
        You can take a final decision about which methods to enable and disable for {providerName}
      </Text>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.5"
        padding={['spacing.4', 'spacing.6']}
        marginTop="spacing.7"
        borderColor="surface.border.gray.subtle"
        borderWidth="thin"
        borderRadius="small"
        width="650px"
      >
        <Box display="flex" flexDirection="column" gap="spacing.4">
          {methodsList?.map((method, index) => (
            <>
              <Box display="flex" gap="spacing.4">
                <Box display="flex" gap="spacing.2" alignItems="center" width="200px">
                  <Text color="surface.text.gray.subtle">
                    {METHODS_MAP[method] || titleCase(method)}
                  </Text>
                  {['sodexo', 'recurring'].includes(method) && (
                    <Tooltip
                      content={method === 'sodexo' ? sodexoTooltip : recurringTooltip}
                      onOpenChange={function noRefCheck() {}}
                      placement="right"
                      zIndex={99999}
                    >
                      <TooltipInteractiveWrapper>
                        <InfoIcon marginTop="spacing.2" size="medium" />
                      </TooltipInteractiveWrapper>
                    </Tooltip>
                  )}
                </Box>
                <Box display="flex" gap="spacing.2">
                  <Switch
                    accessibilityLabel={METHODS_MAP[method] || titleCase(method)}
                    size="medium"
                    name={method}
                    value={method}
                    isChecked={methods[method]}
                    onChange={changeMethods}
                    isDisabled={
                      (method === 'sodexo' && !methods.card) ||
                      (method === 'recurring' && !methods.card && !methods.upi)
                    }
                  />
                </Box>
              </Box>
              {index < methodsList.length - 1 && <Divider />}
            </>
          ))}
          {showWallets && (
            <Box display="flex" gap="spacing.4">
              <Box display="flex" gap="spacing.2" width="200px" />
              <Box display="flex" flexDirection="column" gap="spacing.5">
                <Box display="flex" gap="spacing.7">
                  <CheckboxGroup name="wallets1" onChange={changeWallets} defaultValue={wallets1}>
                    {wallets1List?.map((wallet) => (
                      <Checkbox key={wallet} value={wallet}>
                        {wallet}
                      </Checkbox>
                    ))}
                  </CheckboxGroup>
                  <CheckboxGroup name="wallets2" onChange={changeWallets} defaultValue={wallets2}>
                    {wallets2List?.map((wallet) => (
                      <Checkbox key={wallet} value={wallet}>
                        {wallet}
                      </Checkbox>
                    ))}
                  </CheckboxGroup>
                </Box>
              </Box>
            </Box>
          )}
        </Box>
      </Box>
    </Box>
  );
};
