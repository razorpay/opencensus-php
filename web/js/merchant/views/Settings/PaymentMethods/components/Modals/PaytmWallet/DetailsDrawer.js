import React, { useState } from 'react';

export const DetailsDrawer = () => {
  const [showDrawer, setShowDrawer] = useState(false);
  return (
    <div className="form-container">
      <div className="details-drawer">
        <p>Where do I find Paytm API Keys details?</p>
        <a onClick={() => setShowDrawer(!showDrawer)}>{`${showDrawer ? 'Hide <' : 'Show >'}  `}</a>
      </div>
      <div className="details-side-drawer" style={{ display: showDrawer ? 'block' : 'none' }}>
        <p className="drawer-header">Where do I find these details?</p>
        <div style={{ position: 'relative' }}>
          <p className="drawer-list-item">
            1. Login to your paytm dashboard > Go to Developer Settings > API Keys
          </p>
          <img
            src="https://cdn.razorpay.com/static/assets/instrument-request/paytm-instruction-1.png"
            alt=""
          />
        </div>
        <div style={{ position: 'relative' }}>
          <p className="drawer-list-item">2. Here you will find these details</p>
          <img
            src="https://cdn.razorpay.com/static/assets/instrument-request/paytm-instruction-2.jpg"
            alt=""
          />
        </div>
      </div>
    </div>
  );
};
