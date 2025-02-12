import { trackLinkClick } from './ga';
import React from 'react';
import PoweredByRzp from 'assets/branding/powered_by_rzp.png';

export default ({ user }) => {
  return (
    <React.Fragment>
      {!user.isOrgRZP && (
        <img
          src={PoweredByRzp}
          className="rzp-branding-logo"
          alt="Powered by Razorpay"
          style={{ marginLeft: 14 }}
        />
      )}
      <footer className="pagefooter">
        © {user.isOrgRZP ? '2017' : '2018'} Copyright Razorpay ·{' '}
        <u>
          <a
            href="https://razorpay.com/agreement/"
            target="_blank"
            onClick={trackLinkClick}
            rel="noreferrer noopener"
          >
            Merchant Agreement
          </a>
        </u>{' '}
        ·{' '}
        <u>
          <a
            href="https://razorpay.com/terms/"
            target="_blank"
            onClick={trackLinkClick}
            rel="noreferrer noopener"
          >
            Terms of Use
          </a>
        </u>{' '}
        ·{' '}
        <u>
          <a
            href="https://razorpay.com/privacy/"
            target="_blank"
            onClick={trackLinkClick}
            rel="noreferrer noopener"
          >
            Privacy Policy
          </a>
        </u>{' '}
        ·{' '}
      </footer>
    </React.Fragment>
  );
};
