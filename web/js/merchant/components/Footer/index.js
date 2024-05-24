import React from 'react';
import { trackLinkClick } from './ga';
import ShowWhen from 'merchant/components/ShowWhen';
import { getCustomURL } from 'merchant/components/DocsLink';
import { isOrgFeatureExist, ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import PoweredByRzp from 'assets/branding/powered_by_rzp.png';
import { POLICY_LINKS } from 'merchant/constants/urls';
import { useI18Service } from 'common/i18';

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

const ORG_BASED_FOOTER_LINKS = {
  [ORG_CUSTOM_CODE_MAP.CURLEC]: MALAYSIAN_FOOTER_LINKS,
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: FOOTER_LINKS,
};

const FooterLine = ({ user }) => {
  const currentYear = new Date().getFullYear();
  const { isConfigTagEnabled } = useI18Service();

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
          {FooterLinks.map((link_obj) =>
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
