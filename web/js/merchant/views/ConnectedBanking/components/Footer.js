import React from 'react';

const Footer = ({ title, ctaLabel, onCTAClick }) => {
  return (
    <div className="offer-page--footer">
      <div className="title">{title}</div>
      <a className="cta" onClick={onCTAClick}>
        {ctaLabel}
      </a>
    </div>
  );
};

export default Footer;
