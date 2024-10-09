import {
  ArrowRightIcon,
  Box,
  Button,
  CheckIcon,
  Link,
  Text,
  TextInput,
  useToast,
} from '@razorpay/blade/components';
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import { DEVICE_TESTING_AMOUNT_REGEX } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/constants';

interface DeviceTestingProp {
  description: string;
  defaultTestingAmount: string;
  isUpdateModularLoading: boolean;
  deviceName: string;
  handleUpdateModularConfig: (payload: ModularPayload) => void;
  handleProceed: () => void;
}

const DeviceTesting = ({
  description,
  defaultTestingAmount,
  isUpdateModularLoading,
  deviceName,
  handleUpdateModularConfig,
}: DeviceTestingProp): JSX.Element | null => {
  const { isMobile } = useScreen();
  const toast = useToast();
  const [testAmount, setTestAmount] = useState<string | undefined>(defaultTestingAmount);
  const [showAmountResend, setShowAmoutResend] = useState<boolean>(false);
  const [amountSent, setAmountSent] = useState<boolean>(false);
  const [showAmountError, setShowAmountError] = useState<boolean>(false);
  const navigate = useNavigate();

  const sendAmountCallback = () => {
    setAmountSent(true);
    setShowAmoutResend(true);
    toast.show({
      content: 'Amount sent successfully',
      color: 'positive',
      autoDismiss: true,
    });
  };

  const handleProceed = () => {
    navigate(-1);
  };

  const handleSendAmount = () => {
    const payload = {
      [DEVICE_DEPLOYMENT_FIELDS.DEVICE_TESTING_AMOUNT_FIELD]: Number(testAmount),
      [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: sendAmountCallback,
    };
    handleUpdateModularConfig(payload);
  };

  const handleAmountReceived = () => {
    const payload = {
      [DEVICE_DEPLOYMENT_FIELDS.DEVICE_TESTING_AMOUNT_RECEIVED_FIELD]: true,
      [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: handleProceed,
    };
    handleUpdateModularConfig(payload);
  };

  const handleAmountChange = (value: string) => {
    if (DEVICE_TESTING_AMOUNT_REGEX.test(value)) {
      setTestAmount(value);
      setShowAmountError(false);
    } else {
      setShowAmountError(true);
    }
  };

  return (
    <Box padding={['spacing.5', 'spacing.6']}>
      <Text weight="semibold" size="large">
        {description}
      </Text>
      <Box display="flex" marginTop="spacing.6" gap="spacing.3">
        <Text color="surface.text.gray.subtle" weight="medium" size="large">
          Model Details
        </Text>
        <Text color="surface.text.gray.subtle" weight="regular" size="large">
          {deviceName}
        </Text>
      </Box>
      <Box marginTop="spacing.6">
        <TextInput
          type="number"
          label="Enter Amount"
          labelPosition="top"
          name="testAmount"
          onChange={({ value = '' }) => handleAmountChange(value)}
          value={testAmount}
          placeholder="Enter Test Amount"
          size="medium"
          prefix="₹"
          validationState={showAmountError ? 'error' : 'none'}
          errorText={'Enter numeric value only'}
        />
      </Box>
      {showAmountResend ? (
        <Box display="flex" gap="spacing.3" marginTop="spacing.4">
          <Text weight="regular" size="medium" color="surface.text.gray.subtle">
            Didn't receive the Amount?
          </Text>
          <Link onClick={handleSendAmount} variant="button">
            Re-send Amount
          </Link>
        </Box>
      ) : null}

      <Box
        display="flex"
        justifyContent="center"
        position={{ base: 'fixed', l: 'relative' }}
        bottom="0px"
        padding="spacing.4"
        backgroundColor={{
          base: 'surface.background.gray.intense',
          l: 'transparent',
        }}
        left="0px"
        right="0px"
        zIndex="1"
      >
        <Button
          icon={amountSent ? CheckIcon : ArrowRightIcon}
          onClick={amountSent ? handleAmountReceived : handleSendAmount}
          size={isMobile ? 'medium' : 'large'}
          isFullWidth
          iconPosition={amountSent ? 'left' : 'right'}
          isLoading={isUpdateModularLoading}
        >
          {amountSent ? 'Amount Received' : 'Send Amount'}
        </Button>
      </Box>
    </Box>
  );
};

export default DeviceTesting;
