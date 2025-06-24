import React from 'react';
import PoweredByRzp from 'assets/branding/powered_by_rzp.png';

import { useI18Service } from 'common/i18';
import { getCustomURL } from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import { POLICY_LINKS } from 'merchant/constants/urls';
import { isOrgFeatureExist } from 'merchant/models/User';

import { trackLinkClick } from './ga';

export const LEGAL_DOCS_NAMES = {
  TERMS_OF_USE: 'Terms of Use',
  PRIVACY_POLICY: 'Privacy Policy',
  MERCHANT_AGREEMENT: 'Merchant Agreement',
};

export const FOOTER_LINKS = [
  {
    label: LEGAL_DOCS_NAMES.TERMS_OF_USE,
    link: POLICY_LINKS.TERMS_OF_USE,
    key: 'terms_of_use',
  },
  {
    label: LEGAL_DOCS_NAMES.PRIVACY_POLICY,
    link: POLICY_LINKS.PRIVACY_POLICY,
    key: 'privacy_policy',
  },
];

export const MALAYSIAN_FOOTER_LINKS = [
  {
    label: LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT,
    link: POLICY_LINKS.MERCHANT_AGGREMENT_CURLEC,
    key: 'merchant_agreement',
  },
  {
    label: LEGAL_DOCS_NAMES.TERMS_OF_USE,
    link: POLICY_LINKS.TERMS_OF_USE_CURLEC,
    key: 'terms_of_use',
  },
  {
    label: LEGAL_DOCS_NAMES.PRIVACY_POLICY,
    link: POLICY_LINKS.PRIVACY_POLICY_CURLEC,
    key: 'privacy_policy',
  },
];

/*
  Return the legal footer links "Merchant Agreement, Terms, and Privacy Policy—based" on the country code. For India, or when the merchant country code is unavailable (e.g., in some cases on the Admin dashboard), the default footer links will be provided.
*/
export const getURLsByCountry = (_countryCode) => {
  // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
  if (_countryCode === 'IN' || !_countryCode) return FOOTER_LINKS;

  // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
  if (_countryCode === 'MY') {
    return MALAYSIAN_FOOTER_LINKS;
  }

  const countryCode = _countryCode.toLowerCase();
  return [
    {
      label: LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT,
      link: `https://razorpay.com/${countryCode}/agreement/`,
      key: 'merchant_agreement',
    },
    {
      label: LEGAL_DOCS_NAMES.TERMS_OF_USE,
      link: `https://razorpay.com/${countryCode}/tnc/`,
      key: 'terms_of_use',
    },
    {
      label: LEGAL_DOCS_NAMES.PRIVACY_POLICY,
      link: `https://razorpay.com/${countryCode}/privacy/`,
      key: 'privacy_policy',
    },
  ];
};

const FooterLine = ({ user }) => {
  const currentYear = new Date().getFullYear();
  const { isConfigTagEnabled } = useI18Service();

  const footerLinks = getURLsByCountry(user.merchant.country_code);
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
          {footerLinks.map((link_obj) =>
            !isConfigTagEnabled(`merchant_agreements.${link_obj.key}`) ? (
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
            ) : null,
          )}
        </ShowWhen>
      </footer>
    </ShowWhen>
  );
};

export default FooterLine;
