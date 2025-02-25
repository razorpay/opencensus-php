import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useCallback, useEffect } from 'react';
import { AsyncBtn } from 'common/new-ui/Button';
import CouponWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/native/CouponWrapper';
import CheckoutWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CheckoutWrapper';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { isUrlLenient } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';
import {
  FETCH_STATUS,
  PLATFORMS,
  COUPON_FORM,
  CARD,
  COUPON,
  CHECKOUT,
  CHECKOUT_FORM,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { updateDefaultViewInStorage } from 'merchant/views/MagicCheckout/utils/storeSettings';

export const CheckoutSetting = ({ settings, updateSettings, merchantId }) => {
  const [listPromURL, setListPromURL] = useState('');
  const [applyPromURL, setApplyPromURL] = useState('');
  const [autoFetchCoupon, setAutoFetchCoupon] = useState({});
  const [checkoutSettings, setCheckoutSettings] = useState([]);

  const [currentView, setCurrentView] = useState(CARD);
  const [formValid, setFormValid] = useState(false);

  const { nestedTabsStatus } = settings;
  const isLoading = nestedTabsStatus === FETCH_STATUS.LOADING;
  const showAllFormView = window?.localStorage.getItem(`show_default_view-${merchantId}`) === 'true';
  const isFormView = !currentView.includes(CARD);
  const showCTA = isFormView || showAllFormView;
  const showSettings = (setting) =>
    currentView.includes(CARD) || currentView.includes(setting) || showAllFormView;

  const onSave = useCallback(() => {
    const platform = PLATFORMS.VALUES.NATIVE;
    const payload = {
      platform,
      list_promotions: listPromURL,
      apply_promotion: applyPromURL,
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
  }, [
    listPromURL,
    applyPromURL,
    autoFetchCoupon.key,
    autoFetchCoupon.value,
    checkoutSettings,
    merchantId,
    updateSettings,
  ]);

  const showFormView = (formView) => currentView === formView || showAllFormView;

  useEffect(() => {
    if (isUrlLenient(listPromURL) && isUrlLenient(applyPromURL)) {
      setFormValid(true);
    } else {
      setFormValid(false);
    }
  }, [listPromURL, applyPromURL]);

  return (
    <div className={`native-checkout${!showAllFormView && !isFormView ? ' native-card' : ''}`}>
      {showSettings(CHECKOUT) && (
        <CheckoutWrapper
          checkoutSettings={checkoutSettings}
          setCheckoutSettings={setCheckoutSettings}
          setCurrentView={setCurrentView}
          showFormView={showFormView(CHECKOUT_FORM)}
          settings={settings}
          extraClass={showAllFormView ? 'form-view' : ''}
        />
      )}
      <hr />
      {showSettings(COUPON) && (
        <CouponWrapper
          listPromotions={listPromURL}
          applyPromotion={applyPromURL}
          autoFetchCoupon={autoFetchCoupon}
          settings={settings}
          setCurrentView={setCurrentView}
          showFormView={showFormView(COUPON_FORM)}
          setListPromotionsURL={setListPromURL}
          setApplyPromotionURL={setApplyPromURL}
          setAutoFetchCoupon={setAutoFetchCoupon}
        />
      )}
      {showCTA && (
        <AsyncBtn.Primary
          type="button"
          disabled={!formValid}
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
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CheckoutSetting);
