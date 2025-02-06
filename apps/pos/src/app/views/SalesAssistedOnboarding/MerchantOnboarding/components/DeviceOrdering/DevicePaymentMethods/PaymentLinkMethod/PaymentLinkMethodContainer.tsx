import React, { useEffect, useMemo, useState } from 'react';
import PaymentLinkMethod from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/PaymentLinkMethod/PaymentLinkMethod';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import moment from 'moment';
import {
  getOrderSummaryFieldsFromModularConfig,
  getDevicePaymentDetails,
} from 'apps/pos/src/app/utils/deviceSelection';
import copyToClipboard from 'apps/pos/src/app/utils/copyToClipboard';
import { CheckCircleIcon, useToast } from '@razorpay/blade/components';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { analyticsTypes, trackEvent } from 'apps/pos/src/services/analytics';
import { PAGE_TYPES } from 'apps/pos/src/services/analytics/types';

const PaymentLinkMethodContainer = (): JSX.Element | null => {
  const [isResendingLink, setIsResendingLink] = useState(false);
  const toast = useToast();
  const { states, handlers } = useOnboardingContext();
  const { updateModularConfig, handleProceedToNextComponent } = handlers;
  const { modularConfig, merchantDetails, isUpdateModularLoading } = states;

  const paymentDetails = getDevicePaymentDetails({
    modularConfig,
  });
  const { paymentLinkStatus, paymentLinkUrl, paymentLinkCreatedAt, paymentLinkCompletedAt } =
    paymentDetails ?? {};

  const deviceSummary = useMemo(() => {
    return getOrderSummaryFieldsFromModularConfig({ modularConfig });
  }, [modularConfig]);
  const { orderSummary } = deviceSummary ?? {};

  const moveToOrderSuccessScreen = () => {
    handleProceedToNextComponent({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.DEVICE_PAYMENT]: true,
      },
    });
  };

  const generatePaymentLink = () => {
    updateModularConfig({
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_EMAIL]:
        merchantDetails?.contactPerson.email.value || '',
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_MOBILE]:
        merchantDetails?.contactPerson.phone.value.number || '',
      [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_PAYMENT_LINK_AMOUNT_FIELD]:
        orderSummary?.totalOrderCharge || 0,
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_GENERATE_PAYMENT_LINK_FIELD]: moment().unix(),
    });
  };

  //used to generate link after old link expired
  const generateNewPaymentLink = () => {
    updateModularConfig({
      [MODULAR_DEVICE_FIELDS.DEVICE_GENERATE_PAYMENT_LINK_FIELD]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
    });
  };

  const checkPaymentStatus = () => {
    updateModularConfig({
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_PAYMENT_LINK_STATUS_FIELD]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
    });
  };

  const handleModularUpdate = () => {
    if (!paymentLinkStatus) {
      generatePaymentLink();
      return;
    }
    if (paymentLinkStatus === 'expired' || paymentLinkStatus === 'cancelled') {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Generate New Link',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
          section: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          subSection: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
        },
      });
      generateNewPaymentLink();
      return;
    }
    if (paymentLinkStatus === 'created') {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Check Payment Status',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
          section: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_STATUS,
          subSection: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
        },
      });
      return checkPaymentStatus();
    }
    if (paymentLinkStatus === 'paid') {
      return moveToOrderSuccessScreen();
    }
  };

  const onResendBtnClick = () => {
    try {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Resend Link',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_LINK_PAYMENT_SCREEN,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
          section: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_LINK_PAYMENT_SCREEN,
          subSection: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
        },
      });
      setIsResendingLink(true);
      updateModularConfig({
        [MODULAR_DEVICE_FIELDS.DEVICE_RESEND_PAYMENT_LINK_FIELD]: moment().unix(),
        [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
        [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: () => {
          trackEvent({
            eventName: analyticsTypes.ANALYTICS_EVENTS.IMAGE,
            action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
            properties: {
              l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_LINK_PAYMENT_SCREEN,
              l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAYMENT_FAILED,
              section: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_LINK_PAYMENT_SCREEN,
              subSection: 'Payment Link re-sent successfully',
            },
          });
          setIsResendingLink(false);
          toast.show({
            color: 'positive',
            content: 'Payment link re-sent successfully',
            leading: CheckCircleIcon,
          });
        },
      });
    } catch (error) {
      console.log(error);
      setIsResendingLink(false);
    }
  };

  const onCopyBtnClick = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Copy Link',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_LINK_PAYMENT_SCREEN,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
        section: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_LINK_PAYMENT_SCREEN,
        subSection: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
      },
    });
    copyToClipboard(`${paymentLinkUrl}`);
    toast.show({
      color: 'positive',
      content: 'Link copied successfully',
      leading: CheckCircleIcon,
    });
  };

  useEffect(() => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.PAGE,
      action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
      properties: {
        label: 'Payment Link',
        pageType: PAGE_TYPES.PAYMENT_LINK_CHECKOUT_PAGE,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_PAGE,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAGE_VIEW,
      },
    });
  }, []);

  if (!modularConfig) return null;
  return (
    <PaymentLinkMethod
      onResendBtnClick={onResendBtnClick}
      onCopyBtnClick={onCopyBtnClick}
      paymentLinkDetails={{
        paymentLinkStatus: paymentDetails?.paymentLinkStatus || '',
        paymentLinkCreatedAt,
        paymentLinkCompletedAt,
      }}
      handleModularUpdate={handleModularUpdate}
      amount={orderSummary?.totalOrderCharge || 0}
      isUpdateModularLoading={isUpdateModularLoading}
      isResendingLink={isResendingLink}
    />
  );
};

export default PaymentLinkMethodContainer;
