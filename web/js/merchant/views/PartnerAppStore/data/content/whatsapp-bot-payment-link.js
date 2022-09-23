import PadImage from '../PadImage';
import Img1 from 'assets/app-store/content-assets/whatsapp-bot-payment-link/whatsapp-bot-payment-link-2.png';
import Img2 from 'assets/app-store/content-assets/whatsapp-bot-payment-link/whatsapp-bot-payment-link-3.png';
import Img3 from 'assets/app-store/content-assets/whatsapp-bot-payment-link/whatsapp-bot-payment-link-4.png';

function Data(brandColor) {
  // eslint-disable-next-line babel/new-cap
  const PaddedImage = PadImage(brandColor);
  const brandStyles = {
    listBg: { backgroundColor: brandColor },
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };

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
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with Whatsapp Bot for Payment Links
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            To enable Razorpay Payment Links with your Whatsapp account
          </p>

          <ol>
            <li>
              <p>
                <div className="list-counter" style={brandStyles.listBg} />
                <strong>Install</strong>
                <br />
                <span>Install the Whatsapp Bot for Payment Links from the Razorpay App Store</span>
              </p>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <strong>Welcome Message</strong>
                  <br />
                  <span>
                    Receive a welcome message on the mobile number associated with your Razorpay
                    Admin Account
                  </span>
                </p>
                <PaddedImage
                  alt="Screenshot from whatsapp chat where Razorpay bot sent App Successfully Installed message"
                  src={Img1}
                  isWebP
                />
              </div>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
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
                <PaddedImage alt="User sent Create 100" src={Img2} isWebP />
              </div>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <strong>Share Payment Link</strong>
                  <br />
                  <span>
                    You will receive the Payment Link from Razorpay which can shared with your
                    customers
                  </span>
                </p>
                <PaddedImage
                  alt="Razorpay bot replied with Link creation success message and the payment link"
                  src={Img3}
                  isWebP
                />
              </div>
            </li>
          </ol>
        </article>
      </div>
    </main>
  );
}

export default Data;
