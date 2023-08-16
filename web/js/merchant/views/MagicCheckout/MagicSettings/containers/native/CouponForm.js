import { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { isUrlLenient } from 'common/utils/validators';
import isEmpty from 'lodash/isEmpty';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { SettingsInputLabel } from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsInputLabel';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import {
  COUPON_SETTINGS,
  FETCH_STATUS,
  PLATFORMS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

const isUrlValid = (value) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return null;
};

const getInputLabel = ({ label, description }) => {
  return <SettingsInputLabel label={label}>{description}</SettingsInputLabel>;
};

const CouponForm = ({
  settings: {
    nestedTabsStatus,
    list_promotions,
    apply_promotion,
    platform,
    one_cc_auto_fetch_coupons,
  },
  updateSettings,
}) => {
  const [formValid, setFormValid] = useState(false);
  const [listPromotionsUrl, setListPromotionsUrl] = useState('');
  const [applyPromotionUrl, setApplyPromotionUrl] = useState('');
  const [autoFetchCoupon, setAutoFetchCoupon] = useState({});

  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  useEffect(() => {
    if (isUrlLenient(listPromotionsUrl) && isUrlLenient(applyPromotionUrl)) {
      setFormValid(true);
    } else {
      setFormValid(false);
    }
  }, [listPromotionsUrl, applyPromotionUrl]);

  useEffect(() => {
    if (platform === PLATFORMS.VALUES.NATIVE) {
      setListPromotionsUrl(list_promotions);
      setApplyPromotionUrl(apply_promotion);
      setAutoFetchCoupon((prevSettings) => {
        const tempCouponSettings = isEmpty(prevSettings) ? COUPON_SETTINGS : { ...prevSettings };
        tempCouponSettings.value = one_cc_auto_fetch_coupons;

        return tempCouponSettings;
      });
    }
  }, [platform, list_promotions, apply_promotion, one_cc_auto_fetch_coupons]);

  const onSave = useCallback(() => {
    updateSettings(
      {
        platform: PLATFORMS.VALUES.NATIVE,
        list_promotions: listPromotionsUrl,
        apply_promotion: applyPromotionUrl,
        one_cc_auto_fetch_coupons: autoFetchCoupon.value,
      },
      false,
    );
  }, [updateSettings, listPromotionsUrl, applyPromotionUrl, autoFetchCoupon]);

  const onToggle = useCallback((checked) => {
    setAutoFetchCoupon((prevSetting) => ({ ...prevSetting, value: checked }));
  }, []);

  return (
    <>
      <div className="native-coupon-container padding-16">
        <Input
          required
          validator={isUrlValid}
          onChange={onChange(setListPromotionsUrl)}
          value={listPromotionsUrl}
          disabled={nestedTabsStatus === FETCH_STATUS.LOADING}
          label={getInputLabel({
            label: 'URL for get promotions',
            description:
              'The API URL to return the list of promotions applicable for given order_id and customer',
          })}
          name="displayPrmotionsUrl"
          placeholder="Enter API URL for displaying promotion"
          id="displayPrmotionsUrl"
          className="display-flex"
        />
        <Input
          required
          validator={isUrlValid}
          value={applyPromotionUrl}
          onChange={onChange(setApplyPromotionUrl)}
          disabled={nestedTabsStatus === FETCH_STATUS.LOADING}
          label={getInputLabel({
            label: 'URL for apply promotions',
            description:
              'The API URL to validate the promotion code applied by the user and return the discount amount',
          })}
          name="applyPromotionUrl"
          placeholder=" Enter API URL for apply promotion"
          id="applyPromotionUrl"
          className="display-flex"
        />
        <div className="display-flex align-center">
          <SettingsToggle setting={autoFetchCoupon} onToggle={onToggle} />
        </div>
      </div>
      <AsyncBtn.Primary
        type="button"
        onClick={onSave}
        disabled={!formValid}
        isPending={nestedTabsStatus === FETCH_STATUS.LOADING}
        showLoader={nestedTabsStatus === FETCH_STATUS.LOADING}
        className="settings-cta"
      >
        Save Settings
      </AsyncBtn.Primary>
    </>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(CouponForm);
