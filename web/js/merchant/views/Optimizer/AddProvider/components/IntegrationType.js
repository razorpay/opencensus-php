import React, { memo } from 'react';
import { Box, Button, Radio, RadioGroup, Text, Title, EditIcon } from '@razorpay/blade/components';

import SeamlessNote from 'merchant/views/Navigator/components/Provider/SeamlessNote';
import { SEAMLESS_CONTENT } from 'merchant/views/Navigator/constants';

/**
 * Instant(beta) -> optimizer_seamless_disabled is true, which means seamless option is disabled
 * Server-to-Server -> optimizer_seamless_disabled is false, which means seamless option is enabled
 */

const IntegrationType = (props) => {
  const {
    isEdit,
    isFormEdit,
    providers,
    selectedProvider,
    gatewayDetails,
    toggleIntegrationType,
    validateStep,
    onNextClick,
    onEditClick,
  } = props;

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
          <Title color="surface.text.subtle.lowContrast">Select integration type</Title>
          {isFormEdit && (
            <Text color="surface.text.subtle.lowContrast">
              Select the type of integration for the selected gateway
            </Text>
          )}
        </Box>

        <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="end">
          {isFormEdit ? (
            <Text color="surface.text.subdued.lowContrast">STEP 2 OUT OF 4</Text>
          ) : (
            <Button
              icon={EditIcon}
              onClick={() => onEditClick(2)}
              variant="tertiary"
              iconPosition="left"
            >
              Edit Integration
            </Button>
          )}
        </Box>
      </Box>

      {isFormEdit ? (
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
              seamlessDisabled
              selectedProvider={selectedProvider}
            />
          </Box>
        </Box>
      ) : (
        <Box display="flex" flexDirection="column" gap="spacing.5">
          <Box display="flex" alignItems="center">
            <Box minWidth="180px">
              <Text>Integration type</Text>
            </Box>
            <Box minWidth="280px">
              <Text weight="bold">{integrationType}</Text>
            </Box>
          </Box>
        </Box>
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
