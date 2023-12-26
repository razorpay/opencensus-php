import React, { Fragment } from 'react';
import {
  Box,
  TextInput,
  Checkbox,
  Text,
  HelpCircleIcon,
  RadioGroup,
  Radio,
  Switch,
  Title,
  Link,
  Button,
  EditIcon,
} from '@razorpay/blade/components';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { titleCase } from 'common/utils/rzp-utils';
import {
  METHODS,
  PROVIDER_KEYS,
  INSTANT_PROVIDER_UNSUPPORTED_METHODS,
  METHODS_MAP,
  TPV_OPTIONS,
} from 'merchant/views/Navigator/constants';

import WalletAutoDebit from './WalletAutoDebit';
import WalletsMultiSelect from './WalletsMultiSelect';

const ProviderConfiguration = (props) => {
  const {
    isFormEdit,
    selectedProvider,
    providers,
    provider,
    isPaytmAutoDebitEnabled,
    hasSeamlessOption,
    validationErrors,
    changeGatewayDetails,
    changeGatewayWallets,
    changeEnableAutoDebitSwitch,
    onLinkClick,
    onEditClick,
    isSubmitting,
    isSubmitDisabled,
    onSubmit,
  } = props;

  const { Gateway_details } = provider;

  const selectedProviderDetails = providers?.[selectedProvider] ?? {};
  const walletOptions =
    selectedProviderDetails?.['Payment Methods']?.meta_data?.wallet_metadata?.wallets ?? [];

  // Check if 'Sodexo' is enabled in provider
  const isSodexoSupported = selectedProviderDetails?.hasOwnProperty(PROVIDER_KEYS.SODEXO);

  // check if "Integration Type" is "Instant (beta) / true" or "S2S / false"
  const isS2SEnabled = !Gateway_details?.optimizer_seamless_disabled;

  // 'sodexo' is not supported for instant integration, only available in S2S integration
  const isSodexoEnabled = isSodexoSupported && isS2SEnabled;

  const PAYTM_AUTO_DEBIT_FIELDS = ['ENABLE_AUTO_DEBIT', 'CLIENT_KEY', 'CLIENT_SECRET'];
  const paytmAutoDebitFields = [0, 1, 2];

  // Filter out the fields that are required in this step3.
  const fields = Object.entries(selectedProviderDetails).reduce((acc, [label, value]) => {
    if (!['Gateway Name', 'optimizer_seamless_disabled', PROVIDER_KEYS.SODEXO].includes(label)) {
      // Need to show auto debit fields at the end of the list in mentioned order
      if (selectedProvider === 'paytm' && PAYTM_AUTO_DEBIT_FIELDS.includes(label)) {
        if (label === 'ENABLE_AUTO_DEBIT') {
          paytmAutoDebitFields[0] = { label, ...value };
        } else if (label === 'CLIENT_KEY') {
          paytmAutoDebitFields[1] = { label, ...value };
        } else if (label === 'CLIENT_SECRET') {
          paytmAutoDebitFields[2] = { label, ...value };
        }
      } else {
        acc.push({ label, ...value });
      }
    }
    return acc;
  }, []);

  const handlePaymentMethods = ({ event, value, isChecked }) => {
    changeGatewayDetails({ name: event?.target?.name, value, isChecked });
  };

  const isMethodCheckboxDisabled = (method) => {
    if (!isFormEdit) return true;

    const isCardChecked = Gateway_details?.['Payment Methods']?.includes(METHODS.CARD);

    // Paytm onboarding enabled wallet method by default
    if (selectedProvider === 'paytm' && method === 'wallet') return true;

    // Payu disable sodexo if card method is not selected
    if (method === METHODS.SODEXO && !isCardChecked) {
      return true;
    }

    return false;
  };

  const shouldShowField = (fieldLabel) => {
    if (fieldLabel === 'Recurring') {
      // to enable 'Recurring' either 'card' or 'upi' method should be enabled
      return Gateway_details?.['Payment Methods']?.some(
        (method) => method === METHODS.CARD || method === METHODS.UPI,
      );
    }

    return false;
  };

  const isSodexoCheckboxDisabled = isMethodCheckboxDisabled(METHODS.SODEXO);

  return (
    <Box
      display="flex"
      flexDirection="column"
      padding="spacing.7"
      gap={isFormEdit ? 'spacing.9' : 'spacing.6'}
      backgroundColor="surface.background.level2.lowContrast"
    >
      <Box display="flex" justifyContent="space-between">
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Title color="surface.text.subtle.lowContrast">
            {selectedProviderDetails?.['Gateway Name']?.data_value || ''} Production API Details
          </Title>
          {isFormEdit && (
            <Box>
              <Text as="p" color="surface.text.subtle.lowContrast">
                Please make sure you{' '}
                <Text as="span" weight="bold">
                  enter the production API details
                </Text>{' '}
                only and{' '}
                <Text as="span" weight="bold">
                  NOT the Test Details
                </Text>
              </Text>
              <Box display="flex" alignItems="center" marginTop="spacing.2">
                <HelpCircleIcon
                  size="medium"
                  color="action.icon.link.default"
                  marginRight="spacing.2"
                />
                <Link onClick={onLinkClick} variant="anchor" size="small">
                  Where do I find {selectedProviderDetails?.['Gateway Name']?.data_value || ''} API
                  Keys details?
                </Link>
              </Box>
            </Box>
          )}
        </Box>

        <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="end">
          {selectedProvider && !isFormEdit ? (
            <Button
              icon={EditIcon}
              onClick={() => onEditClick(4)}
              variant="tertiary"
              iconPosition="left"
            >
              Edit details
            </Button>
          ) : (
            <Text color="surface.text.subdued.lowContrast">
              {hasSeamlessOption ? 'STEP 4 OUT OF 4' : 'STEP 3 OUT OF 3'}
            </Text>
          )}
        </Box>
      </Box>

      <Box display="flex" flexDirection="column" gap="spacing.7">
        {fields.map(({ label = '', data_type, data_value }) => {
          if (data_type === 'array') {
            if (label === 'Payment Methods') {
              return (
                <Fragment key={label}>
                  <Box display="flex">
                    <Box minWidth="180px">
                      <Text>{label}</Text>
                    </Box>
                    {isFormEdit ? (
                      <Box>
                        {data_value
                          .filter((method) => {
                            // For Instant (beta) on-boarding there are certain methods not supported
                            const IS_METHOD_UNSUPPORTED =
                              Gateway_details?.optimizer_seamless_disabled &&
                              INSTANT_PROVIDER_UNSUPPORTED_METHODS[selectedProvider]?.includes(
                                method,
                              );

                            return !IS_METHOD_UNSUPPORTED;
                          })
                          .map((method) => (
                            <Checkbox
                              key={method}
                              name={label}
                              value={method}
                              isChecked={Gateway_details?.['Payment Methods']?.includes(method)}
                              isDisabled={isMethodCheckboxDisabled(method)}
                              onChange={handlePaymentMethods}
                            >
                              {METHODS_MAP?.[method] ?? method}
                            </Checkbox>
                          ))}
                        {isSodexoEnabled && (
                          <Checkbox
                            key={`${PROVIDER_KEYS.SODEXO}-${
                              Gateway_details?.[PROVIDER_KEYS.SODEXO]
                            }`}
                            name={PROVIDER_KEYS.SODEXO}
                            value={METHODS.SODEXO}
                            isChecked={Gateway_details?.[PROVIDER_KEYS.SODEXO]}
                            isDisabled={isSodexoCheckboxDisabled}
                            onChange={handlePaymentMethods}
                          >
                            {METHODS_MAP[METHODS.SODEXO]}
                            {isSodexoCheckboxDisabled && (
                              <Popover theme="dark" align="right">
                                <PopoverBody>
                                  To activate the Sodexo feature, please make sure to enable the
                                  &quot;card&quot; method.
                                </PopoverBody>
                              </Popover>
                            )}
                          </Checkbox>
                        )}
                        <Text type="subdued" variant="caption">
                          Select the payment methods to be enabled for{' '}
                          {selectedProviderDetails?.['Gateway Name']?.data_value || ''}.
                        </Text>
                      </Box>
                    ) : (
                      <Text weight="bold">
                        {Gateway_details?.['Payment Methods']
                          ?.map((method) => METHODS_MAP?.[method] ?? method)
                          ?.join(', ')}
                      </Text>
                    )}
                  </Box>

                  {walletOptions?.length > 0 &&
                    Gateway_details?.['Payment Methods']?.includes('wallet') && (
                      <WalletsMultiSelect
                        walletOptions={walletOptions}
                        walletSelected={Gateway_details?.wallet_metadata?.wallets}
                        changeGatewayWallets={changeGatewayWallets}
                        disabled={selectedProvider === 'paytm'} // For paytm wallets get enabled by default
                        isFormEdit={isFormEdit}
                      />
                    )}
                </Fragment>
              );
            } else if (label === 'TPV') {
              return (
                <Box key={label} display="flex">
                  <Box minWidth="180px">
                    <Box display="flex" alignItems="center">
                      <Text>{label}</Text>
                      <Box display="flex" alignItems="center" marginLeft="spacing.2">
                        <HelpCircleIcon
                          testID="tpv-info-icon"
                          size="medium"
                          color="feedback.icon.neutral.lowContrast"
                        />
                        <Popover theme="dark" align="right">
                          <PopoverBody>
                            Third-Party Validation (TPV) of your customer’s bank accounts in
                            real-time. It is a mandatory requirement for merchants in the BFSI
                            (Banking, Financial Services and Insurance) sector.
                          </PopoverBody>
                        </Popover>
                      </Box>
                    </Box>
                  </Box>
                  <Box minWidth="280px">
                    {isFormEdit ? (
                      <RadioGroup
                        name={label}
                        defaultValue={Gateway_details?.TPV ?? -1}
                        onChange={changeGatewayDetails}
                      >
                        <Radio value={0}>Non TPV</Radio>
                        <Radio value={1}>TPV Only</Radio>
                        <Radio value={2}>Both (TPV and Non TPV)</Radio>
                      </RadioGroup>
                    ) : (
                      <Text weight="bold">{TPV_OPTIONS[Gateway_details?.TPV] || ''}</Text>
                    )}
                  </Box>
                </Box>
              );
            }
          } else if (data_type === 'bool' && shouldShowField(label)) {
            const labelText = Gateway_details?.[label] ? 'Enabled' : 'Disabled';
            return (
              <Box key={label} display="flex">
                <Box minWidth="180px" marginTop="spacing.3">
                  <Text>{titleCase(label)}</Text>
                </Box>
                <Box minWidth="280px">
                  {isFormEdit ? (
                    <>
                      <Box as="label" display="flex" alignItems="center" gap="spacing.2">
                        <Switch
                          value={label}
                          isChecked={Gateway_details?.[label]}
                          onChange={changeGatewayDetails}
                          accessibilityLabel="Toggle Recurring"
                        />
                        <Text type="subdued">{labelText}</Text>
                      </Box>
                      <Text type="subdued" variant="caption">
                        Available for Card and Netbanking, coming soon for UPI.
                      </Text>
                    </>
                  ) : (
                    <Text weight="bold">{labelText}</Text>
                  )}
                </Box>
              </Box>
            );
          } else if (data_type === 'string') {
            return (
              <Box key={label} display="flex" alignItems="center">
                <Box minWidth="180px">
                  <Text>{titleCase(label)}</Text>
                </Box>
                <Box minWidth="280px">
                  {isFormEdit ? (
                    <TextInput
                      name={label}
                      placeholder={data_value}
                      value={Gateway_details?.[label] || ''}
                      type={data_type === 'string' ? 'text' : 'number'}
                      validationState={!!validationErrors?.[label] ? 'error' : 'none'}
                      errorText={validationErrors[label]}
                      onChange={changeGatewayDetails}
                      isRequired={true}
                      gap={8}
                    />
                  ) : (
                    <Text weight="bold">{Gateway_details?.[label] || ''}</Text>
                  )}
                </Box>
              </Box>
            );
          }
          return null;
        })}
        {selectedProvider === 'paytm' &&
          isPaytmAutoDebitEnabled &&
          paytmAutoDebitFields?.map(({ label = '', data_type, data_value }) => {
            if (label === 'ENABLE_AUTO_DEBIT') {
              return (
                <WalletAutoDebit
                  key={label}
                  label={label}
                  provider={provider}
                  changeEnableAutoDebitSwitch={changeEnableAutoDebitSwitch}
                />
              );
            }
            if (Gateway_details?.ENABLE_AUTO_DEBIT) {
              return (
                <Box key={label} display="flex" alignItems="center">
                  <Box minWidth="180px">
                    <Text>{titleCase(label)}</Text>
                  </Box>

                  <Box minWidth="280px">
                    {isFormEdit ? (
                      <TextInput
                        name={label}
                        placeholder={data_value}
                        value={Gateway_details?.[label] || ''}
                        type={data_type === 'string' ? 'text' : 'number'}
                        validationState={!!validationErrors?.[label] ? 'error' : 'none'}
                        errorText={validationErrors[label]}
                        onChange={changeGatewayDetails}
                        necessityIndicator="required"
                        gap={8}
                      />
                    ) : (
                      <Text weight="bold">{Gateway_details?.[label] || ''}</Text>
                    )}
                  </Box>
                </Box>
              );
            }
            return null;
          })}
      </Box>

      {isFormEdit && (
        <Box display="flex" justifyContent="end">
          <Box display="flex" alignItems="center" gap="spacing.7">
            <Button isLoading={isSubmitting} isDisabled={isSubmitDisabled} onClick={onSubmit}>
              Submit
            </Button>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default ProviderConfiguration;
