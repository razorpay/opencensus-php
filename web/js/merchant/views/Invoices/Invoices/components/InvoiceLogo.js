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
    <div className="row inv__branding">
      <div className="col-md-8 inv__branding--merchant">
        <div className="media">
          {logo && (
            <div className="media-left">
              <span className="inv__merchant-logo">
                <img className="media-object" src={logo} alt={name} />
              </span>
            </div>
          )}
          <div className={`media-body ${gstin || cin ? 'valign-top' : ''}`}>
            <h3 className="inv__company-name">{name}</h3>
            {gstin && (
              <div className="inv__company-tax-details">
                <span className="tax-heading">GSTIN - </span>
                {gstin}
              </div>
            )}
            {cin && (
              <div className="inv__company-tax-details">
                <span className="tax-heading">CIN - </span>
                {cin}
              </div>
            )}
          </div>
        </div>
      </div>
      {!hideRazorpayDetails && (
        <div className="col-md-4 inv__branding--rzp">
          <div className="text-right pull-right">
            <a
              className="rzp-logo"
              href={orgOptions.link}
              target="_blank"
              rel="noreferrer noopener"
              height={orgOptions.logo_height}
            >
              <img src={orgOptions.logo} alt="logo" />
            </a>
            <div className="rzp-header-branding-label">
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
