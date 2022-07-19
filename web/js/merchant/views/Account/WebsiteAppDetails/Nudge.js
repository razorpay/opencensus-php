import React from 'react';
import { isMobileDevice } from 'merchant/components/Home/data';

function WebsiteAppDetailsNudge() {
  const isMobileResolution = isMobileDevice();

  if (!isMobileResolution) return null;

  return (
    <div className="website-app-details-container">
      <div className="nudge-container">
        <span className="website-app-info-status details-required">
          <p>UNDER VERIFICATION</p>
        </span>
        <div>Update details about your website/app</div>
        <div>
          Terms & Conditions, Privacy Policy, Contact Us, Cancellation and Refund Policy, and
          Shipping and Delivery Policy pages are required as per RBI guidelines.
        </div>
        <div>
          <button className="btn btn-primary">Update</button>
        </div>
      </div>
    </div>
  );
}

export default WebsiteAppDetailsNudge;
