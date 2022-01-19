export const PineLabsPoints = () => {
  return (
    <>
      <div>
        You need to keep the following steps in mind while setting up your Pine Labs Plural account:
      </div>
      <ol>
        <li>
          Write to your Pine Labs Plural account manager to enable all the required payment methods
          and refunds. Mention that you are using Razorpay as the technology company to handle
          sensitive card data.
        </li>
        <li>
          Copy Razorpay email and we will provide supporting document from our side if required.
        </li>
        <li>
          The <b>Aggregator model should be enabled</b>, If it is not enabled then RRN and other
          attributes won't come in the callback response of card payment initialization. Razorpay
          supports only the aggregator model for Pine Labs Plural card integration.
        </li>
        <li>
          Pine Labs Plural supports only the static callback url (merchant_return_url). So you need
          to share Razorpay dynamic callback url to whitelist. These can be found below:
          <ol type="a">
            <li>
              {`Production redirect url format https://api.razorpay.com/v1/payments/<pay_payment_id>/callback/<hash>/<rzp_live_merchant_keys>`}
            </li>
            <div>
              NOTE: Once the Razorpay production url is shared, Pine Labs DBA team is required to
              make some changes in the production data base to support the RZP dynamic url.
            </div>
          </ol>
        </li>
        <li>
          To check the payment status, the INQUIRY API should be enabled for your Pine Labs Plural
          account.
        </li>
        <li>
          For an AXIS terminal configuration done by Pine Labs only Mastercard and Visa networks
          will be supported by default.
        </li>
      </ol>
    </>
  );
};
