import React from 'react';

interface DesktopPreviewProps {
  brandName: string;
  textColor: string;
  brandColor: string;
  uploadLogo?: string;
  rzpLogo: string;
}

export const DesktopPreviewDetails = ({
  brandName,
  textColor,
  brandColor,
  uploadLogo,
  rzpLogo,
}: DesktopPreviewProps): JSX.Element => {
  return (
    <div id="preview-container-desktop">
      {(uploadLogo || brandName) && (
        <div className="top-logo-container">
          {uploadLogo && uploadLogo !== '' && (
            <img src={uploadLogo} alt="logo-top" width="25" height="28" />
          )}
          {brandName && <span className="brand-name">{brandName}</span>}
        </div>
      )}
      <div className="preview-main-container" id="preview-main-container-shadow">
        <div className="content" id="preview-content-shadow">
          <div id="preview-header-container">
            <div className="back-container">
              <i className="i-solid i-chevron-left" /> <span>Platform Details</span>
            </div>
            <div className="progress-section">
              <div className="progress-container">
                <div className="progress-bar" />
              </div>
              <div className="progress-container" />
              <div className="progress-container" />
              <div className="progress-container" />
            </div>
          </div>

          <div className="checklist-container">
            <div className="checklist-header">Where do you want to accept payments?</div>
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
          <div className="bottom-container" id="bottom-container-shadow">
            <div className="skip-button" style={{ color: brandColor }}>
              Skip
            </div>
            <div className="save-button" style={{ backgroundColor: brandColor, color: textColor }}>
              <div className="partner-overlay" />
              Continue
            </div>
          </div>
        </div>
      </div>
      <div className="partner-rzp-container" id="partner-rzp-container-shadow">
        <div className="logo-and-text">
          <span className="powered-by">powered by</span>
          <img src={rzpLogo} alt="rzp" width="40" />
        </div>

        <div className="t-and-c-container">
          © 2017-2022 ݀· Merchant agreement · Terms of use · Privacy policy · Support
        </div>
      </div>
    </div>
  );
};
