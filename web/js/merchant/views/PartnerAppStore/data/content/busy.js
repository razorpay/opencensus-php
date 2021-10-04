import padImage from '../PadImage';

function Data(brandColor) {
  const PaddedImage = padImage(brandColor);
  const brandStyles = {
    listBg: { backgroundColor: brandColor },
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };

  return (
    <main>
      <div className="description">
        <p>
          BUSY Accounting Software offers accounting and inventory management with features like
          Comprehensive GST Module, Operations Management, Configurable Invoicing and more.
        </p>
        <p>
          The Razorpay patch on BUSY is Easy to set up for online collection of payments with
          Instant reconciliation. With{' '}
          <a href="https://razorpay.com/payment-links/" target="_blank" rel="noreferrer">
            Razorpay Payment Links
          </a>{' '}
          , give your customers the convenience to pay you immediately via email, SMS, etc. You can
          accept payments via credit card, debit card, Net Banking, UPI and more with giving offers
          and discounts to your customers from time to time.
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            Integrate Razorpay on BUSY’s dashboard to send payment links to your customers in just 6
            simple steps:
          </h2>
          {/* <p style={brandStyles.brandBorderLeft}>
            To enable Razorpay Payment Links with your Whatsapp account
          </p> */}

          <ol>
            {/* step 1 */}
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <div>
                    Install Razorpay’s Software Patch and Run it
                    <br />
                    Go to <b>Add On → Collection Engine → Registration</b> to begin.
                  </div>
                </p>
                <PaddedImage alt="" src="/dist/css/assets/app-store/content-assets/busy/1.png" />
              </div>
            </li>

            {/* step 2 */}
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <span>Register with Razorpay to get your Merchant ID.</span>
                </p>
                <PaddedImage alt="" src="/dist/css/assets/app-store/content-assets/busy/2.png" />
              </div>
            </li>
            {/* step 3 */}
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <span>
                    Add in customer’s account details and click on <b>Install Custom Validation</b>{' '}
                    to install the add-on in your dashboard.
                  </span>
                </p>
                <PaddedImage alt="" src="/dist/css/assets/app-store/content-assets/busy/3.png" />
              </div>
            </li>
            {/* step 4 */}
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <span>
                    Fill in account details in <b>Transaction</b> to enable payment links. Use{' '}
                    <b>Reports</b> to send payment reminders’.
                  </span>
                </p>
                <PaddedImage alt="" src="/dist/css/assets/app-store/content-assets/busy/4.png" />
              </div>
            </li>
            {/* step 5 -1*/}
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <div>
                    <strong>Start Sending Payment Links to Your Customers</strong>
                    <br />
                    <br />
                    Go to <b>Transaction → Sale → Add</b> (to add Sale Voucher details){' '}
                    <b>
                      → OK → Do you want to send Payment Link? → ‘Yes’→ Sending Payment Link → Sent
                    </b>
                    {''}.
                  </div>
                </p>
                <PaddedImage alt="" src="/dist/css/assets/app-store/content-assets/busy/5-1.png" />
              </div>
            </li>
            {/* step 5 -2*/}
            <li className="number-label-top">
              <div className="list-flex">
                Wait to see &nbsp;<b> Link sent successfully</b>
                <PaddedImage alt="" src="/dist/css/assets/app-store/content-assets/busy/5-2.png" />
              </div>
            </li>
            {/* step 6 */}
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <span>
                    Go to{' '}
                    <b>
                      Add On → Collection Engine → Get Data → Razorpay Payments → Verify Payments
                    </b>{' '}
                    to send receipts to your customers after receiving payments
                  </span>
                </p>
                <PaddedImage alt="" src="/dist/css/assets/app-store/content-assets/busy/6.png" />
              </div>
            </li>
          </ol>
        </article>
      </div>
    </main>
  );
}

export default Data;
