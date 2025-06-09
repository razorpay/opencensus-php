import React, { useState, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import { AsyncBtn } from 'common/new-ui/Button';
import CheckoutWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CheckoutWrapper';
import CouponWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/magento/CouponWrapper';

import {
  FETCH_STATUS,
  PLATFORMS,
  COUPON_FORM,
  CARD,
  COUPON,
  CHECKOUT,
  CHECKOUT_FORM,
  COUPON_SETTINGS,
  CHECKOUT_SETTINGS_CONFIG,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { analyticsTrack } from 'common/utils/analytics';
import { updateDefaultViewInStorage } from 'merchant/views/MagicCheckout/utils/storeSettings';

import { getGCAnalytics } from 'merchant/views/MagicCheckout/MagicSettings/containers/helpers';

export const MagentoCouponSettings = ({ settings, updateSettings, merchantId, user }) => {
  const [autoFetchCoupon, setAutoFetchCoupon] = useState<typeof COUPON_SETTINGS>();
  const [checkoutSettings, setCheckoutSettings] = useState<typeof CHECKOUT_SETTINGS_CONFIG>([]);

  const [currentView, setCurrentView] = useState(CARD);

  const { nestedTabsStatus } = settings;
  const isLoading = nestedTabsStatus === FETCH_STATUS.LOADING;
  const shouldShowAllFormView = localStorage.getItem(`show_default_view-${merchantId}`) === 'true';
  const isFormView = !currentView.includes(CARD);
  const shouldShowCTA = isFormView || shouldShowAllFormView;
  const showSettings = (setting) =>
    currentView.includes(CARD) || currentView.includes(setting as string) || shouldShowAllFormView;

  const handleSave = useCallback(() => {
    const platform = PLATFORMS.VALUES.MAGENTO;

    const payload = {
      platform,
      one_click_checkout: true,
      [autoFetchCoupon?.key ?? '']: autoFetchCoupon?.value,
    };

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
  }, [updateSettings, autoFetchCoupon]);

  const showFormView = (formView) => currentView === formView || shouldShowAllFormView;

  return (
    <div
      className={`${shouldShowAllFormView ? 'coupon-gc-default' : 'wooc-coupon-gc'} ${
        isFormView ? 'coupon-gc-form' : ''
      }`}
    >
      {showSettings(CHECKOUT) ? (
        <CheckoutWrapper
          checkoutSettings={checkoutSettings}
          setCheckoutSettings={setCheckoutSettings}
          setCurrentView={setCurrentView}
          showFormView={showFormView(CHECKOUT_FORM)}
          settings={settings}
          user={user}
        />
      ) : null}

      {showSettings(COUPON) ? (
        <CouponWrapper
          autoFetchCoupon={autoFetchCoupon}
          settings={settings}
          setCurrentView={setCurrentView}
          showFormView={showFormView(COUPON_FORM)}
          setAutoFetchCoupon={setAutoFetchCoupon}
        />
      ) : null}
      {shouldShowCTA ? (
        <AsyncBtn.Primary
          type="button"
          isPending={isLoading}
          showLoader={isLoading}
          onClick={handleSave}
          className="save-cta"
        >
          Save settings
        </AsyncBtn.Primary>
      ) : null}
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(MagentoCouponSettings);
