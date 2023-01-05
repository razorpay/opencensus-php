import React from 'react';

const InfoBanner = ({ text }) => {
  return (
    <div className="b2b-info-banner">
      <i className="i i-info-outline" />
      <div className="b2b-info-banner-content">{text}</div>
    </div>
  );
};

export default InfoBanner;
