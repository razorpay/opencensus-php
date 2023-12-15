import { useEffect, useCallback } from 'react';
import Card from 'merchant/views/MagicCheckout/MagicSettings/components/shopify/Card';
import Form from 'merchant/views/MagicCheckout/MagicSettings/components/shopify/Form';
import {
  FETCH_STATUS,
  CHECKOUT_FORM,
  CHECKOUT_CARD,
  CHECKOUT_SETTINGS,
  SHOPIFY_CHECKOUT_SETTINGS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { getInitialSettings } from 'merchant/views/MagicCheckout/MagicSettings/containers/helpers';

const CheckoutWrapper = ({
  settings,
  showFormView,
  setCurrentView,
  checkoutSettings,
  setCheckoutSettings,
}) => {
  const {
    nestedTabsStatus,
    one_cc_auto_fetch_coupons,
    one_cc_international_shipping,
    one_cc_capture_billing_address,
    one_cc_capture_gstin,
    one_cc_capture_order_instructions,
    one_cc_hide_cod_when_disabled,
  } = settings;

  useEffect(() => {
    if (nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setCurrentView(CHECKOUT_CARD);
    } else {
      setCurrentView(CHECKOUT_FORM);
    }
  }, [settings]);

  useEffect(() => {
    setCheckoutSettings((prevSettings) => {
      const tempCheckoutSettings = getInitialSettings(SHOPIFY_CHECKOUT_SETTINGS, prevSettings);

      tempCheckoutSettings.forEach((settingItem) => {
        settingItem.value = settings[settingItem.key];
      });
      return tempCheckoutSettings;
    });
  }, [
    one_cc_auto_fetch_coupons,
    one_cc_international_shipping,
    one_cc_capture_billing_address,
    one_cc_capture_gstin,
    one_cc_capture_order_instructions,
    one_cc_hide_cod_when_disabled,
    setCheckoutSettings,
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
        />
      ) : (
        <Card
          settings={settings}
          switchToEdit={switchToEdit}
          cardTitle={CHECKOUT_SETTINGS}
          cardItems={SHOPIFY_CHECKOUT_SETTINGS}
        />
      )}
    </>
  );
};

export default CheckoutWrapper;
