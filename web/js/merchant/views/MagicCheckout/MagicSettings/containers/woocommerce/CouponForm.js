import { useState, useCallback, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import isEmpty from '@universe/utils/isEmpty';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import {
  FETCH_STATUS,
  PLATFORMS,
  COUPON_SETTINGS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

const CouponForm = ({
  settings: { list_promotions, apply_promotion, one_cc_auto_fetch_coupons, nestedTabsStatus },
  updateSettings,
}) => {
  const [autoFetchCoupon, setAutoFetchCoupon] = useState({});

  useEffect(() => {
    setAutoFetchCoupon((prevSettings) => {
      const tempCouponSettings = isEmpty(prevSettings) ? COUPON_SETTINGS : { ...prevSettings };
      tempCouponSettings.value = one_cc_auto_fetch_coupons;

      return tempCouponSettings;
    });
  }, [one_cc_auto_fetch_coupons]);

  const onToggle = useCallback((checked) => {
    setAutoFetchCoupon((prevSetting) => ({ ...prevSetting, value: checked }));
  }, []);

  const onSave = useCallback(() => {
    updateSettings(
      {
        platform: PLATFORMS.VALUES.WOOCOMMERCE,
        [autoFetchCoupon.key]: autoFetchCoupon.value,
      },
      false,
    );
  }, [updateSettings, autoFetchCoupon]);

  return (
    <div className="woocommerce-coupons-container padding-16">
      <div className="display-flex align-center woo-url-wrapper">
        <label className="wooc-url-label">URL for get promotions</label>
        <Input
          disabled
          required
          value={list_promotions}
          name="display_prmotions_url"
          placeholder="Enter API URL for displaying promotion"
          id="display_prmotions_url"
          className="Magic-Settings--Input"
        />
      </div>
      <div className="display-flex align-center woo-url-wrapper">
        <label className="wooc-url-label">URL for apply promotions</label>
        <Input
          disabled
          required
          value={apply_promotion}
          name="apply_promotion_url"
          placeholder=" Enter API URL for apply promotion"
          id="apply_promotion_url"
          className="Magic-Settings--Input"
        />
      </div>
      <div className="display-flex align-center woo-url-wrapper">
        <SettingsToggle setting={autoFetchCoupon} onToggle={onToggle} />
      </div>
      <AsyncBtn.Primary
        type="button"
        isPending={nestedTabsStatus === FETCH_STATUS.LOADING}
        showLoader={nestedTabsStatus === FETCH_STATUS.LOADING}
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
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CouponForm);
