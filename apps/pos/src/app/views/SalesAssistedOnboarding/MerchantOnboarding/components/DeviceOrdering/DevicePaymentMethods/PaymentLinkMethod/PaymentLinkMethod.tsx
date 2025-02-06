import React, { useEffect } from 'react';
import { ArrowRightIcon, Box, Button, Heading } from '@razorpay/blade/components';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import PaymentStatus from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/PaymentLinkMethod/PaymentStatus';
import { PaymentLinkStatusType } from 'apps/pos/src/app/types/DeviceSelection';
import { analyticsTypes, trackEvent } from 'apps/pos/src/services/analytics';

interface ContinueBtnProps {
  isMobile: boolean;
  onClick: (payload: any) => void;
  paymentLinkStatus: PaymentLinkStatusType;
  isLoading?: boolean;
}
const ContinueBtn = ({ isMobile, onClick, paymentLinkStatus, isLoading }: ContinueBtnProps) => {
  const getBtnText = () => {
    if (!paymentLinkStatus) return 'Generate link';
    if (paymentLinkStatus === 'expired' || paymentLinkStatus === 'cancelled')
      return 'Generate new link';
    if (paymentLinkStatus === 'created') return 'Check payment status';
    if (paymentLinkStatus === 'paid') return 'Continue to next step';
    return 'Continue';
  };
  return (
    <Button
      isDisabled={false}
      type="submit"
      isFullWidth={isMobile}
      iconPosition="right"
      isLoading={isLoading}
      onClick={onClick}
      {...(!paymentLinkStatus && { icon: ArrowRightIcon })}
    >
      {getBtnText()}
    </Button>
  );
};

export interface PaymentLinkDetails {
  paymentLinkStatus: PaymentLinkStatusType;
  paymentLinkCreatedAt?: string;
  paymentLinkCompletedAt?: string;
}
export interface PaymentLinkMethodProps {
  handleModularUpdate: (payload: any) => void;
  paymentLinkDetails: PaymentLinkDetails;
  onResendBtnClick: () => void;
  onCopyBtnClick: () => void;
  amount: number;
  isUpdateModularLoading: boolean;
  isResendingLink: boolean;
}
const PaymentLinkMethod = ({
  handleModularUpdate,
  paymentLinkDetails,
  onResendBtnClick,
  onCopyBtnClick,
  amount,
  isUpdateModularLoading,
  isResendingLink,
}: PaymentLinkMethodProps): JSX.Element => {
  const { isMobile } = useScreen();

  useEffect(() => {
    if (isUpdateModularLoading) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.IMAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          section: 'Checkout Status',
          subSection: 'Payment confirmation loading',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAYMENT_CONFIRMATION_LOADING,
        },
      });
    }
  }, [isUpdateModularLoading]);
  return (
    <Box padding={['spacing.7', 'spacing.5', 'spacing.7', 'spacing.5']}>
      <Heading marginBottom="spacing.6" size="large">
        Payment Link
      </Heading>
      <PaymentStatus
        paymentLinkStatus={paymentLinkDetails.paymentLinkStatus}
        paymentLinkCreatedAt={paymentLinkDetails.paymentLinkCreatedAt}
        paymentLinkCompletedAt={paymentLinkDetails.paymentLinkCompletedAt}
        onCopyBtnClick={onCopyBtnClick}
        onResendBtnClick={onResendBtnClick}
        amount={amount}
        isResendingLink={isResendingLink}
      />
      {isMobile ? (
        <Box
          display="flex"
          justifyContent="center"
          position="fixed"
          bottom="0px"
          padding="spacing.4"
          backgroundColor="surface.background.gray.intense"
          left="0px"
          right="0px"
          zIndex="1"
        >
          <ContinueBtn
            paymentLinkStatus={paymentLinkDetails.paymentLinkStatus}
            isMobile={isMobile}
            onClick={handleModularUpdate}
            isLoading={isUpdateModularLoading}
          />
        </Box>
      ) : (
        <Box marginTop="spacing.9">
          <ContinueBtn
            paymentLinkStatus={paymentLinkDetails.paymentLinkStatus}
            isMobile={isMobile}
            onClick={handleModularUpdate}
            isLoading={isUpdateModularLoading}
          />
        </Box>
      )}
    </Box>
  );
};

export default PaymentLinkMethod;
