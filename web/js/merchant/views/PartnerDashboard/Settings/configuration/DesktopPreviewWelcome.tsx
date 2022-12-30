import React from 'react';
import { DesktopBackground } from './DesktopBackground';

interface DesktopPreviewProps {
  brandName: string;
  textColor: string;
  brandColor: string;
  uploadLogo?: string;
  rzpLogo: string;
}

export const DesktopPreviewWelcome = ({
  brandName,
  textColor,
  brandColor,
  uploadLogo,
  rzpLogo,
}: DesktopPreviewProps): JSX.Element => {
  return (
    <div id="preview-container-desktop" style={{ background: brandColor }}>
      <div className="preview-bg-img">
        <div className="partner-overlay" />
        <DesktopBackground stroke={textColor} />
        <DesktopBackground stroke={textColor} />
        <DesktopBackground stroke={textColor} />
        {window.innerWidth > 1650 && <DesktopBackground stroke={textColor} />}
      </div>
      <div className="preview-main-container">
        <div className="content">
          {(uploadLogo || brandName) && (
            <div className="logo-container">
              {uploadLogo && <img src={uploadLogo} alt="logo-desktop" width="40" height="36" />}
              {brandName && <span className="logo-brand-name">{brandName}</span>}
            </div>
          )}
          <div className="preview-color-highlight" style={{ background: brandColor }} />
          <div className="preview-main-heading">
            A simple and secure way to accept payments for your business
          </div>
          <div className="preview-sub-heading">Create your account to get started </div>
          <div className="bottom-container">
            <div className="save-button" style={{ backgroundColor: brandColor, color: textColor }}>
              <div className="partner-overlay" />
              Get Started &nbsp;
              <span>
                <i className="i i-arrow-forward" />
              </span>
            </div>
          </div>
        </div>
      </div>
      <div style={{ color: textColor }} className="partner-rzp-container">
        <span className="powered-by">powered by</span>
        <img src={rzpLogo} alt="rzp" width="62" />
      </div>
    </div>
  );
};
