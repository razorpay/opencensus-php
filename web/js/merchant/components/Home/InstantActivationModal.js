import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import confetti from 'canvas-confetti';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import Button from 'common/new-ui/Button';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';
import { setOnBoardingDataInLocalState } from 'merchant/components/OnBoarding';
import InstantActivation from 'merchant/helpers/lottieConfigs/InstantActivation.json';
import { ProgressBar } from 'common/ui/ProgressBar';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import * as EventActions from 'merchant/reducers/trackEvents';
import VideoModal from 'merchant/components/VideoModal';
import {
  RECOMMENDED_PRODUCT_MAP,
  getRecommendedProductDetails,
} from 'merchant/components/Activation/ActivationUtils';
const CustomLottie = lazy(() =>
  import(/* webpackChunkName: 'CustomLottie' */ 'common/new-ui/Lottie'),
);

const InstantActivationModal = ({
  history,
  openPaymentAcceptModal = () => {},
  onClose = () => {},
  handleProductQuickGuide: productQuickGuide,
  currentOnboarding,
  tracking,
  isActivationFormFullView = false,
  trackEvents,
  isInstantActivationVideoEnabled = false,
}) => {
  const { recommendedProduct, hasRecommendedProduct } = getRecommendedProductDetails();
  const isPaymentLinkRecommendedProduct = recommendedProduct === 'payment_link';
  const [counter, setCounter] = useState(5);
  const [isProgressStarted, setProgress] = useState(false);
  const [animationStart, setAnimationStart] = useState(false);
  const [showVideoModal, setShowVideoModal] = useState(false);
  const trackEvent = tracking.trackEvent;
  const commonProperty = { auto_pl_product: recommendedProduct };

  const currentButton = () => {
    const completKYCBtn = (
      <Button.Secondary
        onClick={() => {
          analyticsTrack({
            objectName: 'L2 Start',
            actionName: 'form fill initiated',
            screen: 'home page',
            properties: {
              clickSource: 'form submission popup',
              milestone: 'L2 Start',
              ...commonProperty,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          trackEvents({
            objectName: 'Modal CTA',
            actionName: 'Clicked',
            screen: 'home page',
            properties: {
              'CTA Label': 'Complete KYC',
              'Modal Label': 'KYC Form',
            },
          });
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.form_fill', {
              clickSource: 'instant activation modal',
            }),
          );
          onClose();
          if (isActivationFormFullView) {
            history.push('/kyc');
          } else {
            history.push('/activation');
          }
        }}
        children="Complete KYC"
      />
    );
    if (isPaymentLinkRecommendedProduct) {
      return (
        <Button.Primary
          onClick={() => {
            analyticsTrack({
              objectName: 'Create PL Button',
              actionName: 'clicked',
              screen: 'home page',
              properties: {
                ...commonProperty,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            trackEvents({
              objectName: 'Pop Up CTA',
              actionName: 'Clicked',
              screen: 'home page',
              properties: {
                'Pop-up Label': 'Congratulations! You are ready to accept payments now',
                'CTA Label': 'Create PL Button',
              },
            });
            analyticsTrack({
              objectName: 'Auto PL Pop Up Redirection',
              actionName: 'Initiated',
              screen: 'home page',
              properties: {
                ...commonProperty,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            trackEvent(window.rzpQ.onbr().clicked('create_pl_button'), { ...commonProperty });
            trackEvent(window.rzpQ.onbr().initiated('auto_pl_pop_up_redirection'), {
              ...commonProperty,
            });
            onClose();
            history.push('/paymentlinks?link_type=standard');
          }}
          children="Create Payment Link"
        />
      );
    } else if (hasRecommendedProduct) {
      const recommendProductName = RECOMMENDED_PRODUCT_MAP[recommendedProduct]?.name || '';
      return (
        <>
          {completKYCBtn}
          <Button.Primary
            onClick={() => {
              trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'Congratulations! You are ready to accept payments now',
                  'CTA Label': 'Complete KYC',
                },
              });
              analyticsTrack({
                objectName: `Create ${recommendProductName} Button`,
                actionName: 'clicked',
                screen: 'home page',
                properties: {
                  ...commonProperty,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              trackEvent(
                window.rzpQ
                  .onbr()
                  .clicked(
                    `create_${recommendProductName.split(' ').join('_').toLowerCase()}_button`,
                  ),
                { ...commonProperty },
              );
              onClose();
              history.push(RECOMMENDED_PRODUCT_MAP[recommendedProduct]?.link);
            }}
            children={`Create ${recommendProductName}`}
          />
        </>
      );
    }
    return (
      <>
        {completKYCBtn}
        <Button.Primary
          onClick={() => {
            trackEvents({
              objectName: 'Pop Up CTA',
              actionName: 'Clicked',
              screen: 'home page',
              properties: {
                'Pop-up Label': 'Congratulations! You are ready to accept payments now',
                'CTA Label': 'Accept Payments',
              },
            });
            openPaymentAcceptModal();
          }}
          children="Accept Payments"
        />
      </>
    );
  };

  // eventListeners for animation
  const eventListeners = [
    {
      eventName: 'complete',
      callback: () => {},
    },
    {
      eventName: 'DOMLoaded',
      callback: () => {
        setTimeout(() => {
          setAnimationStart(true);
        }, 2000);
      },
    },
  ];

  let counterInterval = null;
  const counterUpdate = () => {
    counterInterval = setInterval(() => {
      if (counter > 0) {
        clearInterval(counterInterval);
        setCounter(counter - 1);
      }
    }, 10);
  };

  useEffect(() => {
    // redirect to payment link page when counter equal to 0
    if (counter === 0) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PL, false);
      setOnBoardingDataInLocalState({
        feature: RZPFeatures.PL,
        data: {
          isEnabled: true,
          lastVisitedTime: Date.now(),
        },
      });
      productQuickGuide({
        ...currentOnboarding,
        showOnboarding: false,
      });
      setTimeout(() => {
        analyticsTrack({
          objectName: 'Auto PL Pop Up Redirection',
          actionName: 'Initiated',
          screen: 'home page',
          properties: {
            ...commonProperty,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        trackEvent(window.rzpQ.onbr().initiated('auto_pl_pop_up_redirection'), {
          ...commonProperty,
        });
        onClose();
        history.push('/paymentlinks?link_type=standard');
      }, 500);
    }
    return () => {
      clearInterval(counterInterval);
    };
  }, [counter]);

  useEffect(() => {
    triggerHotjarRecording('instant_activation_popup_shown', ['instant_activation_popup_shown']);
    const end = Date.now() + 1 * 1000;
    const colors = [
      '#bb0000',
      '#ffffff',
      '#27c24c',
      '#528ff0',
      '#FF7E8D',
      '#6BDEB2',
      '#02CFFE',
      '#F08A08',
    ];

    // render confetti animation on load of popup
    (function frame() {
      confetti({
        particleCount: 4,
        angle: 60,
        spread: 55,
        origin: { x: 0 },
        colors,
        zIndex: 99999,
      });
      confetti({
        particleCount: 4,
        angle: 120,
        spread: 55,
        origin: { x: 1 },
        colors,
        zIndex: 99999,
      });

      if (Date.now() < end) {
        requestAnimationFrame(frame);
      }
    })();
    analyticsTrack({
      objectName: 'Payments Animation',
      actionName: 'Shown',
      screen: 'home page',
      properties: {
        ...commonProperty,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    trackEvent(window.rzpQ.onbr().initiated('payment_animation_shown'), {
      ...commonProperty,
    });
  }, []);

  if (isPaymentLinkRecommendedProduct) {
    // start counter and progress after 2sec
    setTimeout(() => {
      setProgress(true);
      counterUpdate();
    }, 2000);
  }

  const handleVideoClick = () => {
    trackEvents({
      objectName: 'Instant Activation',
      actionName: 'Video CTA clicked',
      screen: 'home page',
    });
  };

  useEffect(() => {
    trackEvents({
      objectName: 'Pop Up',
      actionName: 'Viewed',
      screen: 'home page',
      properties: {
        'Pop-up Label': 'Congratulations! You are ready to accept payments now',
      },
    });
  }, []);

  useEffect(() => {
    if (isInstantActivationVideoEnabled) {
      trackEvents({
        objectName: 'Instant Activation',
        actionName: 'Video enabled',
        screen: 'home page',
      });
    }
  }, [isInstantActivationVideoEnabled]);

  return (
    <>
      <ModalMask>
        <Modal
          className="instant-activations-success"
          onClose={() => {
            trackEvents({
              objectName: 'Pop Up',
              actionName: 'Closed',
              screen: 'home page',
              properties: {
                'Pop-up Label': 'Congratulations! You are ready to accept payments now',
              },
            });
            onClose();
          }}
          showCloseBtn={!isPaymentLinkRecommendedProduct}
        >
          <div className="modal-header">
            <SuspenseWithLoader>
              <CustomLottie
                animationData={InstantActivation}
                autoplay={animationStart}
                loop={0}
                eventListeners={eventListeners}
                isStopped={false}
              />
            </SuspenseWithLoader>
          </div>
          <div className="modal-body">
            <div className="modal-description">
              <div className="title">
                Congratulations! <br /> You are now all set to start receiving payments up to INR
                15,000
              </div>

              {isInstantActivationVideoEnabled && (
                <p>
                  <a
                    rel="noreferrer noopener"
                    onClick={() => {
                      setShowVideoModal(true);
                      handleVideoClick();
                    }}
                    className="btn-link"
                  >
                    {' '}
                    <strong>Click here</strong>{' '}
                  </a>{' '}
                  to watch a short video that will take you through your next steps.
                </p>
              )}

              <p>
                Complete your KYC to get settlements to your bank account and receive more than INR
                15,000
              </p>
            </div>
            {currentButton()}
            {isPaymentLinkRecommendedProduct && (
              <p className="redirect-counter">Redirecting you to payment links in {counter}.</p>
            )}
          </div>
          {isPaymentLinkRecommendedProduct && (
            <ProgressBar
              className="redirect-progress"
              type={isProgressStarted ? 'animation' : ''}
              max={100}
              min={0}
              color="#2B83EA"
            />
          )}
        </Modal>
      </ModalMask>
      <VideoModal
        visible={showVideoModal}
        width={853}
        height={505}
        onClose={() => setShowVideoModal(false)}
        src="https://www.youtube-nocookie.com/embed/FM2P1D-yjOU?rel=0"
      />
    </>
  );
};
export default compose(
  withRouter,
  connect(
    (state) => ({
      currentOnboarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PL),
    }),
    { handleProductQuickGuide, ...EventActions },
  ),
  rTracking(() => window.rzpQ.component('InstantActivationModal')),
)(InstantActivationModal);
