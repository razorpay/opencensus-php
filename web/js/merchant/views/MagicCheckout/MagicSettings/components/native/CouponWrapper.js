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
  setTabHeadingVisible,
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
    setTabHeadingVisible(false);
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
        <CouponCard
          isWooCommerce
          switchToEdit={switchToEdit}
          setTabHeadingVisible={setTabHeadingVisible}
        />
      )}
    </>
  );
};

export default CouponWrapper;
