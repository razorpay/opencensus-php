import React, { useEffect } from 'react';
import {
  Button,
  Title,
  Text,
  Modal,
  ModalBody,
  ModalHeader,
  Box,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { compose, bindActionCreators } from 'redux';

import CongratulationsDesktop from 'assets/pricing-bundle/congratulations-desktop.png';
import Image from 'common/ui/Image';
import { PAYMENT_TYPE } from 'common/ui/PricingSubscription/constants';
import {
  MODAL_TYPE,
  MODAL_CONTENT,
} from 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions/constant';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';

import { StyleModalParent, StyleImageBox } from './MultiPaymentOptionsStyled';

import type {
  TrackingObjectType,
  TogglePlan,
  PlansType,
  PaymentType,
} from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

interface CongratulatoryModalContentType {
  closeModal: () => void;
  user: {
    isAllowedMultiple: (value: string) => boolean;
    isAccountAndSettingsRevampEnabled: boolean;
  };
  setCongratulatoryModal: (value: boolean) => void;
  type: string;
  togglePlan: TogglePlan;
  trackInstrumentation: (type: string, trackingObject: TrackingObjectType) => void;
  selectedPaymentMode: PaymentType;
  selectedPlan: PlansType;
}
interface CongratulatoryModalType extends CongratulatoryModalContentType {
  isCongModalOpen: boolean;
  zIndex?: number;
}
export const getBalanceMode = (balanceType: string): string => {
  switch (balanceType) {
    case MODAL_TYPE.CHECKOUT_PAYMENT:
      return 'Checkout payment';
    case MODAL_TYPE.INSUFFICIENT_BALANCE:
      return 'Insufficient';
    case MODAL_TYPE.SUFFICIENT_BALANCE:
      return 'Sufficient';
    default:
      return '';
  }
};
export const CongratulatoryModalContent = ({
  closeModal,
  setCongratulatoryModal,
  user,
  type,
  togglePlan,
  selectedPaymentMode,
  selectedPlan,
  trackInstrumentation,
}: CongratulatoryModalContentType): JSX.Element => {
  const navigate = useNavigate();

  const handleToastLink = () => {
    trackInstrumentation('', {
      event_method: 'initiated',
      cta_value: 'Check Pricing Plan Status',
      toggle_switch: togglePlan,
      balance: getBalanceMode(type),
      payment_method:
        selectedPaymentMode === PAYMENT_TYPE.INTERNAL ? 'Settlement balance' : 'Normal Checkout',
      plan_id: selectedPlan.id,
      plan_name: selectedPlan.title,
      event_name: 'merchant_dashboard.click_cta',
    });
    setCongratulatoryModal(false);
    if (
      user.isAllowedMultiple(
        'webhooks applications configuration api_keys profile credits add_funds team referrals',
      ) &&
      user.isAccountAndSettingsRevampEnabled
    )
      navigate(ROUTES_INFO.PRICING_PLANS);
    else navigate(ROUTES_INFO.PRICING_PLANS_RELATIVE);
    closeModal();
  };
  return (
    <Box display="flex" justifyContent="flex-start" testID="modalContentContainer">
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="space-between"
        alignItems="flex-start"
        marginBottom="spacing.2"
      >
        <Box>
          <StyleModalParent>
            <Title size="large">Congratulations!</Title>
          </StyleModalParent>
          <Text marginTop="spacing.6" weight="bold" testID="modalMainHeader">
            {MODAL_CONTENT[type].header}
          </Text>
          <Text marginTop="spacing.4" type="muted" testID="modalSubHeader">
            {MODAL_CONTENT[type].subHeader}
          </Text>
        </Box>
        <Button
          marginTop="spacing.9"
          variant="primary"
          onClick={handleToastLink}
          testID="modalButton"
        >
          Check Pricing Plan Status
        </Button>
      </Box>
      <StyleImageBox>
        <Image src={CongratulationsDesktop} alt="Congratulatory modal" />
      </StyleImageBox>
    </Box>
  );
};

const CongratulatoryModal = ({
  closeModal,
  user,
  isCongModalOpen,
  setCongratulatoryModal,
  type,
  zIndex = 9999,
  trackInstrumentation,
  selectedPlan,
  selectedPaymentMode,
  togglePlan,
}: CongratulatoryModalType): JSX.Element => {
  const handleClose = (): void => {
    setCongratulatoryModal(false);
    trackInstrumentation('', {
      event_method: 'initiated',
      cta_value: 'Close',
      toggle_switch: togglePlan,
      modal: 'Payment Success modal',
      plan_id: selectedPlan.id,
      plan_name: selectedPlan.title,
      event_name: 'merchant_dashboard.click_close',
    });
    closeModal();
  };

  useEffect(() => {
    trackInstrumentation('', {
      event_method: 'initiated',
      balance: getBalanceMode(type),
      payment_method:
        selectedPaymentMode === PAYMENT_TYPE.INTERNAL ? 'Settlement balance' : 'Normal Checkout',
      plan_id: selectedPlan.id,
      plan_name: selectedPlan.title,
      event_name: 'merchant_dashboard.payment_success_screen',
    });
  }, []);

  return (
    <Modal zIndex={zIndex} isOpen={isCongModalOpen} onDismiss={handleClose} size="medium">
      <ModalHeader title="" />
      <ModalBody>
        <CongratulatoryModalContent
          closeModal={closeModal}
          setCongratulatoryModal={setCongratulatoryModal}
          user={user}
          type={type}
          togglePlan={togglePlan}
          selectedPlan={selectedPlan}
          selectedPaymentMode={selectedPaymentMode}
          trackInstrumentation={trackInstrumentation}
        />
      </ModalBody>
    </Modal>
  );
};
export default compose<CongratulatoryModalContentType | any>(
  connect(
    (state) => ({
      user: state.session.user,
    }),
    (dispatch) => {
      return bindActionCreators(
        {
          closeModal: fnCloseModal,
        },
        dispatch,
      );
    },
  ),
)(CongratulatoryModal);
