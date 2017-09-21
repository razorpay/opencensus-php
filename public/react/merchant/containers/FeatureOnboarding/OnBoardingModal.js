import React from 'react';

import FeatureOnboarding from 'merchant/containers/FeatureOnboarding/OnBoarding';

export default ({ onClose, onSuccess, ...otherProps }) =>
  <div className="feature-onboarding-modal">
    <div className="feature-onboarding-content">
      <FeatureOnboarding {...otherProps} />
    </div>
    <div className="close" onClick={onClose}>
      &times;
    </div>
  </div>;
