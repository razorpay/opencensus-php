import PadImage from '../PadImage';

import Img1 from 'assets/app-store/content-assets/zoho/link-razorpay-zoho-1.png';
import Img2 from 'assets/app-store/content-assets/zoho/link-razorpay-zoho-2.png';
import Img3 from 'assets/app-store/content-assets/zoho/link-razorpay-zoho-3.png';
import Img4 from 'assets/app-store/content-assets/zoho/link-razorpay-zoho-4.png';
import Img5 from 'assets/app-store/content-assets/zoho/link-razorpay-zoho-5.png';
import Img6 from 'assets/app-store/content-assets/zoho/link-razorpay-zoho-6.png';
import ImgZoho from 'assets/app-store/content-assets/zoho/integrate-razorpay-on-zoho.gif';

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
          Zoho Commerce is an e-commerce platform designed to help you build, manage and market your
          own online store. The platform is packed with features and is based on the cloud, so you
          can manage operations from anywhere.
        </p>
        <p>
          <strong>Why Zoho Commerce?</strong>
        </p>
        <ul>
          <li>Easy and intuitive store builder with all templates free</li>
          <li>Set up and launch in minutes</li>
          <li>Integrations for shipping and payments for easy operations</li>
          <li>Marketing tools to build traffic, and quick integration with Facebook and Google</li>
          <li>
            Worldwide reach with the Zoho ecosystem - the right tools you need to grow with ease
          </li>
        </ul>
        <p>
          <a
            target="_blank"
            href="https://www.youtube.com/watch?v=GTm3MxIekOk"
            rel="noreferrer noopener"
          >
            https://www.youtube.com/watch?v=GTm3MxIekOk
          </a>
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with Zoho Commerce
          </h2>
          <p style={brandStyles.brandBorderLeft}>To integrate Razorpay with your Zoho suite:</p>
          <ol>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              <a
                href="https://razorpay.com/docs/payment-gateway/dashboard-guide/sign-up/"
                target="_blank"
                rel="noreferrer noopener"
              >
                Create and activate an account with Razorpay
              </a>
              .
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Log into your Zoho Dashboard and click <strong>Settings</strong>.
                </p>
                <PaddedImage alt="screenshot of settings icon on top right" src={Img1} />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click <strong>Integrations</strong>.
                </p>
                <PaddedImage alt="Integration button in right settings pan" src={Img2} />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Find <strong>Razorpay</strong> on the <strong>Customer Payments</strong> page and
                  click <strong>Setup Now</strong>.
                </p>
                <PaddedImage
                  alt="Red button with Setup now text on Customer Payments page"
                  src={Img3}
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  You are redirected to the Razorpay login page. Enter your credentials on this page
                  and click <strong>Login</strong>.
                </p>
                <PaddedImage
                  alt="Screenshot of razorpay login page with email and password inputs"
                  src={Img4}
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click <strong>Authorize</strong>.
                </p>
                <PaddedImage alt="Authorize button in purple" src={Img5} />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  The integration is complete, Razorpay is marked as <code>Active</code> and you can
                  start accepting payments from your customers using the Razorpay Payment Gateway.
                </p>
                <PaddedImage alt="Active text next to Razorpay logo" src={Img6} />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  {/* <div className="list-counter" style={brandStyles.listBg} /> */}
                  The short animation below shows you how to integrate your Zoho suite with
                  Razorpay.
                </p>
                <PaddedImage alt="Above steps in animation" src={ImgZoho} />
              </div>
            </li>
          </ol>
        </article>
      </div>
    </main>
  );
}

export default Data;
