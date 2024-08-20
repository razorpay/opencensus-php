import React, { useEffect, useCallback } from 'react';
import { connect } from 'react-redux';

import Card from 'merchant/views/MagicCheckout/MagicSettings/components/common/Card';
import Form from 'merchant/views/MagicCheckout/MagicSettings/components/common/Form';

import { useSplitzService } from 'common/splitz';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';
import { getInitialSettings } from 'merchant/views/MagicCheckout/MagicSettings/containers/helpers';

import {
  FETCH_STATUS,
  CHECKOUT_FORM,
  CHECKOUT_CARD,
  CHECKOUT_SETTINGS,
  CHECKOUT_SETTINGS_CONFIG,
  ADDITIONAL_WOOC_SETTINGS_CONFIG,
  CHECKOUT_SETTINGS_CAPTURE_BILLING,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';

const CheckoutWrapper = ({
  settings,
  showFormView,
  setCurrentView,
  checkoutSettings,
  setCheckoutSettings,
  extraClass,
  user,
}) => {
  const {
    nestedTabsStatus,
    one_cc_capture_gstin,
    one_cc_capture_order_instructions,
    one_cc_hide_cod_when_disabled,
    one_cc_capture_billing_address,
  } = settings;

  const { isMagicWoocEnabled } = user;
  const { abExperiments } = useSplitzService();

  const isHideCodWhenDisabledExperimentEnabled =
    abExperiments?.magic_hide_cod_when_disabled?.variables?.result === 'on';
  const isMagicDashboardV2Enabled = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT);

  useEffect(() => {
    if (nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setCurrentView(CHECKOUT_CARD);
    } else {
      setCurrentView(CHECKOUT_FORM);
    }
  }, [nestedTabsStatus]);

  useEffect(() => {
    setCheckoutSettings((prevSettings) => {
      let checkoutSettings = CHECKOUT_SETTINGS_CONFIG;

      if (settings.platform === 'woocommerce' && isMagicWoocEnabled) {
        checkoutSettings = [...CHECKOUT_SETTINGS_CONFIG, ...ADDITIONAL_WOOC_SETTINGS_CONFIG];
      }
      /**
       * We will be moving capture billing address to checkout setup from wooc shipping settings
       */
      if (isMagicDashboardV2Enabled) {
        checkoutSettings = [...checkoutSettings, ...CHECKOUT_SETTINGS_CAPTURE_BILLING];
      }

      if (!isHideCodWhenDisabledExperimentEnabled) {
        const index = checkoutSettings.findIndex(
          (setting) => setting.key === 'one_cc_hide_cod_when_disabled',
        );
        if (index >= 0) checkoutSettings.splice(index, 1);
      }

      const tempCheckoutSettings = getInitialSettings(checkoutSettings, prevSettings);

      tempCheckoutSettings.forEach((settingItem) => {
        settingItem.value = settings[settingItem.key];
      });
      return tempCheckoutSettings;
    });
  }, [
    one_cc_capture_gstin,
    one_cc_capture_order_instructions,
    settings,
    one_cc_hide_cod_when_disabled,
    isHideCodWhenDisabledExperimentEnabled,
    one_cc_capture_billing_address,
    isMagicDashboardV2Enabled,
  ]);

  const onToggleCheckout = useCallback((checked, label) => {
    setCheckoutSettings((prevSettings) => {
      const tempCheckoutSettings = [...prevSettings];
      tempCheckoutSettings.forEach((setting) => {
        if (setting.label === label) {
          setting.value = checked;
        }
      });

      return tempCheckoutSettings;
    });
  }, []);

  const switchToEdit = () => setCurrentView(CHECKOUT_FORM);

  return (
    <>
      {showFormView ? (
        <Form
          formTitle={CHECKOUT_SETTINGS}
          formItems={checkoutSettings}
          onToggle={onToggleCheckout}
          extraClass={extraClass}
        />
      ) : (
        <Card
          settings={settings}
          switchToEdit={switchToEdit}
          cardTitle={CHECKOUT_SETTINGS}
          cardItems={checkoutSettings}
          extraClass="checkout-card"
        />
      )}
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(CheckoutWrapper);
