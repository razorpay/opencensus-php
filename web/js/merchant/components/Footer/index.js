import { trackLinkClick } from './ga';
import { Link } from 'react-router-dom';

import { getUser } from 'merchant/store';
const user = getUser();

export default () => {
  return (
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
  );
};
