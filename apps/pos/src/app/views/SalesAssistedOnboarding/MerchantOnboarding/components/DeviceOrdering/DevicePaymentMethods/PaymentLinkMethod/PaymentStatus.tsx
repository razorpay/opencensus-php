import React from 'react';
import {
  AlertCircleIcon,
  Amount,
  Box,
  Button,
  CheckIcon,
  CloseIcon,
  CopyIcon,
  IconComponent,
  SendIcon,
  StepGroup,
  StepItem,
  StepItemIcon,
  Text,
} from '@razorpay/blade/components';
import { formatUnixTimestamp } from 'apps/pos/src/app/utils/agreementSigning';
import { PaymentLinkStatusType } from 'apps/pos/src/app/types/DeviceSelection';
import { FeedbackColors } from 'apps/pos/src/app/types/AgreementSigning';

export interface PaymentStatusProps {
  onResendBtnClick: () => void;
  onCopyBtnClick: () => void;
  paymentLinkCreatedAt?: string;
  paymentLinkCompletedAt?: string;
  paymentLinkStatus: PaymentLinkStatusType;
  amount: number;
  isResendingLink: boolean;
}

interface PaymentStatusDetailsReturnType {
  title: string;
  description: string;
  icon: IconComponent;
  color: FeedbackColors;
}
const PaymentStatus = ({
  onResendBtnClick,
  onCopyBtnClick,
  paymentLinkCreatedAt,
  paymentLinkCompletedAt,
  paymentLinkStatus,
  amount,
  isResendingLink,
}: PaymentStatusProps): JSX.Element => {
  const getStatusDetails = (
    paymentLinkStatus: PaymentLinkStatusType,
  ): PaymentStatusDetailsReturnType => {
    const map: Record<Exclude<PaymentLinkStatusType, ''>, PaymentStatusDetailsReturnType> = {
      created: {
        title: 'Payment pending',
        description: "Payment hasn't been processed yet",
        icon: AlertCircleIcon,
        color: 'notice',
      },
      expired: {
        title: 'Payment link expired',
        description:
          'The payment link has exceeded its 30-day validity period. Please generate a new payment link.',
        icon: CloseIcon,
        color: 'negative',
      },
      paid: {
        title: 'Payment successful',
        description: 'Payment is successful',
        icon: CheckIcon,
        color: 'positive',
      },
      cancelled: {
        title: 'Payment link cancelled',
        description: 'Payment link is cancelled. Please generate a new payment link.',
        icon: CloseIcon,
        color: 'negative',
      },
    };
    return paymentLinkStatus
      ? map[paymentLinkStatus]
      : { title: '', description: '', icon: AlertCircleIcon, color: 'neutral' };
  };

  return (
    <StepGroup orientation="vertical" size="medium">
      <StepItem
        title="Payment link created & shared"
        description="Link has been sent to the merchant via SMS"
        timestamp={formatUnixTimestamp(paymentLinkCreatedAt ?? '')}
        marker={<StepItemIcon icon={CheckIcon} color="positive" />}
        stepProgress="full"
      >
        {paymentLinkStatus === 'created' && (
          <Box>
            <Text marginBottom="spacing.3" color="surface.text.gray.muted" size="small">
              Amount to be paid:{' '}
              <Amount
                weight="semibold"
                size="small"
                color="surface.text.gray.muted"
                currency="INR"
                value={Number(amount) || 0}
              />
            </Text>
            <Box display="flex" alignItems="center" gap="spacing.3" flexWrap="wrap">
              <Button
                onClick={onResendBtnClick}
                icon={SendIcon}
                iconPosition="left"
                size="medium"
                variant="tertiary"
                isLoading={isResendingLink}
              >
                Re-send link
              </Button>
              <Button
                onClick={onCopyBtnClick}
                icon={CopyIcon}
                iconPosition="left"
                size="medium"
                variant="tertiary"
              >
                Copy link
              </Button>
            </Box>
          </Box>
        )}
      </StepItem>
      <StepItem
        title={getStatusDetails(paymentLinkStatus)?.title}
        description={getStatusDetails(paymentLinkStatus)?.description}
        timestamp={
          paymentLinkStatus === 'paid' ? formatUnixTimestamp(paymentLinkCompletedAt ?? '') : ''
        }
        marker={
          <StepItemIcon
            icon={getStatusDetails(paymentLinkStatus).icon}
            color={getStatusDetails(paymentLinkStatus).color}
          />
        }
      />
    </StepGroup>
  );
};

export default PaymentStatus;
