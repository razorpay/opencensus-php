import React from 'react';

export const ProviderShimmer = (): JSX.Element => {
  return (
    <div className="providers-shimmer">
      {[1, 2, 3, 4].map((val) => (
        <div key={val} className="primary-shimmer col-xs-3 gateway-provider-col">
          <div className="img-holder">
            <span className="shimmer-loader gateway-icon" />
          </div>
          <div className="side-space">
            <span className="shimmer-loader first-sec" />
            <span className="shimmer-loader second-sec" />
          </div>
        </div>
      ))}
    </div>
  );
};
