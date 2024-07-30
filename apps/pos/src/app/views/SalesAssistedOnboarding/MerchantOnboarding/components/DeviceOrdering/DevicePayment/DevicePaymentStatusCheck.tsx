import React, { useState } from 'react';
import moment from 'moment';
import { Alert, Box, Button } from '@razorpay/blade/components';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';

interface DevicePaymentStatusCheckProps {
  isUpdateModularLoading: boolean;
  handleModularUpdate: (payload: ModularPayload) => void;
}

const DevicePaymentStatusCheck = ({
  isUpdateModularLoading,
  handleModularUpdate,
}: DevicePaymentStatusCheckProps): JSX.Element => {
  const [isClicked, setIsClicked] = useState(false);

  const onStatusCheckAttempt = () => {
    setIsClicked(true);
  };

  const handlePaymentStatusCheck = () => {
    const payload: ModularPayload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_QR_PAYMENT_STATUS_CHECK]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: onStatusCheckAttempt,
    };
    handleModularUpdate(payload);
  };

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      padding="spacing.5"
      position="fixed"
      left="0px"
      right="0px"
      bottom="0px"
      elevation="highRaised"
      width="100%"
      zIndex={1}
    >
      {isClicked && !isUpdateModularLoading ? (
        <Alert
          color="notice"
          title="Payment Pending"
          description="Payment from the merchant hasn’t been intiated yet"
          marginBottom="spacing.5"
          isDismissible={false}
          isFullWidth
        />
      ) : null}
      <Button onClick={handlePaymentStatusCheck} isLoading={isUpdateModularLoading} isFullWidth>
        {isClicked ? 'Refresh' : 'Check Payment Status'}
      </Button>
    </Box>
  );
};

export default DevicePaymentStatusCheck;
