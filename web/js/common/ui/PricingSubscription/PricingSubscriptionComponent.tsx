import { withRouter } from 'common/deprecated/withRouter';
import {
  IMPRESSION_TIME_INTERVAL,
  LS_LABELS,
  PAYMENT_TYPE,
} from 'common/ui/PricingSubscription/constants';
import type {
  PaymentType,
  PlansType,
  PricingSubscriptionProps,
  TogglePlan,
  TrackingObjectType,
} from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';
import { setCookie } from 'common/utils/cookies';
import { getAssetTrackingProperties } from 'merchant/models/GrowthService/commonUtils';
import { fetchGSModal as fetchGSModalAction } from 'merchant/reducers/growthService';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import rTracking, { useTracking } from 'react-tracking';
import { bindActionCreators, compose } from 'redux';
import { Container, PlanRow, PlanSection, StyleHeroImage } from './PricingStyled';

import { Box } from '@razorpay/blade/components';
import {
  getPaymentOptions,
  ReturnPaymentResponse,
} from 'common/ui/PricingSubscription/API/getPaymentDetails.api';
import { INITIAL_PLAN, MultiPaymentContext } from 'common/ui/PricingSubscription/PricingContext';
import MultiPaymentModal from 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions';
import { MODAL_TYPE } from 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions/constant';
import { PRICING_BUNDLE_VARIANT } from 'merchant/models/GrowthService/growthServiceCTAHandler';
import lazy from 'merchant/routes/LazyLoader';
import {
  FooterButton,
  PlanDetails,
  handleCheckoutPayment,
  ModalLoader,
  PricingHeader,
  PricingTncInfoMemo,
  TogglePlanValue,
} from './PricingBundleCommon';
import { equalizeRowElementHeights } from './utils';

const CongratulatoryModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'BundlePricingCongratulatoryModal' */ 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions/CongratulatoryModal'
    ),
);
export interface MULTIPAYMENT_DATA_TYPE {
  planName: string;
  amount: number;
  frequency: string;
  taxPercentage: number;
  icon: string;
}
let outsidePlanSectionTimer;
const MULTIPAYMENT_DATA = Object.freeze({
  planName: '',
  amount: 0,
  frequency: '',
  taxPercentage: 0,
  icon: '',
});

const PricingSubscriptionComponent = ({
  pricingSubscription,
  closeModal,
  showNotificationToast,
  user,
  variant = '',
  templateId,
  fetchGSModal,
  loading,
  gs_modals = {},
  currentBalance,
  isMobile,
}: PricingSubscriptionProps): React.ReactElement | null => {
  const isReadOnly = variant === PRICING_BUNDLE_VARIANT.READ_ONLY;
  const [isFullView, setFullView] = useState(false);
  const [isLoading, setLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [isPaymentOptionLoading, setIsPaymentOptionLoading] = useState(false);
  const checkoutId = useRef('');
  const [selectedPlanId, setSelectedPlanId] = useState('');
  const [selectedPaymentMode, setSelectedPaymentMode] = useState<PaymentType>(PAYMENT_TYPE.PG);
  const [isCongModalOpen, setCongratulatoryModal] = useState(false);
  const [modalType, setModalType] = useState('');
  const [selectedPlan, setSelectedPlan] = useState<PlansType>({ ...INITIAL_PLAN });
  const [multiPaymentData, setMultiPaymentData] = useState<MULTIPAYMENT_DATA_TYPE>({
    ...MULTIPAYMENT_DATA,
  });
  const [togglePlan, setTogglePlan] = useState<TogglePlan>(TogglePlanValue.monthly);
  const columnRefs = useRef<(HTMLDivElement | null)[]>([]);

  const { trackEvent } = useTracking({ page: 'Home' });
  const totalPlanTimer = {};
  const { data: { balance = 0 } = {} } = currentBalance || {};
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

  const trackInstrumentation = (type: string, trackingObject: TrackingObjectType) => {
    const { toggle_switch, cta_value, section, event_name, event_method } = trackingObject || {};

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

    if (event_method === 'initiated') {
      trackEvent(
        window.rzpQ &&
          window.rzpQ.merchantActions().initiated(event_name, {
            ...trackingObject,
            ...handleEventBasedOnType(),
          }),
      );
    } else {
      trackEvent(
        window.rzpQ &&
          window.rzpQ
            .merchantActions()
            .clicked(`${event_name || 'merchant_dashboard.click_cta_initiated'}`, {
              ...trackingObject,
              ...handleEventBasedOnType(),
            }),
      );
    }
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
  const togglePaymentOptionModal = (): void => {
    if (isOpen)
      trackInstrumentation('', {
        event_method: 'initiated',
        cta_value: 'Close',
        toggle_switch: togglePlan,
        modal: 'Payment method selection modal',
        plan_id: selectedPlan?.id,
        plan_name: selectedPlan?.title,
        event_name: 'merchant_dashboard.click_close',
      });
    setIsOpen((prevState) => !prevState);
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

  const handlePaymentSuccess = (response, plans) => {
    setModalType(MODAL_TYPE.CHECKOUT_PAYMENT);
    setCongratulatoryModal(true);
    if (isOpen) togglePaymentOptionModal();
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

  const checkoutPayment = {
    trackInstrumentation,
    togglePlan,
    setLoading: setIsPaymentOptionLoading,
    isLoading: isPaymentOptionLoading,
    setSelectedPlanId,
    handlePaymentSuccess,
    handlePaymentFailure,
    showNotificationToast,
    planAmount: multiPaymentData.amount,
    settlementBalance: balance,
    setCongratulatoryModal,
    setModalType,
    togglePaymentOptionModal,
    closeModal,
  };

  const handleClose = (buttonType: string | undefined): void => {
    if (isLoading) return;

    const eventType = buttonType === 'close' ? 'CloseCTA' : 'NotInterestedCTA';
    const ctaValue = buttonType === 'close' ? 'Close' : 'Not Interested';
    const eventName =
      buttonType === 'close'
        ? 'merchant_dashboard.click_close.initiated'
        : 'merchant_dashboard.not_interested.initiated';

    trackInstrumentation(eventType, {
      toggle_switch: togglePlan,
      cta_value: ctaValue,
      event_name: eventName,
    });

    if (buttonType !== 'close' && !templateId) {
      localStorage.setItem(`${LS_LABELS.NOT_INTERESTED}-${user?.current}`, '1');
    }

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

  const getPaymentMethodCall =
    (
      plans,
    ): ((plans?: PlansType | React.MouseEvent<HTMLButtonElement, MouseEvent>) => Promise<void>) =>
    async () => {
      setLoading(true);
      setSelectedPlanId(plans.id);
      try {
        const paymentMethod: unknown = await getPaymentOptions({ plans, togglePlan });
        const {
          payment_methods,
          name: planName,
          amount,
          frequency,
          tax_percentage,
          icon_url,
        } = paymentMethod as ReturnPaymentResponse;
        setMultiPaymentData({
          planName,
          amount,
          frequency,
          taxPercentage: tax_percentage,
          icon: icon_url,
        });
        if (
          payment_methods.includes(PAYMENT_TYPE.INTERNAL) &&
          payment_methods.includes(PAYMENT_TYPE.PG)
        ) {
          setSelectedPlan(plans);
          setSelectedPaymentMode(PAYMENT_TYPE.INTERNAL); // Since once we get payment mode from BE, default will be INTERNAL payment mode(settlement balance)
          togglePaymentOptionModal();
        } else {
          handleCheckoutPayment({ ...checkoutPayment, plans, type: PAYMENT_TYPE.PG })();
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

  return (
    <Container
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
        toggleSwitchButton={toggleAnnualPlan}
        pillText={String(pillText)}
        handleClose={handleClose}
      />

      <PlanSection>
        <Box display="flex" justifyContent="space-between" alignItems="flex-start">
          <PlanRow noBorder>
            <StyleHeroImage>
              <img src={heroSrc} alt={heroAlt} />
            </StyleHeroImage>
          </PlanRow>

          {pricingPlans.map((plans, index) => {
            return (
              <PlanRow
                key={index}
                addRightMargin
                isRecommended={plans?.isRecommended}
                onMouseEnter={handleMouseEnter(plans?.title)}
                onMouseLeave={handleMouseLeave(plans?.title)}
                ref={(el) => (columnRefs.current[index] = el)}
                data-testid={`plan-column-${plans.id}`}
              >
                <PlanDetails
                  plans={plans}
                  togglePlan={togglePlan}
                  isReadOnly={isReadOnly}
                  isLoading={isLoading}
                  selectedPlanId={selectedPlanId}
                  getPaymentMethodCall={getPaymentMethodCall}
                  isPaymentOptionLoading={isPaymentOptionLoading}
                  featureIdOrder={featureIdOrder}
                  featureIdToFeatureCopyMap={featureIdToFeatureCopyMap}
                  isFullView={isFullView}
                  columnRefs={columnRefs}
                  featureIndex={index}
                  handleMouseLeave={handleMouseLeave}
                  handleMouseEnter={handleMouseEnter}
                />
              </PlanRow>
            );
          })}
        </Box>

        <PricingTncInfoMemo isMobile={isMobile} />
      </PlanSection>
      <MultiPaymentContext.Provider
        value={{ checkoutPayment, currentBalance, plans: selectedPlan, multiPaymentData }}
      >
        <MultiPaymentModal
          isOpen={isOpen}
          togglePaymentOptionModal={togglePaymentOptionModal}
          setSelectedPaymentMode={setSelectedPaymentMode}
        />
      </MultiPaymentContext.Provider>

      {isCongModalOpen ? (
        <CongratulatoryModal
          type={modalType}
          isCongModalOpen={isCongModalOpen}
          setCongratulatoryModal={setCongratulatoryModal}
          trackInstrumentation={trackInstrumentation}
          selectedPlan={selectedPlan}
          selectedPaymentMode={selectedPaymentMode}
          togglePlan={togglePlan}
        />
      ) : null}
      <FooterButton
        equalizeRowElementHeights={() => equalizeRowElementHeights(pricingPlans, columnRefs)}
        handleToggle={handleToggle}
        isFullView={isFullView}
        handleClose={handleClose}
      />
    </Container>
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
      currentBalance: state.home.current_balance,
      isMobile: state.app.isMobileResolution,
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
