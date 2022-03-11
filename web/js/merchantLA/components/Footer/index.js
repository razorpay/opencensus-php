import { trackLinkClick } from './ga';
import React from 'react';

export default ({ user }) => {
  return (
    <React.Fragment>
      {!user.isOrgRZP && (
        <img
          src="/img/branding/powered-by-razorpay-dashboard.png"
          class="rzp-branding-logo"
          alt="Powered by Razorpay"
          style={{ marginLeft: 14 }}
        />
      )}
      <footer class="pagefooter">
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
