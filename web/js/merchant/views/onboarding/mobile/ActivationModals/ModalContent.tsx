import React, { ReactNode, useState } from 'react';
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
import { SAMPLE_TICKET } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import useTrackEvents from 'merchant/hooks/useTrackEvents';
import VideoModal from 'merchant/components/VideoModal';
import { getNcExpiryDate } from 'merchant/views/onboarding/mobile/services/utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import ImgNcKyc from 'assets/onboarding/ncKyc.svg';

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
  | 'needs_clarification_payments_settlement_enabled'
  | 'needs_clarification_with_payments_enabled'
  | 'needs_clarification_with_payment_disabled'
  | '';

const InlineText = styled(View)`
  color: ${({ color }) => color};
  margin-top: 20px;
`;

const NCImg = styled.img`
  width: 100%;
`;

export const getModalContent = (
  modalType: ModalTypeT,
  closeModal: () => void,
  history: any,
  activationData: any,
  dedupeStatus: string | undefined,
) => {
  /* eslint-disable react-hooks/rules-of-hooks */
  const { user, experiments, submerchantId } = useApp();
  const trackEvents = useTrackEvents();
  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const activationFormUrl = experiments.isActivationFormFullView ? '/kyc' : '/activation';
  const isInstantActivationVideoEnabled = user.isInstantActivationVideoEnabled;

  let title = '';
  let description: string | ReactNode = '';
  let button: ReactNode = <div />;
  let image: ReactNode = <div />;
  let additionalDesc: ReactNode = <span />;
  let pill: ReactNode = <span />;
  const statusLog = activationData?.activationStatusChangeLogs || [];
  const isPartialMatch = dedupeStatus === 'partial_match';
  const isPaymentLimitRemoved =
    !statusLog.includes('needs_clarification') && !!activationData?.activated;

  const expiryDate = getNcExpiryDate(activationData?.kyc_clarification_reasons);

  const [shouldShowVideoModal, setShouldShowVideoModal] = useState(false);
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

  const sendSegmentEventFromButton = (ctaText) => {
    trackEvents({
      objectName: 'Modal CTA',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        'CTA Label': ctaText,
      },
    });
  };

  const handleVideoClick = () => {
    trackEvents({
      objectName: 'Instant Activation',
      actionName: 'Video CTA clicked',
      screen: 'home page',
    });
  };

  const InstantActivationVideoLink = () => {
    if (isInstantActivationVideoEnabled) {
      trackEvents({
        objectName: 'Instant Activation',
        actionName: 'Video enabled',
        screen: 'home page',
      });

      return (
        <div>
          {' '}
          <a
            rel="noreferrer noopener"
            onClick={(): void => {
              setShouldShowVideoModal(true);
              handleVideoClick();
            }}
          >
            Click here
          </a>{' '}
          to watch a simple video on how to start accepting payments.
          <VideoModal
            visible={shouldShowVideoModal}
            maskClosable={true}
            onClose={() => setShouldShowVideoModal(false)}
            src="https://www.youtube-nocookie.com/embed/FM2P1D-yjOU?rel=0"
          />
        </div>
      );
    } else return null;
  };

  const trackNCEasyRedirect = (trackProps = {}) => {
    trackEvents({
      objectName: 'NC Resolve Now',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        funnelStage: 'NC',
        ctaClicked: 'Resolve Now',
        clickSource: 'Modal',
        ncCount: `${activationData?.kyc_clarification_reasons?.nc_count}`,
        deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
        ...trackProps,
      },
    });
  };

  const handleNcButton = () => {
    closeModal();
    sendSegmentEventFromButton(Message.NC.buttonText);
    const formUrl = submerchantId
      ? `partners/submerchants/${submerchantId}/activation`
      : activationFormUrl;
    if (submerchantId) {
      history.push(formUrl);
    } else {
      trackNCEasyRedirect({
        activationState: modalType,
        formName: title,
      });
      window.open(
        `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`,
        '_self',
        'noopener',
      );
    }
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
        <Button
          onClick={() => {
            sendSegmentEventFromButton(Message.DEDUPE.buttonText);
            openCustomerSupport();
          }}
          block
        >
          {Message.DEDUPE.buttonText}
        </Button>
      );
      return { title, description, image, button, additionalDesc };

    case 'poi_initiated':
      title = Message.POI_INITIATED.title;
      image = <img src={UnderReview} />;
      description = Message.POI_INITIATED.description;
      button = (
        <Button
          onClick={() => {
            sendSegmentEventFromButton(Message.POI_INITIATED.buttonText);
            location.href = submerchantId ? '/app/partners/submerchants' : '/';
          }}
          block
        >
          {Message.POI_INITIATED.buttonText}
        </Button>
      );
      return { title, description, image, button };

    case 'payment_enable':
      title = Message.PAYMENT_ENABLE.title;
      image = <img src={PaymentEnable} />;
      description = Message.PAYMENT_ENABLE.description;
      additionalDesc = (
        <InlineText color="inherit">
          {Message.PAYMENT_ENABLE.sub_description}
          <InstantActivationVideoLink />
        </InlineText>
      );
      button = (
        <>
          <Button
            onClick={() => {
              sendSegmentEventFromButton(Message.PAYMENT_ENABLE.buttonText);
              location.href = submerchantId ? '/app/partners/submerchants' : '/';
            }}
            block
          >
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
                    sendSegmentEventFromButton(Message.PAYMENT_ENABLE.secondryButtonText);
                    location.href = submerchantId
                      ? `/app/partners/submerchants/onboarding/acc_${submerchantId}/steps`
                      : '/app/onboarding/steps';
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
            sendSegmentEventFromButton(Message.PAYMENT_DISABLE.buttonText);
            const url = submerchantId
              ? `/partners/submerchants/onboarding/acc_${submerchantId}/steps`
              : '/onboarding/steps';
            history.push(url);
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
        <Button
          onClick={() => {
            sendSegmentEventFromButton('Back To Dashboard');
            location.href = submerchantId ? '/app/partners/submerchants' : '/';
          }}
          block
        >
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
            sendSegmentEventFromButton(Message.TNC.buttonText);
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
          note, you can still accept payments from your customers. Meanwhile, you can request a call
          from our team.
        </>
      );
      button = (
        <Button
          onClick={() => {
            if (window.rzpTicketSystem) {
              window.rzpTicketSystem.openModal(`#schedule-call`, {
                ticket: SAMPLE_TICKET,
              });
            }
            sendSegmentEventFromButton('Request a call');
            closeModal();
          }}
          block
        >
          Request a call
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
              sendSegmentEventFromButton(Message.NC.buttonText);
              const formUrl = submerchantId
                ? `partners/submerchants/${submerchantId}/activation`
                : activationFormUrl;
              history.push(formUrl);
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
                    onClick={() => {
                      sendSegmentEventFromButton(Message.NC.secondryButtonText);
                      closeModal();
                    }}
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
        <Button
          onClick={() => {
            sendSegmentEventFromButton(Message.REJECTED.buttonText);
            openCustomerSupport();
          }}
          block
        >
          {Message.REJECTED.buttonText}
        </Button>
      );
      return { title, description, image, button };

    case 'needs_clarification_payments_settlement_enabled':
      title = Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title;
      image = <NCImg src={ImgNcKyc} />;
      description = `You will not be able to receive payments in your bank account if the required details are
          not updated before ${expiryDate} `;

      pill = Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.pill;
      button = (
        <Button onClick={handleNcButton} block>
          {Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText}
        </Button>
      );
      return { title, pill, description, image, button };

    case 'needs_clarification_with_payments_enabled':
      title = Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title;
      image = <NCImg src={ImgNcKyc} />;
      description = (
        <>
          You’ll be able to receive collected payments in your account only after the required
          details are updated
        </>
      );
      pill = Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.pill;
      button = (
        <Button onClick={handleNcButton} block>
          {Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText}
        </Button>
      );
      return { title, pill, description, image, button };

    case 'needs_clarification_with_payment_disabled':
      title = Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title;
      image = <NCImg src={ImgNcKyc} />;
      description = (
        <>
          You’ll be able to collect payments and receive them in your bank account only after the
          required details are updated
        </>
      );
      pill = Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.pill;
      button = (
        <Button onClick={handleNcButton} block>
          {Message.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText}
        </Button>
      );
      return { title, pill, description, image, button };
    default:
      return { title, description, image, button, additionalDesc };
  }
};
