import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

// i18
export const LANDING_PAGE_DESC = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: {
    link: 'https://razorpay.com/',
    logo: 'https://razorpay.com/images/logo-black.png',
    logo_height: '24px',
  },
  [ORG_CUSTOM_CODE_MAP.CURLEC]: {
    link: 'https://curlec.com/',
    logo: 'https://cdn.razorpay.com/static/assets/i18n/malaysia/curlec-light-logo.png',
    logo_height: '35px',
  },
};

export default function InvoiceLogo({ name, logo, gstin, cin, hideRazorpayDetails, org }) {
  const orgOptions = LANDING_PAGE_DESC[org.custom_code];
  return (
    <div class="row inv__branding">
      <div class="col-md-8 inv__branding--merchant">
        <div class="media">
          {logo && (
            <div class="media-left">
              <span class="inv__merchant-logo">
                <img class="media-object" src={logo} alt={name} />
              </span>
            </div>
          )}
          <div class={`media-body ${gstin || cin ? 'valign-top' : ''}`}>
            <h3 class="inv__company-name">{name}</h3>
            {gstin && (
              <div class="inv__company-tax-details">
                <span class="tax-heading">GSTIN - </span>
                {gstin}
              </div>
            )}
            {cin && (
              <div class="inv__company-tax-details">
                <span class="tax-heading">CIN - </span>
                {cin}
              </div>
            )}
          </div>
        </div>
      </div>
      {!hideRazorpayDetails && (
        <div class="col-md-4 inv__branding--rzp">
          <div class="text-right pull-right">
            <a
              class="rzp-logo"
              href={orgOptions.link}
              target="_blank"
              rel="noreferrer noopener"
              height={orgOptions.logo_height}
            >
              <img src={orgOptions.logo} alt="logo" />
            </a>
            <div class="rzp-header-branding-label">
              <div>Invoicing and payments</div>
              <div>
                powered by{' '}
                <a href={orgOptions.link} target="_blank" rel="noreferrer noopener">
                  {org.business_name}
                </a>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
