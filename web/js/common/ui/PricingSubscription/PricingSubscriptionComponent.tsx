import React, { useEffect, useState, useRef } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import rTracking, { useTracking } from 'react-tracking';
import { withRouter } from 'react-router';
import { merchantFetch } from 'merchant/utils/ajax';
import { getAssetTrackingProperties } from 'merchant/models/GrowthService/commonUtils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { Button } from '@razorpay/blade/components';
import Image from 'common/ui/Image';
import {
  StyledDiv,
  StyledTable,
  StyledTr,
  StyledTh,
  StyledTd,
  StyleHeroImage,
} from './PricingStyled';
import { loadCheckoutScript } from 'merchant/views/Capital/utils';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { LS_LABELS, IMPRESSION_TIME_INTERVAL } from 'common/ui/PricingSubscription/constants';
import { setCookie } from 'common/utils/cookies';
import { fetchGSModal as fetchGSModalAction } from 'merchant/reducers/growthService';
import type {
  PlansType,
  PricingSubscriptionProps,
  TogglePlan,
} from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

import { PRICING_BUNDLE_VARIANT } from 'merchant/models/GrowthService/growthServiceCTAHandler';
import rzpLogo from 'assets/rzp_logo.jpg';
import pricingTag from 'assets/pricing-bundle/pricingTag.svg';
import {
  FooterButton,
  plansDetailsForViewMore,
  PricingHeader,
  RedirectToastUI,
  getPlanPrice,
  TogglePlanValue,
  ModalLoader,
  PricingTncInfo,
} from './PricingBundleCommon';

let outsidePlanSectionTimer;
const PricingSubscriptionComponent = ({
  pricingSubscription,
  closeModal,
  showNotificationToast,
  user,
  history,
  variant = '',
  templateId,
  fetchGSModal,
  loading,
  gs_modals = {},
}: PricingSubscriptionProps): React.ReactElement | null => {
  const isReadOnly = variant === PRICING_BUNDLE_VARIANT.READ_ONLY;
  const [isFullView, setFullView] = useState(false);
  const [isChecked, setChecked] = useState(false);
  const [isLoading, setLoading] = useState(false);
  const checkoutId = useRef('');
  const [selectedPlanId, setSelectedPlanId] = useState('');
  const [togglePlan, setTogglePlan] = useState<TogglePlan>(TogglePlanValue.monthly);
  const { trackEvent } = useTracking({ page: 'Home' });
  const totalPlanTimer = {};
  const {
    pricingPlans = [],
    featureIdOrder = [],
    id: trackingId = '',
    featureIdToFeatureCopyMap = {},
    heroImage: { alt: heroAlt = '', src: heroSrc = '' } = {},
    header: {
      pillText = '',
      title = '',
      icon: { alt: headerAlt = '', src: headerSrc = '' } = {},
    } = {},
    tracking_data = {},
  } = (templateId ? gs_modals : pricingSubscription?.[0]) || {};

  const trackingData = {
    trackingID: trackingId,
    source: 'Home',
    ...getAssetTrackingProperties(trackingId, tracking_data, {}),
  };

  const trackInstrumentation = (
    type: string,
    trackingObject: {
      toggle_switch?: string;
      cta_value?: string;
      section?: string;
      type?: string;
      value?: string;
      event_name?: string;
      plan_Activated?: string;
      response_code?: string;
      payment_id?: string;
      plan_id?: string;
      time_spent?: any;
      checkout_id?: string;
    },
  ) => {
    const { toggle_switch, cta_value, section, event_name } = trackingObject || {};

    const handleEventBasedOnType = () => {
      switch (type) {
        case 'choosePlanCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...(cta_value && { cta_value }),
            ...(section && { section }),
            ...trackingData,
          };
        case 'NotInterestedCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...(cta_value && { cta_value }),
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'CloseCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'Overlay':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'paymentSuccess':
          return {
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'viewMoreCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...(cta_value && { cta_value }),
            ...trackingData,
          };
        case 'toogleSwitch':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...trackingData,
          };
        default:
          return trackingData;
      }
    };

    trackEvent(
      window.rzpQ &&
        window.rzpQ
          .merchantActions()
          .clicked(`${event_name || 'merchant_dashboard.click_cta_initiated'}`, {
            ...trackingObject,
            ...handleEventBasedOnType(),
          }),
    );
  };

  const timer = () => {
    let timerId, elapsedTime;
    const totalTime = 0;

    const pause = (obj) => {
      window.clearInterval(obj.timerId);
      obj.timerId = null;
      obj.elapsedTime = null;
    };

    const startTimer = (obj) => {
      if (timerId) {
        return;
      }
      const startTime = Date.now();
      obj.timerId = setInterval(() => {
        obj.elapsedTime = Date.now() - startTime;
        // obj.totalTime += Number((obj.elapsedTime / 1000).toFixed(3)); // in sec
        obj.totalTime += Number(obj.elapsedTime); // in millisec
      }, 100);
    };

    return {
      pause,
      startTimer,
      timerId,
      elapsedTime,
      totalTime,
    };
  };

  useEffect(() => {
    if (templateId) fetchGSModal({ template_id: templateId });
  }, []);

  useEffect(() => {
    outsidePlanSectionTimer = timer();
    outsidePlanSectionTimer.startTimer(outsidePlanSectionTimer);
    return () => {
      clearTimeout(outsidePlanSectionTimer.pause(outsidePlanSectionTimer));
    };
  }, []);
  useEffect(() => {
    trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().success(`merchant_dashboard.pricing_banner`, {
          screen: 'Monthly Plans',
          ...trackingData,
        }),
    );
  }, [trackEvent]);

  useEffect(() => {
    if (!templateId) {
      const impressionCount = localStorage.getItem(
        `${LS_LABELS.IMPRESSION_COUNT}-${user?.current}`,
      );
      localStorage.setItem(
        `${LS_LABELS.IMPRESSION_COUNT}-${user?.current}`,
        String(Number(impressionCount) + 1),
      );

      const expiryTime = new Date();
      expiryTime.setTime(expiryTime.getTime() + IMPRESSION_TIME_INTERVAL);
      setCookie(`${LS_LABELS.LAST_IMPRESSION_WITHIN_INTERVAL}-${user?.current}`, '1', expiryTime);
    }
  }, []);

  const handleToggle = (): void => {
    setFullView((prevState) => !prevState);
    trackInstrumentation('viewMoreCTA', {
      toggle_switch: togglePlan,
      cta_value: '🎁 View All Benefits',
    });
  };

  const RedirectToast = (): JSX.Element => {
    const handleToastLink = () => {
      if (
        user.isAllowedMultiple(
          'webhooks applications configuration api_keys profile credits add_funds team referrals',
        ) &&
        user.isAccountAndSettingsRevampEnabled
      )
        history.push(ROUTES_INFO.PRICING_PLANS);
      else history.push('/pricing-plans');
    };
    return <RedirectToastUI handleToastLink={handleToastLink} />;
  };

  const toggleAnnualPlan = (): void => {
    if (togglePlan == TogglePlanValue.monthly) {
      setTogglePlan(TogglePlanValue.annual);
      trackInstrumentation('toogleSwitch', {
        toggle_switch: TogglePlanValue.annual,
        event_name: 'merchant_dashboard.toggle_switch.initiated',
      });
    } else {
      setTogglePlan(TogglePlanValue.monthly);
      trackInstrumentation('toogleSwitch', {
        toggle_switch: TogglePlanValue.monthly,
        event_name: 'merchant_dashboard.toggle_switch.initiated',
      });
    }
  };
  const toggleSwitchButton = (e): void => {
    setChecked(e.target.checked);
    toggleAnnualPlan();
  };
  const handlePaymentSuccess = (response, plans) => {
    closeModal();
    showNotificationToast({
      type: 'success',
      message: RedirectToast,
      closeTimeout: 15000,
    });
    trackInstrumentation('paymentSuccess', {
      value: 'success',
      payment_id: response?.razorpay_payment_id,
      toggle_switch: togglePlan,
      plan_id: plans.id,
      event_name: 'merchant_dashboard.subscription_checkout.success',
      checkout_id: checkoutId.current,
    });
  };
  const handlePaymentFailure = (response, plans) => {
    trackInstrumentation('', {
      value: 'failure',
      payment_id: response.error.metadata?.payment_id,
      response_code: response.error?.code,
      toggle_switch: togglePlan,
      plan_id: plans.id,
      event_name: 'merchant_dashboard.subscription_checkout.failure',
      checkout_id: checkoutId.current,
    });
  };
  const handleCheckoutInitiation = (plans) => {
    trackInstrumentation('', {
      value: 'success',
      toggle_switch: togglePlan,
      plan_id: plans.id,
      event_name: 'merchant_dashboard.checkout_modal.initiated',
      checkout_id: checkoutId.current,
    });
  };
  const handleCheckoutError = (plans) => {
    trackInstrumentation('', {
      value: 'failure',
      toggle_switch: togglePlan,
      plan_id: plans.id,
      event_name: 'merchant_dashboard.checkout_modal.initiated',
    });
    throw new Error('Something went wrong . Please try again');
  };
  const handleCheckoutPayment =
    (
      plans: PlansType,
    ): ((plans?: PlansType | React.MouseEvent<HTMLButtonElement, MouseEvent>) => Promise<void>) =>
    async () => {
      trackInstrumentation('choosePlanCTA', {
        toggle_switch: togglePlan,
        cta_value: plans.button?.label,
        section: plans?.title,
        plan_id: plans?.id,
      });
      setSelectedPlanId(plans.id);
      setLoading(true);
      await loadCheckoutScript();

      try {
        const subscriptionData = await merchantFetch({
          method: 'post',
          url: `pricing/merchant/subscriptions?plan_id=${plans.id}&frequency=${togglePlan}`,
          mode: 'live',
        });
        const { data: { response = {}, status_code = '' } = {} } = subscriptionData || {};

        if (status_code === 200) {
          const { subscription = {} } = response;
          const options = {
            key: subscription?.account_key,
            subscription_id: subscription?.payment_subscription_id,
            name: `Razorpay Pricing Package`,
            description: '18% GST included',
            image: rzpLogo,
            handler: (response) => {
              handlePaymentSuccess(response, plans);
            },
          };
          const razorpayCheckout = new window.Razorpay(options);
          checkoutId.current = razorpayCheckout?.id;
          razorpayCheckout.open();
          razorpayCheckout.on('payment.failed', (response) => {
            handlePaymentFailure(response, plans);
          });
          handleCheckoutInitiation(plans);
        } else {
          handleCheckoutError(plans);
        }
      } catch (e) {
        showNotificationToast({
          type: 'error',
          message: (e as Error)?.message || 'Something went wrong . Please try again',
        });
      } finally {
        setLoading(false);
      }
    };

  const handleClose =
    (buttonType: string | undefined): (() => void) =>
    () => {
      if (isLoading) return;
      trackInstrumentation(buttonType === 'close' ? 'CloseCTA' : 'NotInterestedCTA', {
        toggle_switch: togglePlan,
        cta_value: buttonType === 'close' ? 'Close' : 'Not Interested',
        event_name:
          buttonType === 'close'
            ? 'merchant_dashboard.click_close.initiated'
            : 'merchant_dashboard.not_interested.initiated',
      });

      if (buttonType !== 'close' && !templateId)
        localStorage.setItem(`${LS_LABELS.NOT_INTERESTED}-${user?.current}`, '1');

      const tempTimer = {};
      if (Object.entries(totalPlanTimer).length) {
        for (const [key, value] of Object.entries(totalPlanTimer)) {
          // TODO: Fix `any`
          tempTimer[key] = (value as any)?.totalTime;
        }
        trackInstrumentation('', {
          time_spent: {
            outside: outsidePlanSectionTimer?.totalTime,
            ...tempTimer,
          },
          event_name: 'merchant_dashboard.hover_plan.success',
        });
      }
      closeModal();
    };

  const handleMouseEnter = (title) => (): void => {
    outsidePlanSectionTimer?.pause(outsidePlanSectionTimer);
    if (!totalPlanTimer[title]) {
      totalPlanTimer[title] = timer();
    }
    totalPlanTimer[title]?.startTimer(totalPlanTimer[title]);
  };
  const handleMouseLeave = (title) => (): void => {
    if (outsidePlanSectionTimer?.startTime)
      outsidePlanSectionTimer?.startTime(outsidePlanSectionTimer);
    if (totalPlanTimer[title]) {
      totalPlanTimer[title]?.pause(totalPlanTimer[title]);
    }
  };

  const onClickOverlay = () => {
    trackInstrumentation('Overlay', {
      toggle_switch: togglePlan,
      cta_value: 'Overlay',
      event_name: 'merchant_dashboard.click_close.initiated',
    });
  };

  if (templateId) {
    if (!loading && Object.keys(gs_modals).length === 0) {
      closeModal();
      return null;
    } else if (loading) return <ModalLoader closeModal={closeModal} />;
  }

  const renderPlansDetails = (featureId, index) =>
    plansDetailsForViewMore({
      text: featureIdToFeatureCopyMap[featureId],
      pricingPlans,
      featureId,
      featureIndex: index,
      handleMouseEnter,
      handleMouseLeave,
      togglePlan,
    });
  return (
    <StyledDiv
      fullView={isFullView}
      onMouseLeave={() => {
        document.addEventListener('click', onClickOverlay, { once: true });
      }}
      onMouseEnter={() => {
        document.removeEventListener('click', onClickOverlay);
      }}
    >
      <PricingHeader
        headerSrc={headerSrc}
        headerAlt={headerAlt}
        title={title}
        isChecked={isChecked}
        toggleSwitchButton={toggleSwitchButton}
        pillText={String(pillText)}
        handleClose={handleClose}
      />
      <StyledTable pricingPlanLength={pricingPlans?.length}>
        <StyledTr headerHeight pricingPlanLength={pricingPlans?.length}>
          <StyledTh removeCss>
            <StyleHeroImage>
              <Image src={pricingTag} alt="Pricing Tag" />
              <Image src={heroSrc} alt={heroAlt} />
            </StyleHeroImage>
          </StyledTh>
          {pricingPlans.map((plans, index) => {
            return (
              <StyledTh
                addRightMargin
                key={index}
                isRecommend={plans?.isRecommended}
                onMouseEnter={handleMouseEnter(plans?.title)}
                onMouseLeave={handleMouseLeave(plans?.title)}
              >
                {getPlanPrice({
                  plans,
                  togglePlan,
                  isReadOnly,
                  isLoading,
                  selectedPlanId,
                  handleCheckoutPayment,
                })}
              </StyledTh>
            );
          })}
        </StyledTr>
        {featureIdOrder
          .filter((_, index) => isFullView || index < 3)
          .map((featureId, index) => renderPlansDetails(featureId, index))}
        <StyledTr pricingPlanLength={pricingPlans?.length}>
          <StyledTd removeCss />
          {isFullView &&
            pricingPlans.map((plans) => {
              return (
                !isReadOnly && (
                  <StyledTd lastRow key={plans?.id} isRecommend={plans?.isRecommended}>
                    <Button
                      isLoading={isLoading && selectedPlanId === plans?.id}
                      isDisabled={isLoading && selectedPlanId !== plans?.id}
                      variant={plans.button?.variant}
                      onClick={handleCheckoutPayment(plans)}
                      size="small"
                      type="button"
                    >
                      {plans.button?.label}
                    </Button>
                  </StyledTd>
                )
              );
            })}
        </StyledTr>
      </StyledTable>
      {isFullView && <PricingTncInfo />}
      <FooterButton handleToggle={handleToggle} isFullView={isFullView} handleClose={handleClose} />
    </StyledDiv>
  );
};

export default compose<any>(
  rTracking({
    page: 'DashboardBanner',
  }),
  withRouter,
  connect(
    (state) => ({
      user: state.session.user,
      ...state?.growthService?.gs_modals,
    }),
    (dispatch) => {
      return bindActionCreators(
        {
          closeModal: fnCloseModal,
          showNotificationToast: showNotification,
          fetchGSModal: fetchGSModalAction,
        },
        dispatch,
      );
    },
  ),
)(PricingSubscriptionComponent);
