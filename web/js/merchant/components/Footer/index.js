import { trackLinkClick } from './ga';
import { Link } from 'react-router-dom';
import { getUser } from 'merchant/store';

export default () => {
  const user = getUser();

  return (
    <React.Fragment>
      <img
        src="/img/branding/powered-by-razorpay-dashboard.png"
        class="rzp-branding-logo"
        alt="Powered by Razorpay"
        style={{ marginLeft: 14 }}
      />
      <footer class="pagefooter">
        © {user.isOrgRZP ? '2017' : '2018'} Copyright Razorpay ·{' '}
        <u>
          <a
            href="https://razorpay.com/agreement/"
            target="_blank"
            onClick={trackLinkClick}
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
          >
            Privacy Policy
          </a>
        </u>{' '}
        ·{' '}
        <u>
          <Link to="#request" onClick={trackLinkClick}>
            Contact Us
          </Link>
        </u>
      </footer>
    </React.Fragment>
  );
};
