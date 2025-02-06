import React from 'react';
import { Box, Heading, LinkIcon, QRCodeIcon } from '@razorpay/blade/components';
import { StyledCardContainer } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/styled';
import PaymentMethodCard from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/PaymentLinkMethod/PaymentMethodCard';
import {
  MODULAR_DEVICE_FIELDS,
  PaymentLinkStatusType,
  QrPaymentStatusType,
} from 'apps/pos/src/app/types/DeviceSelection';
import { ModularOnboardingOption } from 'apps/pos/src/app/types/modular';

export interface DevicePaymentMethodsProps {
  title: string;
  onClickScanAndPay: () => void;
  onClickPaymentLink: () => void;
  paymentMethods: ModularOnboardingOption[];
  isUpdateModularLoading: boolean;
  paymentLinkStatus: PaymentLinkStatusType;
  qrPaymentStatus: QrPaymentStatusType;
  isPaymentLinkEnabled: boolean;
  isModuleLoading: {
    isPaymentLinkLoading: boolean;
    isQrCodeLoading: boolean;
  };
}

const getFilteredMethods = (
  isPaymentLinkEnabled: boolean,
  paymentMethods: ModularOnboardingOption[],
) => {
  if (isPaymentLinkEnabled) return paymentMethods;
  return paymentMethods.filter(
    (method) => method.value !== MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK,
  );
};

export const DevicePaymentMethods = ({
  title,
  onClickScanAndPay,
  onClickPaymentLink,
  paymentMethods,
  isUpdateModularLoading,
  paymentLinkStatus,
  qrPaymentStatus,
  isPaymentLinkEnabled,
  isModuleLoading,
}: DevicePaymentMethodsProps): JSX.Element => {
  const filteredPaymentMethods = getFilteredMethods(isPaymentLinkEnabled, paymentMethods);
  return (
    <Box>
      <Heading marginBottom="spacing.7" size="large">
        {title}
      </Heading>
      <StyledCardContainer>
        {filteredPaymentMethods.map((method, index) => (
          <PaymentMethodCard
            key={method.value}
            isDisabled={
              method.value === 'qr_code'
                ? paymentLinkStatus === 'paid'
                : qrPaymentStatus === 'success'
            }
            onClick={
              method.value === MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK
                ? onClickPaymentLink
                : onClickScanAndPay
            }
            title={method.label}
            value={method.value}
            description={method.helpText ?? ''}
            paymentMethodIcon={
              method.value === MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK ? LinkIcon : QRCodeIcon
            }
            hasBottomBorder={
              filteredPaymentMethods.length > 1 && index !== filteredPaymentMethods.length - 1
            }
            isUpdateModularLoading={
              isUpdateModularLoading &&
              (method.value === MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK
                ? isModuleLoading.isPaymentLinkLoading
                : isModuleLoading.isQrCodeLoading)
            }
            paymentStatus={
              method.value === MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE
                ? qrPaymentStatus
                : paymentLinkStatus
            }
          />
        ))}
      </StyledCardContainer>
    </Box>
  );
};

export default DevicePaymentMethods;
