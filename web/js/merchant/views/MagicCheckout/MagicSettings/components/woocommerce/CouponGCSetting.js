import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useCallback } from 'react';
import { AsyncBtn } from 'common/new-ui/Button';
import {
  FETCH_STATUS,
  PLATFORMS,
  COUPON_FORM,
  CARD,
  COUPON,
  CHECKOUT,
  CHECKOUT_FORM,
  GC_FORM,
  GIFT_CARD,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import CouponWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/woocommerce/CouponWrapper';
import CheckoutWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CheckoutWrapper';
import GCWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/common/GCWrapper';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { analyticsTrack } from 'common/utils/analytics';
import { updateDefaultViewInStorage } from 'merchant/views/MagicCheckout/utils/storeSettings';

import { getGCAnalytics } from 'merchant/views/MagicCheckout/MagicSettings/containers/helpers';

export const CouponGCSetting = ({ settings, updateSettings, merchantId, user }) => {
  const [autoFetchCoupon, setAutoFetchCoupon] = useState({});
  const [checkoutSettings, setCheckoutSettings] = useState([]);
  const [giftCard, setGiftCard] = useState({});
  const [giftCardSettings, setGiftCardSettings] = useState([]);

  const [currentView, setCurrentView] = useState(CARD);

  const { list_promotions, apply_promotion, nestedTabsStatus } = settings;
  const isLoading = nestedTabsStatus === FETCH_STATUS.LOADING;
  const showAllFormView = window?.localStorage.getItem(`show_default_view-${merchantId}`) === 'true';
  const isFormView = !currentView.includes(CARD);
  const showCTA = isFormView || showAllFormView;
  const showSettings = (setting) =>
    currentView.includes(CARD) || currentView.includes(setting) || showAllFormView;

  const onSave = useCallback(() => {
    const platform = PLATFORMS.VALUES.WOOCOMMERCE;

    const payload = {
      platform,
      one_click_checkout: true,
      [autoFetchCoupon.key]: autoFetchCoupon.value,
      [giftCard.key]: giftCard.value,
    };

    giftCardSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });

    checkoutSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });

    const {
      one_cc_auto_fetch_coupons,
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
        auto_fetch_coupon: one_cc_auto_fetch_coupons,
        platform,
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
    updateSettings(payload, false).finally(() => {
      updateDefaultViewInStorage(merchantId, false);
    });
  }, [updateSettings, autoFetchCoupon, giftCard, giftCardSettings]);

  const showFormView = (formView) => currentView === formView || showAllFormView;

  return (
    <div
      className={`${showAllFormView ? 'coupon-gc-default' : 'wooc-coupon-gc'} ${
        isFormView ? 'coupon-gc-form' : ''
      }`}
    >
      {showSettings(CHECKOUT) && (
        <CheckoutWrapper
          checkoutSettings={checkoutSettings}
          setCheckoutSettings={setCheckoutSettings}
          setCurrentView={setCurrentView}
          showFormView={showFormView(CHECKOUT_FORM)}
          settings={settings}
          user={user}
        />
      )}

      {showSettings(COUPON) && (
        <CouponWrapper
          listPromotions={list_promotions}
          applyPromotion={apply_promotion}
          autoFetchCoupon={autoFetchCoupon}
          settings={settings}
          setCurrentView={setCurrentView}
          showFormView={showFormView(COUPON_FORM)}
          setAutoFetchCoupon={setAutoFetchCoupon}
        />
      )}

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
    </div>
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
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CouponGCSetting);
