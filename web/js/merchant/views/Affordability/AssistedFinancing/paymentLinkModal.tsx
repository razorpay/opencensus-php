import React from 'react';
import {
  Button,
  Modal,
  ModalBody,
  Box,
  Text,
  Heading,
  CheckCircleIcon,
  AlertTriangleIcon,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
} from '@razorpay/blade/components';

import { isMobileDevice } from 'merchant/components/Home/data';

import { PaymentLinkModalContentContainer } from './styled';
import { PaymentLinkModalType } from './type';

const Content = ({ isSuccess }: { isSuccess: boolean }) => {
  return (
    <PaymentLinkModalContentContainer isSuccess={isSuccess}>
      {isSuccess ? (
        <CheckCircleIcon size="large" color="feedback.icon.positive.intense" />
      ) : (
        <AlertTriangleIcon size="large" color="feedback.icon.negative.intense" />
      )}
      <Heading marginTop="spacing.3" marginBottom="spacing.3">
        {isSuccess ? 'Successfully sent' : 'Something went wrong'}
      </Heading>
      <Box width="80%">
        <Text size="small" textAlign="center">
          {isSuccess
            ? 'You have successfully sent the payment link to the customer mobile number'
            : "We're experiencing technical difficulties while creating your payment link.Our team is on it! Please try again in a few minutes."}
        </Text>
      </Box>
    </PaymentLinkModalContentContainer>
  );
};

const ButtonComponent = ({
  isMobile,
  onPress,
  isSuccess,
}: {
  isMobile: boolean;
  onPress: () => void;
  isSuccess: boolean;
}) => {
  return (
    <Button isFullWidth={isMobile} onClick={onPress}>
      {isSuccess ? 'Done' : 'Retry Later'}
    </Button>
  );
};

const PaymentLinkModal = ({
  isVisible,
  setIsVisible,
  modalType,
  paymentLinkId,
}: {
  isVisible: boolean;
  setIsVisible: (value: boolean) => void;
  modalType: PaymentLinkModalType;
  paymentLinkId: string;
}): JSX.Element => {
  const isSuccess = modalType === PaymentLinkModalType.SUCCESS;
  const isMobile = isMobileDevice();

  const onButtonPress = () => {
    setIsVisible(false);
    if (isSuccess) {
      window.location.href = `/app/paymentlinks/${paymentLinkId}`;
    }
  };

  if (isMobile) {
    return (
      <BottomSheet isOpen={isVisible} onDismiss={() => setIsVisible(false)}>
        <BottomSheetBody>
          <Content isSuccess={isSuccess} />
        </BottomSheetBody>
        <BottomSheetFooter>
          <ButtonComponent isMobile={isMobile} onPress={onButtonPress} isSuccess={isSuccess} />
        </BottomSheetFooter>
      </BottomSheet>
    );
  } else {
    return (
      <Modal isOpen={isVisible} onDismiss={() => setIsVisible(false)} size="small">
        <ModalBody>
          <Content isSuccess={isSuccess} />
          <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
            <ButtonComponent isMobile={isMobile} onPress={onButtonPress} isSuccess={isSuccess} />
          </Box>
        </ModalBody>
      </Modal>
    );
  }
};

export default PaymentLinkModal;
