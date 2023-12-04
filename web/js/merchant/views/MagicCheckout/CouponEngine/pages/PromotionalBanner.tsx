import React from 'react';

// assets imports
import IMG_URL from 'assets/magic_checkout/coupon-banner.png';

// ui imports
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import FeaturesTile from 'merchant/views/MagicCheckout/CouponEngine/components/FeaturesTile';

// helper imports
import { RZPFeatures } from 'merchant/helpers/data';

interface PromotionalCouponBannerProps {
  handlePromotionalBannerClose: () => void;
}

const PromotionalCouponBanner: React.FC<PromotionalCouponBannerProps> = ({
  handlePromotionalBannerClose,
}) => {
  const calloutElement = (
    <>
      <p className="caption">Key Benefits</p>
      <FeaturesTile />
    </>
  );

  const desc = (
    <p className="title-desc">
      Power your customers' shopping experience with Coupons 360 - the most powerful Coupon Creation
      and Management System.
    </p>
  );

  return (
    <OnBoardingWrapper className="CouponEngine">
      <div className="Slider">
        <Landing
          className=""
          title="Coupons 360"
          imageUrl={IMG_URL}
          desc={desc}
          callout={calloutElement}
          ctaText="Continue"
          next={handlePromotionalBannerClose}
          feature={RZPFeatures.MAGIC_COUPON_ENGINE}
          active="0"
        />
      </div>
    </OnBoardingWrapper>
  );
};

export default PromotionalCouponBanner;
