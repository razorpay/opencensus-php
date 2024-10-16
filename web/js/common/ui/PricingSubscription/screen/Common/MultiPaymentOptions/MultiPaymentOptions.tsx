import React, { useState, memo, useEffect } from 'react';
import {
  Button,
  Text,
  Modal,
  ModalBody,
  ModalHeader,
  Box,
  ArrowRightIcon,
  Badge,
  Amount,
} from '@razorpay/blade/components';

import Checked from 'assets/pricing-bundle/Checked.jpg';
import Unchecked from 'assets/pricing-bundle/Unchecked.jpg';
import Image from 'common/ui/Image';
import { handleCheckoutPayment } from 'common/ui/PricingSubscription/PricingBundleCommon';
import { usePricingContext } from 'common/ui/PricingSubscription/PricingContext';
import { PAYMENT_TYPE, supportedPaymentMode } from 'common/ui/PricingSubscription/constants';

import { StyledRadioBox, StyledHover } from './MultiPaymentOptionsStyled';

import type { PaymentType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

interface PaymentOptionCardProps {
  handlePaymentMethod: (paymentType: PaymentType) => React.MouseEventHandler<HTMLDivElement>;
  paymentOption: {
    paymentType: PaymentType;
    title: string;
    shouldPreferred: boolean;
    subText: string;
  };
  isRadioClick: PaymentType;
}
interface MultiPaymentModalProp {
  isOpen: boolean;
  togglePaymentOptionModal: () => void;
  setSelectedPaymentMode: (paymentMethod: PaymentType) => void;
}
interface MultiPaymentOptionsPropsType {
  setSelectedPaymentMode: (paymentMethod: PaymentType) => void;
}

export const PaymentOptionCard = ({
  handlePaymentMethod,
  paymentOption,
  isRadioClick,
}: PaymentOptionCardProps): JSX.Element => {
  const { paymentType, title, shouldPreferred, subText } = paymentOption || {};
  return (
    <StyledRadioBox
      onClick={handlePaymentMethod(paymentType)}
      paymentType={paymentType}
      isRadioClick={isRadioClick}
      data-testid={paymentType}
    >
      <Box display="flex" marginBottom="spacing.2" alignItems="center">
        <StyledHover>
          <Image
            src={isRadioClick === paymentType ? Checked : Unchecked}
            alt={isRadioClick === paymentType ? 'Checked' : 'Unchecked'}
          />
          <Text weight={isRadioClick === paymentType ? 'semibold' : 'regular'}>{title}</Text>
        </StyledHover>
        {shouldPreferred ? (
          <Badge marginLeft="spacing.3" color="positive">
            PREFERRED{' '}
          </Badge>
        ) : null}
      </Box>
      <Text marginLeft="spacing.8" color="surface.text.gray.muted">
        {subText}
      </Text>
    </StyledRadioBox>
  );
};
export const MultiPaymentOptions = ({
  setSelectedPaymentMode,
}: MultiPaymentOptionsPropsType): JSX.Element => {
  const [isRadioClick, setIsRadioClick] = useState<PaymentType>(PAYMENT_TYPE.INTERNAL);
  const { checkoutPayment, currentBalance, plans, multiPaymentData } = usePricingContext();

  const { data: { balance = 0 } = {} } = currentBalance || {};
  const handlePaymentMethod =
    (paymentMethod): (() => void) =>
    (): void => {
      setIsRadioClick(paymentMethod);
      setSelectedPaymentMode(paymentMethod);
    };

  const renderPaymentOptionCard = (): JSX.Element[] => {
    return supportedPaymentMode(balance).map((paymentOption, index) => (
      <PaymentOptionCard
        isRadioClick={isRadioClick}
        paymentOption={paymentOption}
        key={`${paymentOption.paymentType}_${index}`}
        handlePaymentMethod={handlePaymentMethod}
      />
    ));
  };

  useEffect(() => {
    const { trackInstrumentation, togglePlan } = checkoutPayment;
    trackInstrumentation('', {
      event_method: 'initiated',
      toggle_switch: togglePlan,
      plan_id: plans.id,
      plan_name: plans.title,
      event_name: 'merchant_dashboard.settlement_balance_modal',
    });
  }, []);

  return (
    <Box testID="MultiPaymentOptionsContainer">
      <Box
        borderWidth="none"
        borderBottomWidth="thin"
        borderBottomColor="surface.border.gray.muted"
        display="flex"
        padding={['spacing.7', 'spacing.7', 'spacing.4', 'spacing.7']}
        marginBottom="spacing.4"
      >
        <Box width="24px" marginRight="spacing.3" testID="renderImage">
          <Image src={multiPaymentData.icon} alt={multiPaymentData.planName} />
        </Box>
        <Box display="flex" flexDirection="column">
          <Text size="large">{multiPaymentData.planName}</Text>
          <Text color="surface.text.gray.muted">Select how you want to pay :</Text>
        </Box>
      </Box>
      <Box marginTop="spacing.6" padding={['spacing.3', 'spacing.7', 'spacing.0', 'spacing.7']}>
        {renderPaymentOptionCard()}
      </Box>
      <Box
        borderWidth="none"
        borderTopWidth="thin"
        borderTopColor="surface.border.gray.muted"
        display="flex"
        justifyContent="space-between"
        padding="spacing.7"
      >
        <Box>
          <Box display="flex">
            <Amount value={parseFloat((multiPaymentData.amount / 100).toFixed(2))} />/
            <Text weight="semibold">
              {multiPaymentData.frequency?.charAt(0)?.toUpperCase() +
                multiPaymentData.frequency?.slice(1)}
            </Text>
          </Box>
          <Text color="surface.text.gray.muted">
            Including {multiPaymentData.taxPercentage}% GST
          </Text>
        </Box>
        <Button
          isLoading={checkoutPayment && checkoutPayment.isLoading}
          variant="primary"
          icon={ArrowRightIcon}
          iconPosition="right"
          onClick={handleCheckoutPayment({ ...checkoutPayment, plans, type: isRadioClick })}
        >
          Proceed
        </Button>
      </Box>
    </Box>
  );
};

const MultiPaymentModal = ({
  isOpen,
  togglePaymentOptionModal,
  setSelectedPaymentMode,
}: MultiPaymentModalProp): JSX.Element => {
  return (
    <Modal isOpen={isOpen} onDismiss={togglePaymentOptionModal} size="small">
      <ModalHeader title="" />
      <ModalBody padding="spacing.0">
        <MultiPaymentOptions setSelectedPaymentMode={setSelectedPaymentMode} />
      </ModalBody>
    </Modal>
  );
};
export default memo(MultiPaymentModal);
