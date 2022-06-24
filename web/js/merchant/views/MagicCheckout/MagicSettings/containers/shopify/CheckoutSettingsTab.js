import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useCallback, useEffect } from 'react';
import isEmpty from '@universe/utils/isEmpty';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import {
  fetchMagicSettings,
  updateMagicSettings,
} from 'merchant/reducers/magicCheckout/magicSettings/actions';
import {
  SHOPIFY_CHECKOUT_SETTINGS,
  SHOPIFY_ANALYTICS_SETTINGS,
  FETCH_STATUS,
  PLATFORMS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { AsyncBtn } from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';

const CheckoutSettingsTab = ({ settings, merchantId, updateSettings }) => {
  const [checkoutSettings, setCheckoutSettings] = useState([]);
  const [analyticSettings, setAnalyticSettings] = useState([]);

  const getInitialSettings = (initialSettings, currentSettings) =>
    isEmpty(checkoutSettings) ? initialSettings : [...currentSettings];

  useEffect(() => {
    setCheckoutSettings((prevSettings) => {
      const tempCheckoutSettings = getInitialSettings(SHOPIFY_CHECKOUT_SETTINGS, prevSettings);

      tempCheckoutSettings.forEach((settingItem) => {
        settingItem.value = settings[settingItem.key];
      });
      return tempCheckoutSettings;
    });

    setAnalyticSettings((prevSettings) => {
      const tempAnalyticSettings = getInitialSettings(SHOPIFY_ANALYTICS_SETTINGS, prevSettings);
      tempAnalyticSettings.forEach((settingItem) => {
        settingItem.value = settings[settingItem.key];
      });

      return tempAnalyticSettings;
    });
  }, [settings]);

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

  const onToggleAnalytics = useCallback((checked, label) => {
    setAnalyticSettings((prevSettings) => {
      const tempAnalyticSettings = [...prevSettings];
      tempAnalyticSettings.forEach((setting) => {
        if (setting.label === label) {
          setting.value = checked;
        }
      });

      return tempAnalyticSettings;
    });
  }, []);

  const onSave = useCallback(() => {
    const payload = {
      platform: PLATFORMS.VALUES.SHOPIFY,
      shop_id: settings?.shop_id,
    };
    checkoutSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });
    analyticSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });

    const {
      one_cc_buy_now_button,
      one_cc_auto_fetch_coupons,
      one_cc_international_shipping,
      one_cc_capture_billing_address,
      one_cc_ga_analytics,
      one_cc_fb_analytics,
    } = payload;
    analyticsTrack({
      objectName: '1ccclickedsaveplatformsettings',
      actionName: 'behav',
      properties: {
        buy_now: one_cc_buy_now_button,
        auto_fetch_coupon: one_cc_auto_fetch_coupons,
        international_shipping: one_cc_international_shipping,
        capture_billing_address: one_cc_capture_billing_address,
        google_analytics: one_cc_ga_analytics,
        facebook_pixel: one_cc_fb_analytics,
        magic_checkout: settings.one_click_checkout,
        platform: PLATFORMS.VALUES.SHOPIFY,
        store_id: settings?.shop_id,
        merchant_id: merchantId,
      },
    });

    updateSettings(payload);
  }, [updateSettings, checkoutSettings, analyticSettings]);

  return (
    <div className="checkout-settings">
      <p className="font-18 font-bold checkout-headings">Checkout Settings</p>
      {checkoutSettings.map((setting) => (
        <SettingsToggle key={setting.label} setting={setting} onToggle={onToggleCheckout} />
      ))}
      <hr />
      <p className="font-18 font-bold checkout-headings">Analytics Settings</p>
      {analyticSettings.map((setting) => (
        <SettingsToggle key={setting.label} setting={setting} onToggle={onToggleAnalytics} />
      ))}
      <AsyncBtn.Primary
        type="button"
        isPending={settings.status === FETCH_STATUS.LOADING}
        onClick={onSave}
        className="save-cta"
      >
        Save settings
      </AsyncBtn.Primary>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchSettings: fetchMagicSettings,
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CheckoutSettingsTab);
