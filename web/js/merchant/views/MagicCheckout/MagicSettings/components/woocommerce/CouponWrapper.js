import { useEffect, useCallback } from 'react';
import isEmpty from '@universe/utils/isEmpty';
import CouponCard from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CouponCard';
import CouponForm from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/CouponForm';
import {
  FETCH_STATUS,
  COUPON_FORM,
  COUPON_CARD,
  COUPON_SETTINGS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

const CouponWrapper = ({
  listPromotions,
  applyPromotion,
  autoFetchCoupon,
  settings,
  setCurrentView,
  showFormView,
  setAutoFetchCoupon,
}) => {
  const { one_cc_auto_fetch_coupons, nestedTabsStatus } = settings;

  useEffect(() => {
    if (listPromotions && applyPromotion && nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setCurrentView(COUPON_CARD);
    } else {
      setCurrentView(COUPON_FORM);
    }
  }, [listPromotions, applyPromotion, one_cc_auto_fetch_coupons, nestedTabsStatus]);

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

  const onToggle = useCallback((checked) => {
    setAutoFetchCoupon((prevSetting) => ({ ...prevSetting, value: checked }));
  }, []);

  return (
    <>
      {showFormView ? (
        <CouponForm
          isWooCommerce
          listPromotions={listPromotions}
          applyPromotion={applyPromotion}
          autoFetchCoupon={autoFetchCoupon}
          onToggle={onToggle}
          isFieldDisabled
        />
      ) : (
        <CouponCard isWooCommerce switchToEdit={switchToEdit} />
      )}
    </>
  );
};

export default CouponWrapper;
