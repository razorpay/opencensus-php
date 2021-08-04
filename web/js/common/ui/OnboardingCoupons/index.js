import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import { showProductsModal } from 'merchant/reducers/home';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const OnboardingCoupons = ({ closeModal, showProductModal }) => {
  useEffect(() => {
    analyticsTrack({
      objectName: 'Limited time MTU offer popup',
      actionName: 'loaded',
      screen: 'home page',
      properties: {
        location: 'top header',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, []);

  return (
    <div className="Onboarding-coupon">
      <button type="button" class="close btn" onClick={closeModal}>
        <i class="i i-close" />
      </button>
      <div className="content">
        <div className="offer-period">Limited time offer</div>
        <div className="credit-text">
          Get free credits worth <span className="amount">2 Lakhs</span> if you accept a payment in
          the next 5 days !
        </div>
        <div className="bottom-text">
          Credits will be added to your account post your first transaction.
        </div>
        <div className="content__btn">
          <Button.Primary
            type="button"
            className="accept-payment"
            onClick={() => {
              closeModal();
              showProductModal();
              analyticsTrack({
                objectName: 'Accept Payments Limited time offer',
                actionName: 'clicked',
                screen: 'home page',
                properties: {
                  location: 'top header',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
            }}
          >
            Accept payments
          </Button.Primary>
        </div>
      </div>
    </div>
  );
};

export default connect(null, {
  showProductModal: showProductsModal,
})(OnboardingCoupons);
