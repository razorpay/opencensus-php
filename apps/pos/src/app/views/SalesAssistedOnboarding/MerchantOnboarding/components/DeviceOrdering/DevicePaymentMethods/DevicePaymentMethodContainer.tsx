import React, { useContext, useEffect, useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import DevicePaymentMethods from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/DevicePaymentMethods';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import {
  getDevicePaymentMethods,
  getOrderSummaryFieldsFromModularConfig,
  getDevicePaymentDetails,
} from 'apps/pos/src/app/utils/deviceSelection';
import moment from 'moment';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { SpiltzContext } from 'shell/SpiltzServiceContext';
import { analyticsTypes, trackEvent } from 'apps/pos/src/services/analytics';

const DevicePaymentMethodContainer = (): JSX.Element | null => {
  const { handlers, states } = useOnboardingContext();
  const splitz = useContext(SpiltzContext);
  const [isModuleLoading, setIsModuleLoading] = React.useState({
    isPaymentLinkLoading: false,
    isQrCodeLoading: false,
  });
  const isPaymentLinkEnabled = splitz.abExperiments?.pos_payment_link?.variables?.result === 'on';
  const { handleProceedToNextComponent, updateModularConfig } = handlers;
  const { modularConfig, isUpdateModularLoading, merchantDetails } = states;

  const deviceSummary = useMemo(() => {
    return getOrderSummaryFieldsFromModularConfig({ modularConfig });
  }, [modularConfig]);
  const { orderSummary } = deviceSummary ?? {};

  const paymentLinkDetails = getDevicePaymentDetails({
    modularConfig,
  });
  const { paymentLinkStatus, qrCodePaymentStatus } = paymentLinkDetails ?? {};

  const moveToPaymentLinkScreen = () => {
    handleProceedToNextComponent({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.PAYMENT_LINK_METHOD]: true,
      },
    });
  };
  const moveToOrderSuccessScreen = () => {
    handleProceedToNextComponent({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.DEVICE_PAYMENT]: true,
      },
    });
  };

  const onClickScanAndPay = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Scan and Pay',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_PAYMENT_SCREEN,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
        section: 'Order Status',
        subSection: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
      },
    });
    setIsModuleLoading((prev) => ({ ...prev, isQrCodeLoading: true }));
    if (qrCodePaymentStatus === 'success') return moveToOrderSuccessScreen();
    const payload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_OPTIONS_FIELD]: MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE,
      [MODULAR_DEVICE_FIELDS.DEVICE_CREATE_QR_CODE_FIELD]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_QR_AMOUNT]: orderSummary?.totalOrderCharge || 0,
      [MODULAR_DEVICE_FIELDS.DEVICE_CANCEL_PAYMENT_LINK_FIELD]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: () =>
        handleProceedToNextComponent({
          __typeName: 'custom_routing',
          routerConditions: {
            [AvailableComponents.DEVICE_PAYMENT]: true,
          },
        }),
    };
    updateModularConfig(payload);
  };

  useEffect(() => {
    if (!isUpdateModularLoading) {
      setIsModuleLoading({ isPaymentLinkLoading: false, isQrCodeLoading: false });
    }
  }, [isUpdateModularLoading]);

  const onClickPaymentLink = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Payment Link',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_PAYMENT_SCREEN,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
        section: 'Order Status',
        subSection: analyticsTypes.L2_FUNNEL_STAGE.CHECKOUT_CONFIRMATION,
      },
    });
    setIsModuleLoading((prev) => ({ ...prev, isPaymentLinkLoading: true }));
    if (paymentLinkStatus === 'paid') return moveToPaymentLinkScreen();
    const payload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_EMAIL]:
        merchantDetails?.contactPerson.email.value || '',
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_MOBILE]:
        merchantDetails?.contactPerson.phone.value.number || '',
      [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_PAYMENT_LINK_AMOUNT_FIELD]:
        orderSummary?.totalOrderCharge || 0,
      [MODULAR_DEVICE_FIELDS.DEVICE_GENERATE_PAYMENT_LINK_FIELD]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_OPTIONS_FIELD]:
        MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK,
      [MODULAR_DEVICE_FIELDS.DEVICE_CLOSE_QR_FIELD]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: moment().unix(),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: moveToPaymentLinkScreen,
    };
    updateModularConfig(payload);
  };

  if (!modularConfig) return null;
  return (
    <Box padding={['spacing.7', 'spacing.5', 'spacing.7', 'spacing.5']}>
      <DevicePaymentMethods
        onClickScanAndPay={onClickScanAndPay}
        onClickPaymentLink={onClickPaymentLink}
        title={'Payment Options'}
        paymentMethods={getDevicePaymentMethods({ modularConfig }) ?? []}
        isUpdateModularLoading={isUpdateModularLoading}
        isModuleLoading={isModuleLoading}
        paymentLinkStatus={paymentLinkStatus ?? ''}
        qrPaymentStatus={qrCodePaymentStatus ?? ''}
        isPaymentLinkEnabled={isPaymentLinkEnabled}
      />
    </Box>
  );
};

export default DevicePaymentMethodContainer;
