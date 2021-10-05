import React, { ReactNode } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import NeedsClarification from './icons/NC.svg';
import UnderReview from './icons/UnderReview.svg';
import PaymentEnable from './icons/PaymentActivated.svg';
import MerchantBlocked from './icons/MerchantBlocked.svg';
import PaymentLimitRemoved from './icons/LimitRemoved.svg';
import FillKyc from './icons/FillKyc.svg';
import PaymentPaused from './icons/PaymentPaused.svg';
import * as Message from './Constant';

export type ModalTypeT =
  | 'dedupe'
  | 'poi_initiated'
  | 'payment_enable'
  | 'payment_disable'
  | 'under_review'
  | 'tnc'
  | 'settelment_onhold'
  | 'needs_clarification'
  | 'rejected'
  | '';

const InlineText = styled(View)`
  color: ${({ color }) => color};
  margin-top: 20px;
`;

export const getModalContent = (
  modalType: ModalTypeT,
  closeModal: () => void,
  history: any,
  activationData: any,
  dedupeStatus: string | undefined,
) => {
  /* eslint-disable react-hooks/rules-of-hooks */
  const { user, experiments } = useApp();
  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;

  let title = '';
  let description: string | ReactNode = '';
  let button: ReactNode = <div />;
  let image: ReactNode = <div />;
  let additionalDesc: ReactNode = <span />;
  const statusLog = activationData?.activationStatusChangeLogs || [];
  const isPartialMatch = dedupeStatus === 'partial_match';
  const isPaymentLimitRemoved =
    !statusLog.includes('needs_clarification') && !!activationData?.activated;

  const openCustomerSupport = () => {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'contact support',
      screen: 'home page',
      eventAction: 'initiated',
      user,
    });
    window.rzpTicketSystem?.openModal('#ticket');
    closeModal();
  };

  const sendFormSegment = () => {
    analyticsTrack({
      objectName: 'L2 Start',
      actionName: 'form fill',
      screen: 'home page',
      eventAction: 'initiated',
      properties: {
        milestone: 'L2 Start',
      },
      activationType: 'kyc',
      user,
    });
  };

  switch (modalType) {
    case 'dedupe':
      title = isInstantActivationEnabled ? Message.DEDUPE.title : Message.DEDUPE.old_title;
      image = <img src={MerchantBlocked} />;
      description = !isInstantActivationEnabled
        ? Message.DEDUPE.old_description
        : Message.DEDUPE.description;
      additionalDesc =
        activationData?.activation_form_milestone === 'L2' ? (
          <InlineText color="#162f568a">{Message.DEDUPE.L2_description}</InlineText>
        ) : (
          <span />
        );
      button = (
        <Button onClick={openCustomerSupport} block>
          {Message.DEDUPE.buttonText}
        </Button>
      );
      return { title, description, image, button, additionalDesc };

    case 'poi_initiated':
      title = Message.POI_INITIATED.title;
      image = <img src={UnderReview} />;
      description = Message.POI_INITIATED.description;
      button = (
        <Button onClick={() => (location.href = '/')} block>
          {Message.POI_INITIATED.buttonText}
        </Button>
      );
      return { title, description, image, button };

    case 'payment_enable':
      title = Message.PAYMENT_ENABLE.title;
      image = <img src={PaymentEnable} />;
      description = Message.PAYMENT_ENABLE.description;
      additionalDesc = (
        <InlineText color="inherit">{Message.PAYMENT_ENABLE.sub_description}</InlineText>
      );
      button = (
        <>
          <Button onClick={() => (location.href = '/')} block>
            {Message.PAYMENT_ENABLE.buttonText}
          </Button>
          <Space margin={[1.5, 0, 0]}>
            <Flex justifyContent="center">
              <View>
                <Button
                  variant="tertiary"
                  onClick={() => {
                    closeModal();
                    sendFormSegment();
                    location.href = '/app/onboarding/steps';
                  }}
                >
                  {Message.PAYMENT_ENABLE.secondryButtonText}
                </Button>
              </View>
            </Flex>
          </Space>
        </>
      );
      return { title, description, image, button, additionalDesc };

    case 'payment_disable':
      title = Message.PAYMENT_DISABLE.title;
      image = <img src={FillKyc} />;
      description = Message.PAYMENT_DISABLE.description;
      button = (
        <Button
          onClick={() => {
            closeModal();
            sendFormSegment();
            history.push('/onboarding/steps');
          }}
          block
        >
          {Message.PAYMENT_DISABLE.buttonText}
        </Button>
      );
      return { title, description, image, button };

    case 'under_review':
      title = Message.UNDER_REVIEW.title;
      image = (
        <img
          src={
            isPartialMatch
              ? PaymentPaused
              : isPaymentLimitRemoved
              ? PaymentLimitRemoved
              : UnderReview
          }
        />
      );
      description = Message.UNDER_REVIEW.description;
      if (isPartialMatch) {
        title = Message.UNDER_REVIEW.partial_match_title;
        description = Message.UNDER_REVIEW.partial_match_description;
      } else if (isPaymentLimitRemoved) {
        title = Message.UNDER_REVIEW.payment_enable_title;
        description = Message.UNDER_REVIEW.payment_enable_description;
      }
      button = (
        <Button onClick={() => (location.href = '/')} block>
          Back To Dashboard
        </Button>
      );
      return { title, description, image, button };

    case 'tnc':
      title =
        dedupeStatus === 'partial_match' ? Message.TNC.partial_match_title : Message.TNC.title;
      image = <img src={UnderReview} />;
      description =
        dedupeStatus === 'partial_match'
          ? Message.TNC.partial_match_description
          : !statusLog.includes('needs_clarification') && !!activationData?.activated
          ? Message.TNC.payment_enable_description
          : Message.TNC.description;

      button = (
        <Button
          onClick={() => {
            history.push('/tncform');
            analyticsTrack({
              objectName: 'Act',
              actionName: 'generate page now',
              screen: 'home page',
              properties: { clickSource: 'post KYC submit tnc popup' },
              eventAction: 'initiated',
              user,
            });
          }}
          block
        >
          {Message.TNC.buttonText}
        </Button>
      );
      return { title, description, image, button };

    case 'settelment_onhold':
      title = 'Settlements on hold';
      image = <img src={PaymentPaused} />;
      description = (
        <>
          Our compliance team and partner banks carry out routine audits of your KYC documents. We
          might temporarily pause your settlements during this time, but don't worry, just look for
          clarifications asked by our team on your registered email. <br /> <br /> Once we receive
          the clarifications, we will resume your settlements. Upon receiving your response, we will
          be able to process the application within 2 days and re enable settlements for you. Please
          note, you can still accept payments from your customers.
        </>
      );
      button = (
        <Button onClick={closeModal} block>
          Okay, Got It
        </Button>
      );
      return { title, description, image, button };

    case 'needs_clarification':
      title = Message.NC.title;
      image = <img src={NeedsClarification} />;
      description =
        activationData?.merchant?.hold_funds && statusLog.includes('activated_mcc_pending')
          ? Message.NC.description.onhold_nc
          : !activationData?.merchant?.hold_funds && statusLog.includes('activated_mcc_pending')
          ? Message.NC.description.mcc_pending_nc
          : Message.NC.description.normal_nc;
      button = (
        <>
          <Button
            onClick={() => {
              closeModal();
              history.push('/activation');
            }}
            icon="link"
            iconAlign="right"
            block
          >
            {Message.NC.buttonText}
          </Button>
          {statusLog.includes('activated_mcc_pending') && (
            <Flex justifyContent="center">
              <Space margin={[1.5, 0, 0]}>
                <View>
                  <Button
                    variant="tertiary"
                    children={Message.NC.secondryButtonText}
                    onClick={closeModal}
                  />
                </View>
              </Space>
            </Flex>
          )}
        </>
      );
      return { title, description, image, button };

    case 'rejected':
      title = Message.REJECTED.title;
      image = <img src={MerchantBlocked} />;
      description = Message.REJECTED.description;
      button = (
        <Button onClick={openCustomerSupport} block>
          {Message.REJECTED.buttonText}
        </Button>
      );
      return { title, description, image, button };

    default:
      return { title, description, image, button, additionalDesc };
  }
};
