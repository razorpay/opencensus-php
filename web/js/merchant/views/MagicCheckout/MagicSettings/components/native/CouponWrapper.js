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
  setListPromotionsURL,
  setApplyPromotionURL,
}) => {
  const {
    list_promotions,
    apply_promotion,
    one_cc_auto_fetch_coupons,
    nestedTabsStatus,
    platform,
  } = settings;
  const isLoading = nestedTabsStatus === FETCH_STATUS.LOADING;

  useEffect(() => {
    if (list_promotions && apply_promotion && nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setCurrentView(COUPON_CARD);
    } else {
      setCurrentView(COUPON_FORM);
    }
  }, [list_promotions, apply_promotion, one_cc_auto_fetch_coupons]);

  useEffect(() => {
    setListPromotionsURL(list_promotions);
    setApplyPromotionURL(apply_promotion);
    setAutoFetchCoupon((prevSettings) => {
      const tempCouponSettings = isEmpty(prevSettings) ? COUPON_SETTINGS : { ...prevSettings };
      tempCouponSettings.value = one_cc_auto_fetch_coupons;

      return tempCouponSettings;
    });
  }, [list_promotions, apply_promotion, one_cc_auto_fetch_coupons]);

  const switchToEdit = useCallback(() => {
    setCurrentView(COUPON_FORM);
  }, []);

  const onToggle = useCallback((checked) => {
    setAutoFetchCoupon((prevSetting) => ({ ...prevSetting, value: checked }));
  }, []);

  return (
    <>
      {showFormView ? (
        <CouponForm
          listPromotions={listPromotions}
          applyPromotion={applyPromotion}
          autoFetchCoupon={autoFetchCoupon}
          onToggle={onToggle}
          setListPromotionsURL={setListPromotionsURL}
          setApplyPromotionURL={setApplyPromotionURL}
          isFieldDisabled={isLoading}
          platform={platform}
        />
      ) : (
        <CouponCard isWooCommerce switchToEdit={switchToEdit} />
      )}
    </>
  );
};

export default CouponWrapper;
