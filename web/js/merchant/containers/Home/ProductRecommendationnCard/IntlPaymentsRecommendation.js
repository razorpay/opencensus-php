import React, { useState, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import ShimmerWidget from './shimmer';
import { analyticsFn } from 'merchant/utils/intlPaymentsRecommendation';
import imgIntlPaymentRecommendation from 'assets/product-recommendation/intl-payment-recommendation.svg';

function sendAnalyticsEvent() {
  analyticsFn({ eventName: 'International payments recommendation cta', event: 'clicked' });
}

const IntlPaymentsRecommendation = ({ internationalSettingStatus }) => {
  const { loading, data } = internationalSettingStatus;
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    if (!loading) setVisible(data?.enableRecommendationCard);
  }, [data.enableRecommendationCard, loading]);

  const handleClose = useCallback(() => setVisible((prevState) => !prevState), []);

  if (loading) return <ShimmerWidget />;

  if (!visible) return null;

  return (
    <div className="intl-payment-recommendation">
      <div className="recommend-product">
        <div className="flex-between">
          <div className="title">
            <span>Looking to accept international payments from your</span>
            <span className="highlight"> customers </span>?
          </div>
          <button className="close-btn" onClick={handleClose}>
            &times;
          </button>
        </div>
        <div className="product-card">
          <img
            src={imgIntlPaymentRecommendation}
            alt="intl-payment-recommendation"
            height="50"
            width="50"
          />
          <div className="product-card__container">
            <div className="name">
              <span>Enable International Payments Today</span>
            </div>
            <div className="desc">
              Accept card payments from international customers with a nominal fee or link your
              PayPal account today with 0% additional charge from Razorpay.
            </div>
            <Link
              to="/payment-methods?instrument=international"
              onClick={sendAnalyticsEvent}
              className="btn-link"
            >
              <strong>View International Methods</strong>
              <i className="i i-arrow-forward" />
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(withRouter(IntlPaymentsRecommendation));
