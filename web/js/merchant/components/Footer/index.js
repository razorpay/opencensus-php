import React from 'react';
import { trackLinkClick } from './ga';
import ShowWhen from 'merchant/components/ShowWhen';
import { getCustomURL } from 'merchant/components/DocsLink';

const footer_links = [
  {
    label: 'Merchant Agreement',
    link: 'https://razorpay.com/agreement/',
  },
  {
    label: 'Terms of Use',
    link: 'https://razorpay.com/terms/',
  },
  {
    label: 'Privacy Policy',
    link: 'https://razorpay.com/privacy/',
  },
];

const FooterLine = ({ user }) => {
  const currentYear = new Date().getFullYear();

  return (
    <footer class="pagefooter">
      {user.isOrgAxis && (
        <img
          src="/img/branding/powered-by-razorpay-dashboard.png"
          class="rzp-branding-logo logo-footer"
          alt="Powered by Razorpay"
        />
      )}
      © {`${user.isOrgRZP ? '2017' : '2018'}-${currentYear}`} Copyright Razorpay
      <ShowWhen
        additionalCondition={(userObj) => userObj.isOrgAllowedFunctionality('external_links')}
      >
        {' '}
        ·{' '}
        {footer_links.map((link_obj) => (
          <React.Fragment key={link_obj.label}>
            <u>
              <a
                href={getCustomURL(link_obj.link)}
                rel="noopener noreferrer"
                target="_blank"
                onClick={trackLinkClick}
              >
                {link_obj.label}
              </a>
            </u>{' '}
            ·{' '}
          </React.Fragment>
        ))}
      </ShowWhen>
    </footer>
  );
};

export default FooterLine;
