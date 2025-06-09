import React from 'react';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';

const CouponForm = ({ autoFetchCoupon, onToggle }) => {
  return (
    <div>
      <div className="native-coupons-container padding-16">
        <p className="font-18 font-bold checkout-headings">Coupon Settings</p>
        <div className="display-flex align-center woo-url-wrapper woo-coupon">
          <SettingsToggle setting={autoFetchCoupon} onToggle={onToggle} />
        </div>
      </div>
    </div>
  );
};

export default CouponForm;
