import { useCallback } from 'react';
import Input from 'common/new-ui/Input';
import { SettingsInputLabel } from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsInputLabel';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import { isUrlLenient } from 'common/utils/validators';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const getInputLabel = ({ label, description }) => {
  return <SettingsInputLabel label={label}>{description}</SettingsInputLabel>;
};

const CouponForm = ({
  listPromotions,
  applyPromotion,
  autoFetchCoupon,
  onToggle,
  setListPromotionsURL,
  setApplyPromotionURL,
  isFieldDisabled,
  platform,
  isWooCommerce,
}) => {
  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  const isUrlValid = (value) => {
    if (!isUrlLenient(value) && platform === PLATFORMS.VALUES.NATIVE) {
      return 'Please enter a valid URL.';
    }
    return null;
  };

  return (
    <div className={`${!isWooCommerce ? 'native-' : ''}coupons-container padding-16`}>
      <p className="font-18 font-bold checkout-headings">Coupon Settings</p>
      <div className="display-flex align-center woo-url-wrapper">
        {isWooCommerce && <label className="wooc-url-label">URL for get promotions</label>}
        <Input
          validator={isUrlValid}
          onChange={onChange(setListPromotionsURL)}
          disabled={isFieldDisabled}
          required
          value={listPromotions}
          name="display_prmotions_url"
          placeholder="Enter API URL for displaying promotion"
          id="display_prmotions_url"
          className="Magic-Settings--Input"
          label={
            !isWooCommerce
              ? getInputLabel({
                  label: 'URL for get promotions',
                  description:
                    'The API URL to return the list of promotions applicable for given order_id and customer',
                })
              : ''
          }
        />
      </div>
      <div className="display-flex align-center woo-url-wrapper woo-coupon">
        {isWooCommerce && <label className="wooc-url-label">URL for apply promotions</label>}
        <Input
          validator={isUrlValid}
          onChange={onChange(setApplyPromotionURL)}
          disabled={isFieldDisabled}
          required
          value={applyPromotion}
          name="apply_promotion_url"
          placeholder=" Enter API URL for apply promotion"
          id="apply_promotion_url"
          className="Magic-Settings--Input"
          label={
            !isWooCommerce
              ? getInputLabel({
                  label: 'URL for apply promotions',
                  description:
                    'The API URL to validate the promotion code applied by the user and return the discount amount',
                })
              : ''
          }
        />
      </div>
      <div className="display-flex align-center woo-url-wrapper woo-coupon">
        <SettingsToggle setting={autoFetchCoupon} onToggle={onToggle} />
      </div>
    </div>
  );
};

export default CouponForm;
