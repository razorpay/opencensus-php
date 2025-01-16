import React, { useEffect, useState } from 'react';
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
import { trackEvent } from 'apps/pos/src/services/analytics';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  FIELD_TYPES,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
} from 'apps/pos/src/services/analytics/types';
import { ModularOnboardingField } from 'apps/pos/src/app/types/modular';

type OnboardingModelProps = {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  setFormType: (formType: PaymentMethodFormType) => void;
  acquisitionModelField: PaymentMethodFormType;
  acquisitionModelFields: ModularOnboardingField | null;
};
const OnboardingModel = ({
  isOpen,
  setIsOpen,
  setFormType,
  acquisitionModelField,
  acquisitionModelFields,
}:OnboardingModelProps): JSX.Element => {
  const [paymentMethodFormType, setPaymentMethodFormType] = useState<PaymentMethodFormType>(
    acquisitionModelField || PaymentMethodFormType.AGGREGATOR,
  );
  
  const onDismissClick = () => {
    trackEvent({
      eventName: ANALYTICS_EVENTS.ICON,
      action: ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Close Icon',
        l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage: L2_FUNNEL_STAGE.ONBOARDING_MODEL,
        section: 'Payment Method & Service Selection',
        subSection: 'Onboarding Model',
      },
    });
    setIsOpen(false);
  };

  const onRadioButtonChange = (event) => {
    trackEvent({
      eventName: ANALYTICS_EVENTS.FORM_FIELD,
      action: ANALYTICS_ACTIONS.SELECTED,
      properties: {
        formName: 'Onboarding Model',
        fieldName: 'Onboarding Model',
        fieldType: FIELD_TYPES.RADIO_BUTTON,
        l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage:
          event.value === PaymentMethodFormType.AGGREGATOR
            ? L2_FUNNEL_STAGE.ONBOARDING_OPTION_AGGREGATOR
            : L2_FUNNEL_STAGE.ONBOARDING_OPTION_DIRECT,
        section: 'Payment Method & Service Selection',
        subSection: 'Onboarding Model',
      },
    });
    setPaymentMethodFormType(event.value);
  };

  const onBackButtonClick = () => {
    setIsOpen(false);
  };

  const onProceedButtonClick = () => {
    setFormType(paymentMethodFormType);
    setIsOpen(false);
  };

  useEffect(() => {
    trackEvent({
      eventName: ANALYTICS_EVENTS.FORM_PAGE,
      action: ANALYTICS_ACTIONS.VIEWED,
      properties: {
        formName: 'Onboarding Model screen',
        section: 'Payment Method & Service Selection',
        subSection: 'Onboarding Model',
        l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage: L2_FUNNEL_STAGE.ONBOARDING_MODEL,
      },
    });
    setPaymentMethodFormType(acquisitionModelField);
  }, [acquisitionModelField]);

  
  return (
    <BottomSheet zIndex={99999} isOpen={isOpen} onDismiss={onDismissClick}>
      <BottomSheetHeader title="Select Onboarding Model" />
      <BottomSheetBody>
        <Box padding="spacing.4">
          <RadioGroup onChange={onRadioButtonChange} value={paymentMethodFormType} isDisabled={acquisitionModelFields?.isDisabled}>
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
