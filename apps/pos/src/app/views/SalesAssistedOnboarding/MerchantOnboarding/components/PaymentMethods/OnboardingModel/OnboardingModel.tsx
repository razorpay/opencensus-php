import React, { useState } from 'react';
import {
  Box,
  Button,
  Radio,
  RadioGroup,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheetFooter,
} from '@razorpay/blade/components';
import { PaymentMethodFormType } from 'apps/pos/src/app/types/PaymentsAndService';

const OnboardingModel = ({ isOpen, setIsOpen, setFormType }): JSX.Element => {
  const [paymentMethodFormType, setPaymentMethodFormType] = useState<PaymentMethodFormType>(
    PaymentMethodFormType.AGGREGATOR,
  );

  const onDismissClick = () => {
    setIsOpen(false);
  };

  const onRadioButtonChange = (event) => {
    setPaymentMethodFormType(event.value);
  };

  const onBackButtonClick = () => {
    setIsOpen(false);
  };

  const onProceedButtonClick = () => {
    setFormType(paymentMethodFormType);
    setIsOpen(false);
  };

  return (
    <BottomSheet data-testid="pricing-bottom-sheet" isOpen={isOpen} onDismiss={onDismissClick}>
      <BottomSheetHeader title="Select Onboarding Model" />
      <BottomSheetBody>
        <Box padding="spacing.4">
          <RadioGroup onChange={onRadioButtonChange} value={paymentMethodFormType}>
            <Radio testID="aggregator-model" value={PaymentMethodFormType.AGGREGATOR}>
              Aggregator Model
            </Radio>
            <Radio testID="direct-model" value={PaymentMethodFormType.DIRECT}>
              Direct Model
            </Radio>
          </RadioGroup>
        </Box>
      </BottomSheetBody>
      <BottomSheetFooter>
        <Box display="flex" flexDirection="row" justifyContent="space-between">
          <Button variant="secondary" onClick={onBackButtonClick}>
            Back
          </Button>
          <Button
            testID="acquisition-model-proceed"
            variant="primary"
            onClick={onProceedButtonClick}
          >
            Proceed
          </Button>
        </Box>
      </BottomSheetFooter>
    </BottomSheet>
  );
};

export default OnboardingModel;
