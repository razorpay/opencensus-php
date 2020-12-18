import PadImage from '../PadImage.js';

function Data(brandColor) {
  const PaddedImage = PadImage(brandColor);

  return (
    <main>
      <div className="description">
        <p>
          Razorpay Payment Links is an easy way to receive payments for goods and services. Create
          and share payment links with your customers to accept payments
        </p>

        <p>
          Installing Razorpay Whatsapp Bot for Payment Links enables you to accept payments from
          your customers by creating and sharing payment links to them from Whatsapp directly
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading">
            How to use Razorpay with Whatsapp Bot for Payment Links
          </h2>
          <p>To enable Razorpay Payment Links with your Whatsapp account</p>

          <ol>
            <li>
              <p>
                <strong>Install</strong>
                <br />
                <span>Install the Whatsapp Bot for Payment Links from the Razorpay App Store</span>
              </p>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <strong>Welcome Message</strong>
                  <br />
                  <span>
                    Receive a welcome message on the mobile number associated with your Razorpay
                    Admin Account
                  </span>
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/whatsapp-bot-payment-link/2.jpeg" />
              </div>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <strong>Create Payment Link</strong>
                  <br />
                  <span>
                    To create a Payment Link,
                    <br />
                    Send Create &lt;Amount&gt; to create a Payment Link
                    <br />
                    eg: Create 100 will create a Payment Link for INR 100
                  </span>
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/whatsapp-bot-payment-link/3.jpeg" />
              </div>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <strong>Share Payment Link</strong>
                  <br />
                  <span>
                    You will receive the Payment Link from Razorpay which can shared with your
                    customers
                  </span>
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/whatsapp-bot-payment-link/4.jpeg" />
              </div>
            </li>
          </ol>
        </article>
      </div>
    </main>
  );
}

export default Data;
