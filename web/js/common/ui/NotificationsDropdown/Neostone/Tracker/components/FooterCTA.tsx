import React from 'react';
import { getXBaseURL } from '../../common/utils';

const FooterCTA = ({ footerLabel }) => {
  return (
    <div className="footer-cta">
      <a type="button" className="Button--primary btn" target="__blank" href={`${getXBaseURL()}`}>
        {footerLabel}
      </a>
      <a
        type="button"
        className="Button--transparent btn"
        target="__blank"
        href="https://razorpay.com/docs/razorpayx/getting-started/account-types/#xpayroll-current-account-cc"
      >
        Read The Guide
      </a>
    </div>
  );
};

export default FooterCTA;
