import React, { useState } from 'react';
import { Merchant } from '@dashboard/shared-utils/graphql/graph-types';
import {
  Alert,
  Box,
  CheckCircleIcon,
  Divider,
  Heading,
  RadioGroup,
  useToast,
} from '@razorpay/blade/components';
import moment from 'moment';
import DeliveryAddressRadio from './DeliveryAddressRadio';
import { getFieldsForDeliveryAddressFromMerchantDetails } from 'apps/pos/src/app/utils/deviceSelection';
import {
  MODULAR_DEVICE_FIELDS,
  DeviceDeliveryAddressTypes,
  OrderSummaryItemWithDeviceConfig,
} from 'apps/pos/src/app/types/DeviceSelection';
import DeviceConfirmationCTA from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DeviceConfirmation/DeviceConfirmationCTA';
import { DeviceCharges, ModularPayload } from 'apps/pos/src/app/types/modular';
import PageError from 'apps/pos/src/app/components/PageError';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
import { isAddressPresent } from 'apps/pos/src/app/utils/modularConfig';
interface DeviceDeliveryAddressProps {
  title: string;
  countryCode: string;
  addedDevices: OrderSummaryItemWithDeviceConfig[];
  orderSummary: DeviceCharges;
  merchantDetails: Merchant;
  handleModularUpdate: (payload: ModularPayload) => void;
  handleGoToNextStep: () => void;
  isUpdateModularLoading: boolean;
  isStepCompleted?: boolean;
}

const DeviceDeliveryAddress = ({
  title,
  countryCode,
  addedDevices,
  orderSummary,
  merchantDetails,
  handleModularUpdate,
  handleGoToNextStep,
  isUpdateModularLoading,
  isStepCompleted,
}: DeviceDeliveryAddressProps): JSX.Element => {
  const [addressType, setAddressType] = useState<DeviceDeliveryAddressTypes>('registered');
  const toast = useToast();

  const onAddressUpdate = () => {
    toast.show({
      content: 'Delivery Address updated successfully',
      color: 'positive',
      leading: CheckCircleIcon,
    });
    handleGoToNextStep();
  };

  const addresses = getFieldsForDeliveryAddressFromMerchantDetails({
    merchant: merchantDetails,
    countryCode,
  });

  const handleOnDeliveryAddressClick = (): void => {
    const addressData = addresses?.[addressType];
    const payload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_DELIVERY_ADDRESS_FIELD]: addressData,
      [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_QR_AMOUNT]: orderSummary?.totalOrderCharge,
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: onAddressUpdate,
    };
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Confirm Delivery Address',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_DELIVERY_ADDRESS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.DELIVERY_ADDRESS_CONFIRMATION,
        section: 'Order Delivery Address',
        subSection: 'Delivery Address Confirmation',
      },
    });
    handleModularUpdate(payload);
  };

  if (!isAddressPresent(merchantDetails?.business?.address?.registered)) {
    return (
      <PageError
        title="Error!"
        description="Please fill business address in KYC journey first and try again"
      />
    );
  }

  return (
    <Box margin="spacing.5">
      <Heading marginBottom="spacing.8" size="large">
        {title}
      </Heading>
      <Box>
        <RadioGroup
          defaultValue={addressType}
          onChange={({ value }) => setAddressType(value as DeviceDeliveryAddressTypes)}
        >
          <DeliveryAddressRadio value="registered" address={addresses?.registered} />
          <Divider orientation="horizontal" marginBottom="spacing.5" />
          <DeliveryAddressRadio value="operation" address={addresses?.operation} />
        </RadioGroup>
      </Box>
      <DeviceConfirmationCTA
        ctaName="Confirm Delivery Address"
        addedDevices={addedDevices}
        orderSummary={orderSummary}
        onCtaClick={handleOnDeliveryAddressClick}
        isLoading={isUpdateModularLoading}
        isDisabled={isStepCompleted}
        extra={
          <Alert
            color="notice"
            title="Are you sure about your order?"
            description="Changes to your order won't be possible after payment. Please review your order carefully before confirming"
            isDismissible={false}
            marginBottom="spacing.4"
            isFullWidth
          />
        }
      />
    </Box>
  );
};

export default DeviceDeliveryAddress;
