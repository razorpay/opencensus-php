import React from 'react';

interface MobilePreviewDetailsProps {
  brandColor: string;
  textColor: string;
  rzpLogo: string;
}

export const MobilePreviewDetails = ({
  brandColor,
  textColor,
  rzpLogo,
}: MobilePreviewDetailsProps): JSX.Element => {
  return (
    <div id="preview-container-mobile">
      <div id="preview-mobile-inner-container">
        <div id="preview-header-container">
          <div className="back-container">
            <i className="i-solid i-chevron-left" />
          </div>
          <div className="progress-container">
            <div className="progress-bar" />
          </div>
          <div className="progress-container" />
          <div className="progress-container" />
          <div className="progress-container" />
        </div>

        <div className="preview-heading">Where do you want to accept payments?</div>
        <div className="checklist-container">
          <div className="partner-checklist">
            <div className="partner-checkbox">
              <i className="i i-check" />
            </div>
            <span className="partner-check-details">Website</span>
          </div>
          <div className="partner-checklist">
            <div className="partner-checkbox-unchecked" />
            <span className="partner-check-details">Android app</span>
          </div>
          <div className="partner-checklist">
            <div className="partner-checkbox-unchecked" />
            <span className="partner-check-details">iOS app</span>
          </div>
          <div className="partner-checklist">
            <div className="partner-checkbox-unchecked" />
            <span className="partner-check-details">Others</span>
          </div>
        </div>

        <div className="bottom-container" id="bottom-container-details">
          <div className="button-container">
            <div className="skip-button" style={{ color: brandColor }}>
              Skip
            </div>
            <div className="save-button" style={{ backgroundColor: brandColor, color: textColor }}>
              <div className="partner-overlay" />
              Continue
            </div>
          </div>

          <div className="rzp-logo-container">
            powered by&nbsp; <img src={rzpLogo} alt="razorpay" width="60" />
          </div>
        </div>
      </div>
    </div>
  );
};
