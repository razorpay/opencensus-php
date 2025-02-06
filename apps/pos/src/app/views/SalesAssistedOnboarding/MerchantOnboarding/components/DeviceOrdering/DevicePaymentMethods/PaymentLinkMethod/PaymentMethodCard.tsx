import React, { useMemo } from 'react';
import {
  AlertCircleIcon,
  Badge,
  Box,
  CheckIcon,
  ChevronRightIcon,
  CloseIcon,
  IconComponent,
  Spinner,
  Text,
} from '@razorpay/blade/components';
import { StyledPaymentCard } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/styled';
import {
  MODULAR_DEVICE_FIELDS,
  PaymentLinkStatusType,
  QrPaymentStatusType,
} from 'apps/pos/src/app/types/DeviceSelection';
import { FeedbackColors } from 'apps/pos/src/app/types/AgreementSigning';

export interface PaymentMethodCardProps {
  paymentMethodIcon: IconComponent;
  title: string;
  value: string;
  description: string;
  hasBottomBorder?: boolean | null;
  onClick: () => void;
  isUpdateModularLoading: boolean | null;
  isDisabled?: boolean;
  paymentStatus: PaymentLinkStatusType | QrPaymentStatusType;
}

const getBadgeDetails = (
  status: PaymentLinkStatusType | QrPaymentStatusType,
  value: string,
): {
  label: string;
  color: FeedbackColors;
  icon?: IconComponent;
} => {
  switch (status) {
    case 'created':
    case 'pending':
      return {
        label: 'Payment Pending',
        color: 'notice',
        icon: AlertCircleIcon,
      };
    case 'expired':
      return {
        label: value === MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE ? 'QR Expired' : 'Link Expired',
        color: 'negative',
        icon: CloseIcon,
      };
    case 'cancelled':
      return {
        label: 'Link Cancelled',
        color: 'negative',
        icon: CloseIcon,
      };
    case 'paid':
    case 'success':
      return {
        label: 'Payment Successful',
        color: 'positive',
        icon: CheckIcon,
      };
    default:
      return {
        label: '',
        color: 'neutral',
        icon: undefined,
      };
  }
};

const PaymentMethodCard = ({
  paymentMethodIcon,
  title,
  description,
  hasBottomBorder = false,
  onClick,
  isUpdateModularLoading,
  isDisabled = false,
  paymentStatus,
  value,
}: PaymentMethodCardProps): JSX.Element => {
  const Icon = paymentMethodIcon;

  const badgeDeatils = useMemo(() => getBadgeDetails(paymentStatus, value), [paymentStatus, value]);
  return (
    <StyledPaymentCard
      isDisabled={isDisabled || isUpdateModularLoading}
      isLoading={isUpdateModularLoading}
      onClick={onClick}
      hasBottomBorder={hasBottomBorder}
    >
      <Box flex={1} display="flex" alignItems="center" gap="spacing.5">
        <Box
          display={'flex'}
          alignItems={'center'}
          justifyContent={'center'}
          padding={'spacing.3'}
          backgroundColor={'surface.background.gray.moderate'}
          borderRadius={'large'}
        >
          <Icon color="interactive.icon.primary.normal" />
        </Box>
        <Box>
          <Box marginBottom={'spacing.2'} display="flex" alignItems="center" gap={'spacing.5'}>
            <Text
              weight="medium"
              color={isDisabled ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
            >
              {title}
            </Text>
            {!!paymentStatus && (
              <Badge icon={badgeDeatils.icon} size="small" color={badgeDeatils.color}>
                {badgeDeatils.label}
              </Badge>
            )}
          </Box>
          <Text
            size="small"
            color={isDisabled ? 'surface.text.gray.muted' : 'surface.text.gray.subtle'}
          >
            {description}
          </Text>
        </Box>
      </Box>
      {isUpdateModularLoading ? (
        <Spinner accessibilityLabel="loading" />
      ) : (
        <ChevronRightIcon
          color={isDisabled ? 'interactive.icon.gray.disabled' : 'interactive.icon.gray.subtle'}
        />
      )}
    </StyledPaymentCard>
  );
};

export default PaymentMethodCard;
