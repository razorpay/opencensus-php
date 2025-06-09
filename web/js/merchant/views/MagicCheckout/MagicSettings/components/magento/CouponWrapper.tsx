import React, { useCallback, useEffect } from 'react';
import isEmpty from 'lodash/isEmpty';

import {
  COUPON_CARD,
  COUPON_FORM,
  COUPON_SETTINGS,
  FETCH_STATUS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

import CouponCard from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CouponCard';
import CouponForm from 'merchant/views/MagicCheckout/MagicSettings/containers/magento/CouponForm';

const CouponWrapper = ({
  autoFetchCoupon,
  settings,
  setCurrentView,
  showFormView,
  setAutoFetchCoupon,
}) => {
  const { one_cc_auto_fetch_coupons, nestedTabsStatus } = settings;

  useEffect(() => {
    const isLoading = nestedTabsStatus === FETCH_STATUS.LOADING;
    setCurrentView(isLoading ? COUPON_FORM : COUPON_CARD);
  }, [one_cc_auto_fetch_coupons, nestedTabsStatus]);

  useEffect(() => {
    setAutoFetchCoupon((prevSettings) => {
      const tempCouponSettings = isEmpty(prevSettings) ? COUPON_SETTINGS : { ...prevSettings };
      tempCouponSettings.value = one_cc_auto_fetch_coupons;

      return tempCouponSettings;
    });
  }, [one_cc_auto_fetch_coupons]);

  const switchToEdit = () => {
    setCurrentView(COUPON_FORM);
  };

  const handleToggle = useCallback((checked) => {
    setAutoFetchCoupon((prevSetting) => ({ ...prevSetting, value: checked }));
  }, []);

  return showFormView ? (
    <CouponForm autoFetchCoupon={autoFetchCoupon} onToggle={handleToggle} />
  ) : (
    <CouponCard isWooCommerce switchToEdit={switchToEdit} />
  );
};

export default CouponWrapper;
