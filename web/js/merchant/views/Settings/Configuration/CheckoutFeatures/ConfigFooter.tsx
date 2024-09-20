import React from 'react';

import { useI18Service } from 'common/i18';
import { getCustomURL } from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';

const ConfigFooter = () => {
  const i18 = useI18Service();

  return (
    <ShowWhen additionalCondition={() => !i18?.isConfigTagEnabled('settings.checkout_info')}>
      <div className="footer-note">
        Changes will reflect on{' '}
        <ShowWhen additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}>
          <a
            target="_blank"
            rel="noopener noreferrer"
            href={getCustomURL('https://razorpay.com/payment-gateway/')}
          >
            Checkout page
          </a>
          ,{' '}
          <a
            target="_blank"
            rel="noopener noreferrer"
            href={getCustomURL('https://razorpay.com/payment-links/')}
          >
            Payment Links
          </a>
          ,{' '}
          <a
            target="_blank"
            rel="noopener noreferrer"
            href={getCustomURL('https://razorpay.com/invoices/')}
          >
            Invoices
          </a>{' '}
          &{' '}
          <a
            target="_blank"
            rel="noopener noreferrer"
            href={getCustomURL('https://razorpay.com/payment-pages')}
          >
            Payment pages
          </a>
          {''}.
        </ShowWhen>
      </div>
    </ShowWhen>
  );
};

export default ConfigFooter;
