export default ({ name, logo }) => {
  return (
    <div class="row inv__branding">
      <div class="col-md-6">
        <div class="media">
          {logo &&
            <div class="media-left">
              <a class="merchant-logo">
                <img class="media-object" src={logo} alt="." />
              </a>
            </div>}
          <div class="media-body">
            <h3>{name}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-6 inv__branding--rzp">
        <div class="text-right pull-right">
          <a class="rzp-logo" href="https://razorpay.com/" target="_blank">
            <img src="https://razorpay.com/images/logo-black.png" alt="." />
          </a>
          <div class="rzp-header-branding-label">
            <div>Invoicing and payments</div>
            <div>
              powered by
              {' '}
              <a href="https://razorpay.com/" target="_blank">Razorpay</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
