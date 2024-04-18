import React from 'react';
import {
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  TextInput,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheetFooter,
  PhoneCallIcon,
} from '@razorpay/blade/components';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';

import PaymentLinkModal from './paymentLinkModal';
import { sendPaymentLinkRequest } from './queries';
import { PaymentLinkModalType, SendPaymentLinkReqData, SendPaymentLinkType } from './type';
import { convertToMinorUnit } from '@razorpay/i18nify-js/currency';

const SendPaymentLinkModal = ({
  orderAmount,
  mobileNumber: defaultMobileNumber,
  showPaymentLinkModal,
  setShowPaymentLinkModal,
  paymentLinkData,
}: SendPaymentLinkType): JSX.Element => {
  const [mobileNumber, setMobileNumber] = React.useState(defaultMobileNumber);
  const [additionalNotes, setNotes] = React.useState<undefined | string>('');
  const [isPaymentLinkLoading, setPaymentLinkLoading] = React.useState(false);
  const [isPaymentLinkResponseVisible, setPaymentLinkResponseVisible] = React.useState(false);
  const [paymentLinkModalType, setPaymentLinkModalType] = React.useState(PaymentLinkModalType.NONE);
  const [paymentLinkId, setPaymentLinkId] = React.useState('');
  const isMobile = isMobileDevice();

  const addMobileNumber = (e) => {
    setMobileNumber(e.value);
  };

  const sendPaymentLink = async () => {
    const formatedOrderAmount = convertToMinorUnit(parseFloat(orderAmount || '0'), {
      currency: 'INR',
    });

    analyticsTrack({
      objectName: 'Payment Link',
      actionName: 'clicked',
      screen: 'Assisted Financing',
      properties: {
        customer_mobile_number: mobileNumber,
        order_amount: formatedOrderAmount,
        method: paymentLinkData?.method,
        provider: paymentLinkData?.provider,
        type: paymentLinkData?.type,
        source: 'sales_assisted',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    setPaymentLinkLoading(true);

    let reqData: SendPaymentLinkReqData = {
      currency: 'INR',
      amount: formatedOrderAmount,
      customer: {
        contact: mobileNumber,
      },
      notify: {
        sms: true,
      },
    };

    if (additionalNotes) {
      reqData = {
        ...reqData,
        notes: {
          notes: additionalNotes,
        },
      };
    }

    try {
      const sendPaymentLinkResponse = await sendPaymentLinkRequest(reqData, paymentLinkData);

      analyticsTrack({
        objectName: 'Payment Link',
        actionName: 'response',
        screen: 'Assisted Financing',
        properties: {
          customer_mobile_number: mobileNumber,
          order_amount: formatedOrderAmount,
          method: paymentLinkData?.method,
          provider: paymentLinkData?.provider,
          type: paymentLinkData?.type,
          success: true,
          failed: false,
          failure_reason: 'NA',
          source: 'sales_assisted',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      analyticsTrack({
        objectName: 'Payment Link',
        actionName: 'sent to user',
        screen: 'Assisted Financing',
        properties: {
          payment_Link_Id: sendPaymentLinkResponse.data.id,
          source: 'sales_assisted',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      setPaymentLinkModalType(PaymentLinkModalType.SUCCESS);
      setPaymentLinkId(sendPaymentLinkResponse.data.id);
    } catch (error: any) {
      setPaymentLinkModalType(PaymentLinkModalType.FAILURE);
      analyticsTrack({
        objectName: 'Payment Link',
        actionName: 'response',
        screen: 'Assisted Financing',
        properties: {
          customer_mobile_number: mobileNumber,
          order_amount: formatedOrderAmount,
          method: paymentLinkData?.method,
          provider: paymentLinkData?.provider,
          type: paymentLinkData?.type,
          success: false,
          failed: true,
          failure_reason: error?.[0]?.description || error?.errors?.[0],
          failure_code: error?.[0]?.code,
          source: 'sales_assisted',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    } finally {
      setPaymentLinkLoading(false);
      setShowPaymentLinkModal(false);
      setPaymentLinkResponseVisible(true);
    }
  };

  const modalContent = () => {
    return (
      <>
        <TextInput
          value={orderAmount}
          label="Order Amount"
          type="number"
          placeholder="0"
          isDisabled={true}
          prefix="₹"
        />
        <TextInput
          value={mobileNumber}
          onChange={addMobileNumber}
          label="Mobile Number"
          placeholder="9999999999"
          type="number"
          marginTop="spacing.6"
          prefix="+ 91 - "
          icon={PhoneCallIcon}
        />
        <TextInput
          value={additionalNotes}
          onChange={(e) => setNotes(e.value)}
          necessityIndicator="optional"
          type="text"
          label="Notes"
          placeholder="Add notes like product details"
          marginTop="spacing.6"
        />
      </>
    );
  };

  if (isPaymentLinkResponseVisible) {
    return (
      <PaymentLinkModal
        isVisible={isPaymentLinkResponseVisible}
        setIsVisible={setPaymentLinkResponseVisible}
        modalType={paymentLinkModalType}
        paymentLinkId={paymentLinkId}
      />
    );
  }

  if (isMobile) {
    return (
      <BottomSheet
        isOpen={showPaymentLinkModal}
        onDismiss={() => {
          setShowPaymentLinkModal(false);
        }}
        snapPoints={[0.5, 0.7, 0.85]}
      >
        <BottomSheetHeader title="Send Payment Link" />
        <BottomSheetBody>{modalContent()}</BottomSheetBody>
        <BottomSheetFooter>
          <Button
            onClick={sendPaymentLink}
            size="medium"
            type="button"
            variant="primary"
            isFullWidth={true}
            isLoading={isPaymentLinkLoading}
            isDisabled={!mobileNumber || !orderAmount}
            testID="send-payment-link-button-mobile"
          >
            Send Payment Link
          </Button>
        </BottomSheetFooter>
      </BottomSheet>
    );
  } else {
    return (
      <Modal
        isOpen={showPaymentLinkModal}
        onDismiss={() => setShowPaymentLinkModal(false)}
        size="small"
      >
        <ModalHeader title="Send Payment Link" />
        <ModalBody>{modalContent()}</ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
            <Button variant="secondary">Cancel</Button>
            <Button
              onClick={sendPaymentLink}
              isLoading={isPaymentLinkLoading}
              isDisabled={!mobileNumber || !orderAmount}
              testID="send-payment-link-button"
            >
              Send Payment Link
            </Button>
          </Box>
        </ModalFooter>
      </Modal>
    );
  }
};

export default SendPaymentLinkModal;
