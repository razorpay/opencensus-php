import isEmpty from 'lodash/isEmpty';
import {
  COUPON_CARD,
  COUPON_FORM,
  COUPON_SETTINGS,
  FETCH_STATUS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import CouponCard from 'merchant/views/MagicCheckout/MagicSettings/containers/common/CouponCard';
import CouponForm from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/CouponForm';
import { useCallback, useEffect } from 'react';

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
