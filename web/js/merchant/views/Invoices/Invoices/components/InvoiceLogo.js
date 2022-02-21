export default function InvoiceLogo({ name, logo, gstin, cin, hideRazorpayDetails }) {
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
              href="https://razorpay.com/"
              target="_blank"
              rel="noreferrer noopener"
            >
              <img src="https://razorpay.com/images/logo-black.png" alt="." />
            </a>
            <div class="rzp-header-branding-label">
              <div>Invoicing and payments</div>
              <div>
                powered by{' '}
                <a href="https://razorpay.com/" target="_blank" rel="noreferrer noopener">
                  Razorpay
                </a>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
