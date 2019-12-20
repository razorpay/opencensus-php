import { trackLinkClick } from './ga';
import { Link } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

export default ({ user }) => {
  const currentYear = new Date().getFullYear();
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
        © {`${user.isOrgRZP ? '2017' : '2018'}-${currentYear}`} Copyright
        Razorpay
        <ShowWhen
          additionalCondition={user =>
            user.isOrgAllowedFunctionality('external_links')
          }
        >
          <React.Fragment>
            ·{' '}
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
          </React.Fragment>
        </ShowWhen>
      </footer>
    </React.Fragment>
  );
};
