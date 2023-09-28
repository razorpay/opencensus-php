import React from 'react';
import { trackLinkClick } from './ga';
import ShowWhen from 'merchant/components/ShowWhen';
import { getCustomURL } from 'merchant/components/DocsLink';
import { isOrgFeatureExist, ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import PoweredByRzp from 'assets/branding/powered_by_rzp.png';
import { POLICY_LINKS } from 'merchant/constants/urls';
export const FOOTER_LINKS = [
  {
    label: 'Merchant Agreement',
    link: POLICY_LINKS.MERCHANT_AGGREMENT,
  },
  {
    label: 'Terms of Use',
    link: POLICY_LINKS.TERMS_OF_USE,
  },
  {
    label: 'Privacy Policy',
    link: POLICY_LINKS.PRIVACY_POLICY,
  },
];

export const MALAYSIAN_FOOTER_LINKS = [
  {
    label: 'Merchant Agreement',
    link: POLICY_LINKS.MERCHANT_AGGREMENT_CURLEC,
  },
  {
    label: 'Terms of Use',
    link: POLICY_LINKS.TERMS_OF_USE_CURLEC,
  },
  {
    label: 'Privacy Policy',
    link: POLICY_LINKS.PRIVACY_POLICY_CURLEC,
  },
];

const ORG_BASED_FOOTER_LINKS = {
  [ORG_CUSTOM_CODE_MAP.CURLEC]: MALAYSIAN_FOOTER_LINKS,
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: FOOTER_LINKS,
};

const FooterLine = ({ user }) => {
  const currentYear = new Date().getFullYear();

  const FooterLinks = ORG_BASED_FOOTER_LINKS[user.orgCustomCode?.toLowerCase()] || FOOTER_LINKS;

  return (
    <ShowWhen additionalCondition={() => !isOrgFeatureExist('hide_razorpay_text_link')}>
      <footer className="pagefooter">
        {user.isOrgAxis && (
          <img
            src={PoweredByRzp}
            className="rzp-branding-logo logo-footer"
            alt="Powered by Razorpay"
          />
        )}
        &#169; {`${user.isOrgRZP ? '2017' : '2018'}-${currentYear}`} Copyright Razorpay
        <ShowWhen
          additionalCondition={(userObj) =>
            userObj?.isOrgAllowedFunctionality &&
            userObj.isOrgAllowedFunctionality('external_links')
          }
        >
          {' '}
          ·{' '}
          {FooterLinks.map((link_obj) => (
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
    </ShowWhen>
  );
};

export default FooterLine;
