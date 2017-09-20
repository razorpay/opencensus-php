import React from 'react';

import MediaCard from 'merchant/containers/Home/OnboardingCard/MediaCard';

export default ({ productName, symbol, onActivate }) => {
  return (
    <div className="feature-activation-banner">
      <MediaCard
        symbol={symbol}
        title={`Get Started with ${productName} in live mode`}
      >
        <div className="m-b">
          <div>
            You are currently in test mode. To integrate in test mode, you can
            go through the documentation.
          </div>
          <div>
            To activate {productName} in live mode, you can request for
            activation and we will get back to you in 24 hours.
          </div>
        </div>
        <button className="btn btn-default" onClick={onActivate}>
          Activate Now
        </button>
      </MediaCard>
    </div>
  );
};
