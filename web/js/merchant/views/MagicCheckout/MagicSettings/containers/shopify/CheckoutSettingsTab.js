import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useCallback, useEffect } from 'react';
import GCWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/common/GCWrapper';
import CheckoutWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/CheckoutWrapper';
import AnalyticsWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/AnalyticsWrapper';
import {
  fetchMagicSettings,
  updateMagicSettings,
} from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { AsyncBtn } from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';
import { getGCAnalytics } from 'merchant/views/MagicCheckout/MagicSettings/containers/helpers';
import {
  FETCH_STATUS,
  PLATFORMS,
  SHOPIFY_BUY_NOW_BUTTON,
  SHOPIFY_CHECKOUT_SETTINGS,
  GC_FORM,
  CHECKOUT_FORM,
  ANALYTICS_FORM,
  CHECKOUT,
  ANALYTICS,
  GIFT_CARD,
  CARD,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import 'merchant/views/MagicCheckout/css/settings/checkout.styl';
import { updateDefaultViewInStorage } from 'merchant/views/MagicCheckout/utils/storeSettings';

const CheckoutSettingsTab = ({ settings, merchantId, updateSettings, user, abExperiments }) => {
  const [checkoutSettings, setCheckoutSettings] = useState([]);
  const [analyticSettings, setAnalyticSettings] = useState([]);
  const [giftCard, setGiftCard] = useState({});
  const [giftCardSettings, setGiftCardSettings] = useState([]);
  const [currentView, setCurrentView] = useState(CARD);

  const { nestedTabsStatus } = settings;
  const isLoading = nestedTabsStatus === FETCH_STATUS.LOADING;
  const isFormView = !currentView.includes(CARD);
  const showAllFormView = localStorage.getItem(`show_default_view-${merchantId}`) === 'true';
  const showCTA = isFormView || showAllFormView;

  //hiding the analytics settings section in the store setting tab, as a separate tab for analytics settings was added
  const isAnalyticsSettingExperimentEnabled =
    abExperiments?.magic_analytics_setting?.variables?.result === 'on';

  const isHideCodWhenDisabledExperimentEnabled =
    abExperiments?.magic_hide_cod_when_disabled?.variables?.result === 'on';

  useEffect(() => {
    const { isShopifyMagicEnabled } = user;
    if (!isShopifyMagicEnabled && SHOPIFY_CHECKOUT_SETTINGS[0].key === SHOPIFY_BUY_NOW_BUTTON) {
      SHOPIFY_CHECKOUT_SETTINGS.shift();
    }

    if (!isHideCodWhenDisabledExperimentEnabled) {
      const index = SHOPIFY_CHECKOUT_SETTINGS.findIndex(
        (setting) => setting.key === 'one_cc_hide_cod_when_disabled',
      );
      SHOPIFY_CHECKOUT_SETTINGS.splice(index, 1);
    }
  }, [user, isHideCodWhenDisabledExperimentEnabled]);

  const onSave = useCallback(() => {
    const payload = {
      platform: PLATFORMS.VALUES.SHOPIFY,
      shop_id: settings?.shop_id,
      one_click_checkout: true,
      [giftCard.key]: giftCard.value,
    };
    checkoutSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });
    analyticSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });
    giftCardSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });

    updateDefaultViewInStorage(merchantId, false);

    const {
      one_cc_buy_now_button,
      one_cc_auto_fetch_coupons,
      one_cc_international_shipping,
      one_cc_capture_billing_address,
      one_cc_ga_analytics,
      one_cc_fb_analytics,
      one_cc_gift_card,
      one_cc_multiple_gift_card,
      one_cc_gift_card_restrict_coupon,
      one_cc_buy_gift_card,
      one_cc_gift_card_cod_restrict,
    } = payload;
    analyticsTrack({
      objectName: '1ccclickedsaveplatformsettings',
      actionName: 'behav',
      screen: 'platform settings l1',
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
        gc_enabled: one_cc_gift_card,
        usage_of_multiple_gc: getGCAnalytics(one_cc_gift_card, one_cc_multiple_gift_card),
        restrict_coupon_gc_together: getGCAnalytics(
          one_cc_gift_card,
          one_cc_gift_card_restrict_coupon,
        ),
        buying_gc_using_gc: getGCAnalytics(one_cc_gift_card, one_cc_buy_gift_card),
        cod_restriction_with_gc: getGCAnalytics(one_cc_gift_card, one_cc_gift_card_cod_restrict),
      },
    });

    updateSettings(payload, false);
  }, [
    settings.shop_id,
    settings.one_click_checkout,
    checkoutSettings,
    analyticSettings,
    merchantId,
    updateSettings,
    giftCard,
    giftCardSettings,
  ]);

  const showSettings = (setting) =>
    currentView.includes(CARD) || currentView.includes(setting) || showAllFormView;

  const showFormView = (formView) => currentView === formView || showAllFormView;

  return (
    <>
      <div className="checkout-settings">
        {showSettings(CHECKOUT) && (
          <CheckoutWrapper
            checkoutSettings={checkoutSettings}
            setCheckoutSettings={setCheckoutSettings}
            setCurrentView={setCurrentView}
            showFormView={showFormView(CHECKOUT_FORM)}
            settings={settings}
          />
        )}
        {!isAnalyticsSettingExperimentEnabled && showAllFormView && <hr />}
        {!isAnalyticsSettingExperimentEnabled && showSettings(ANALYTICS) && (
          <AnalyticsWrapper
            analyticSettings={analyticSettings}
            setCurrentView={setCurrentView}
            showFormView={showFormView(ANALYTICS_FORM)}
            settings={settings}
            setAnalyticSettings={setAnalyticSettings}
          />
        )}
        {showAllFormView && <hr />}
        {showSettings(GIFT_CARD) && (
          <GCWrapper
            giftCard={giftCard}
            giftCardSettings={giftCardSettings}
            settings={settings}
            setCurrentView={setCurrentView}
            showFormView={showFormView(GC_FORM)}
            setGiftCard={setGiftCard}
            setGiftCardSettings={setGiftCardSettings}
          />
        )}
      </div>
      {showCTA && (
        <AsyncBtn.Primary
          type="button"
          isPending={isLoading}
          showLoader={isLoading}
          onClick={onSave}
          className="save-cta"
        >
          Save settings
        </AsyncBtn.Primary>
      )}
    </>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
  user: state.session.user,
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
