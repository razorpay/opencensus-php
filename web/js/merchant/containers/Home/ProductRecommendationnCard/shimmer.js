import React from 'react';

const RecommendationWidgetShimmer = ({ isMtuMerchant = false }) => {
  if (isMtuMerchant) {
    return null;
  }
  return (
    <div className={`${isMtuMerchant ? 'hideShimer' : 'recommendation-shimmer '}`}>
      <div className="primary-shimmer">
        <span className="shimmer-loader primary-icon" />
        <div className="side-space">
          <span className="shimmer-loader first-cta" />
          <span className="shimmer-loader sec-cta" />
          <span className="shimmer-loader sec-cta" />
          <span className="shimmer-loader last-cta" />
        </div>
      </div>
      <div className="secondary-shimmer">
        <div className="more-content">
          <span className="shimmer-loader icon" />
          <div className="side-space">
            <span className="shimmer-loader first-cta" />
            <span className="shimmer-loader sec-cta" />
          </div>
        </div>
        <div className="more-content" style={{ marginTop: '24px' }}>
          <span className="shimmer-loader icon" />
          <div className="side-space">
            <span className="shimmer-loader first-cta" />
            <span className="shimmer-loader sec-cta" />
          </div>
        </div>
      </div>
    </div>
  );
};

export default RecommendationWidgetShimmer;
