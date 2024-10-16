import React, { memo, useState } from 'react';
import {
  Box,
  Button,
  Radio,
  RadioGroup,
  Text,
  Heading,
  EditIcon,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import SeamlessNote from 'merchant/views/Navigator/components/Provider/SeamlessNote';
import {
  SEAMLESS_CONTENT,
  RAZORPAY_GATEWAY_KEY,
  PROVIDER_KEYS,
} from 'merchant/views/Navigator/constants';

/**
 * Instant(beta) -> optimizer_seamless_disabled is true, which means seamless option is disabled
 * Server-to-Server -> optimizer_seamless_disabled is false, which means seamless option is enabled
 *
 * Handle Account type input as well for optimizer_razorpay gateway
 */

const IntegrationType = (props) => {
  const {
    isEdit,
    isFormEdit,
    providers,
    selectedProvider,
    gatewayDetails,
    toggleIntegrationType,
    changeGatewayDetails,
    validateStep,
    onNextClick,
    onEditClick,
    updateV3Flow,
  } = props;

  const [isBankingVasAccount, setIsBankingVasAccount] = useState(
    !!gatewayDetails?.[PROVIDER_KEYS.GATEWAY_ACQUIRER] ?? null,
  );

  const integrationType = gatewayDetails?.optimizer_seamless_disabled
    ? 'Instant (beta)'
    : 'Server-to-Server';

  const RadioFeedback = memo(({ isEdit, providers, selectedProvider, gatewayDetails }) => {
    const contentExist = SEAMLESS_CONTENT?.hasOwnProperty(selectedProvider);
    const seamlessSelected = gatewayDetails?.hasOwnProperty('optimizer_seamless_disabled'); // user has selected a value

    if (!(contentExist && seamlessSelected)) return null;

    return (
      <SeamlessNote
        type="info"
        seamlessDisabled={gatewayDetails?.optimizer_seamless_disabled}
        providers={providers}
        selectedProvider={selectedProvider}
        isEdit={isEdit}
      />
    );
  });

  /**
   * Render integration type block (required for the gateways where we have support for instant and s2s integrations)
   */
  const RenderIntegrationBlock = memo(
    ({
      gatewayDetails,
      isEdit,
      toggleIntegrationType,
      providers,
      selectedProvider,
      integrationType,
    }) => {
      if (isFormEdit) {
        return (
          <Box display="flex" flexDirection="column" gap="spacing.8">
            <Box>
              <RadioGroup
                size="medium"
                marginBottom="spacing.5"
                name="optimizer_seamless_disabled"
                defaultValue={gatewayDetails?.optimizer_seamless_disabled}
                onChange={toggleIntegrationType}
                testID="integration-type"
              >
                <Radio value={true}>Instant (beta)</Radio>
                <Radio value={false}>Server-to-Server</Radio>
              </RadioGroup>
              <RadioFeedback
                isEdit={isEdit}
                providers={providers}
                gatewayDetails={gatewayDetails}
                selectedProvider={selectedProvider}
              />
            </Box>
          </Box>
        );
      }
      return (
        <Box display="flex" flexDirection="column" gap="spacing.5">
          <Box display="flex" alignItems="center">
            <Box minWidth="180px">
              <Text>Integration type</Text>
            </Box>
            <Box minWidth="280px">
              <Text weight="semibold">{integrationType}</Text>
            </Box>
          </Box>
        </Box>
      );
    },
  );

  const onAccountTypeChange = (event) => {
    const isBankingVasAccountValue = event.value === 'banking_vas';
    setIsBankingVasAccount(isBankingVasAccountValue);
    event.value = isBankingVasAccountValue ? '' : 'razorpay';
    changeGatewayDetails(event);
  };

  const onBankChange = (event) => {
    event.value = event.values[0]; // in blade-dropdown event we get values array
    changeGatewayDetails(event);
  };

  /**
   * Render account type block (required for optimizer_razorpay gateway)
   */
  const RenderAccountBlock = memo(
    ({ isBankingVasAccount, providers, selectedProvider, isFormEdit, gatewayDetails }) => {
      const gatewayAcquirer = gatewayDetails?.[PROVIDER_KEYS.GATEWAY_ACQUIRER];
      const bankList =
        providers?.[selectedProvider]?.[PROVIDER_KEYS.GATEWAY_ACQUIRER]?.data_value || [];
      const { name: bankName } = bankList.find((bank) => bank?.value === gatewayAcquirer) || {
        name: '',
      };
      return (
        <Box display="flex" flexDirection="column" gap="spacing.5">
          <Box display="flex">
            <Box minWidth="180px">
              <Text>Account type</Text>
            </Box>
            <Box minWidth="280px">
              {isFormEdit ? (
                <RadioGroup
                  name={PROVIDER_KEYS.GATEWAY_ACQUIRER}
                  defaultValue={
                    isBankingVasAccount
                      ? 'banking_vas'
                      : gatewayDetails?.[PROVIDER_KEYS.GATEWAY_ACQUIRER]
                  }
                  onChange={onAccountTypeChange}
                  testID="account-type"
                >
                  <Radio value="razorpay">Regular</Radio>
                  <Radio value="banking_vas">Banking VAS</Radio>
                </RadioGroup>
              ) : (
                <Text weight="semibold">
                  {gatewayAcquirer === 'razorpay' ? 'Regular' : 'Banking VAS'}
                </Text>
              )}
            </Box>
          </Box>
          {isBankingVasAccount && (
            <Box display="flex" alignItems="center">
              <Box minWidth="180px">
                <Text>Bank</Text>
              </Box>
              <Box minWidth="280px">
                {isFormEdit ? (
                  <Dropdown selectionType="single">
                    <SelectInput
                      placeholder="Select bank"
                      name={PROVIDER_KEYS.GATEWAY_ACQUIRER}
                      value={[gatewayDetails?.[PROVIDER_KEYS.GATEWAY_ACQUIRER]]}
                      onChange={onBankChange}
                    />
                    <DropdownOverlay>
                      <ActionList>
                        {bankList.map((bank) => (
                          <ActionListItem key={bank.value} title={bank.name} value={bank.value} />
                        ))}
                      </ActionList>
                    </DropdownOverlay>
                  </Dropdown>
                ) : (
                  <Text weight="semibold">{bankName}</Text>
                )}
              </Box>
            </Box>
          )}
        </Box>
      );
    },
  );

  // Account type selection for razorpay gateway
  const isAccountType = selectedProvider === RAZORPAY_GATEWAY_KEY;

  const header = `Select ${isAccountType ? 'account' : 'integration'} type`;
  const subText = `Select the type of ${
    isAccountType ? 'account' : 'integration'
  } for the selected gateway`;
  const editButtonText = `Edit ${isAccountType ? 'account type' : 'Integration'}`;

  return (
    <Box
      display="flex"
      flexDirection="column"
      padding="spacing.7"
      gap={isFormEdit ? 'spacing.9' : 'spacing.6'}
      backgroundColor="surface.background.gray.intense"
    >
      <Box display="flex" justifyContent="space-between">
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Heading color="surface.text.gray.subtle" size="large">
            {header}
          </Heading>
          {isFormEdit && <Text color="surface.text.gray.subtle">{subText}</Text>}
        </Box>

        <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="end">
          {isFormEdit || (isEdit && isAccountType) ? (
            <Text color="surface.text.gray.muted">STEP 2 OUT OF 4</Text>
          ) : (
            !updateV3Flow && (
              <Button
                icon={EditIcon}
                onClick={() => onEditClick(2)}
                variant="tertiary"
                iconPosition="left"
              >
                {editButtonText}
              </Button>
            )
          )}
        </Box>
      </Box>
      {isAccountType ? (
        <RenderAccountBlock
          isBankingVasAccount={isBankingVasAccount}
          providers={providers}
          selectedProvider={selectedProvider}
          isFormEdit={isFormEdit}
          gatewayDetails={gatewayDetails}
        />
      ) : (
        <RenderIntegrationBlock
          gatewayDetails={gatewayDetails}
          isEdit={isEdit}
          toggleIntegrationType={toggleIntegrationType}
          providers={providers}
          selectedProvider={selectedProvider}
          integrationType={integrationType}
        />
      )}
      {isFormEdit && (
        <Box display="flex" justifyContent="end">
          <Box display="flex" alignItems="center" gap="spacing.7">
            <Button isDisabled={validateStep(2)} onClick={onNextClick}>
              Next
            </Button>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default IntegrationType;
