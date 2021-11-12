import React from 'react';

const NeoStoneTrackerShimmer = () => (
  <div className="nss-tracker-shimmer">
    <div className="left-shimmer">
      <span id="razorpay-x-status" />
      <span id="bank-status" />
    </div>
    <div className="info-section">
      <div className="middle-shimmer">
        <span id="tracker-status" />
        <span id="desc-status" />
        <span id="footer-status" />
      </div>
      <div className="right-shimmer">
        <span id="bg-1-status" />
        <span id="bg-2-status" />
      </div>
    </div>
  </div>
);
export default NeoStoneTrackerShimmer;
