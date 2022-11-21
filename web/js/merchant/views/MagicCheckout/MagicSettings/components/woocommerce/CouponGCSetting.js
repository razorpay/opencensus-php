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
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import CouponWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/woocommerce/CouponWrapper';
import CheckoutWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CheckoutWrapper';
import {
  updateMagicSettings,
  setTabHeadingVisible,
} from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { analyticsTrack } from 'common/utils/analytics';
import { updateDefaultViewInStorage } from 'merchant/views/MagicCheckout/utils/storeSettings';

export const CouponGCSetting = ({ settings, updateSettings, setTabHeadingVisible, merchantId }) => {
  const [autoFetchCoupon, setAutoFetchCoupon] = useState({});
  const [checkoutSettings, setCheckoutSettings] = useState([]);

  const [currentView, setCurrentView] = useState(CARD);

  const { list_promotions, apply_promotion, nestedTabsStatus } = settings;
  const isLoading = nestedTabsStatus === FETCH_STATUS.LOADING;
  const showAllFormView = localStorage.getItem(`show_default_view-${merchantId}`) === 'true';
  const isFormView = !currentView.includes(CARD);
  const showCTA = isFormView || showAllFormView;
  const showSettings = (setting) =>
    currentView.includes(CARD) || currentView.includes(setting) || showAllFormView;

  const onSave = useCallback(() => {
    const platform = PLATFORMS.VALUES.WOOCOMMERCE;
    const payload = {
      platform,
      [autoFetchCoupon.key]: autoFetchCoupon.value,
    };
    checkoutSettings.forEach((setting) => {
      payload[setting.key] = setting.value;
    });

    updateDefaultViewInStorage(merchantId, false);

    const { one_cc_auto_fetch_coupons } = payload;

    analyticsTrack({
      objectName: '1ccclickedsaveplatformsettings',
      actionName: 'behav',
      screen: 'platform settings l1',
      properties: {
        auto_fetch_coupon: one_cc_auto_fetch_coupons,
        platform,
        merchant_id: merchantId,
      },
    });
    updateSettings(payload, false);
  }, [updateSettings, autoFetchCoupon]);

  const showFormView = (formView) => currentView === formView || showAllFormView;

  return (
    <div className={`wooc-coupon-gc ${isFormView ? 'coupon-gc-form' : ''}`}>
      {showSettings(CHECKOUT) && (
        <CheckoutWrapper
          checkoutSettings={checkoutSettings}
          setCheckoutSettings={setCheckoutSettings}
          setCurrentView={setCurrentView}
          showFormView={showFormView(CHECKOUT_FORM)}
          settings={settings}
        />
      )}
      <hr />
      {showSettings(COUPON) && (
        <CouponWrapper
          listPromotions={list_promotions}
          applyPromotion={apply_promotion}
          autoFetchCoupon={autoFetchCoupon}
          settings={settings}
          setCurrentView={setCurrentView}
          setTabHeadingVisible={setTabHeadingVisible}
          showFormView={showFormView(COUPON_FORM)}
          setAutoFetchCoupon={setAutoFetchCoupon}
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
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
      setTabHeadingVisible,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CouponGCSetting);
