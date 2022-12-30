import React from 'react';
import { BackgroundImage } from './BackgroundImage';

interface MobilePreviewWelcomeProps {
  brandColor: string;
  textColor: string;
  uploadLogo?: string;
  brandName: string;
}

export const MobilePreviewWelcome = ({
  brandColor,
  textColor,
  uploadLogo,
  brandName,
}: MobilePreviewWelcomeProps): JSX.Element => {
  return (
    <div
      id="preview-container-mobile"
      style={{
        backgroundColor: brandColor,
      }}
    >
      <div className="preview-bg-img">
        <div className="partner-overlay" />
        <BackgroundImage stroke={textColor} />
      </div>
      <div className="preview-main-container">
        {(uploadLogo || brandName) && (
          <div className="logo-container">
            {uploadLogo && <img src={uploadLogo} alt="logo-mobile" width="40" height="36" />}
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
  );
};
