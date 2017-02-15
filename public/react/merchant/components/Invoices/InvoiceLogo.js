export default ({ name, logo }) => {
  return (
    <div class='row inv__headerbranding'>
      <div class='col-md-6'>
        <div class='media'>
          {
            logo &&
              <div class='media-left'>
                <img class='merchant-logo' src={logo} alt='.' />
              </div>
          }
          <div class='media-body'>
            <h3>{name}</h3>
          </div>
        </div>
      </div>
      <div class='col-md-6'>
        <div class='text-right pull-right'>
          <a class='rzp-logo' href='https://razorpay.com/' target='_blank'>
            <img src='https://razorpay.com/images/logo-black.png' alt='.' />
          </a>
          <div class='rzp-header-branding-label'>
            <div>Invoicing and payments</div>
            <div>powered by <a href='https://razorpay.com/'>Razorpay</a></div>
          </div>
        </div>
      </div>
    </div>
  )
}
