import PadImage from '../PadImage.js';

function Data(brandColor) {
  const PaddedImage = PadImage(brandColor);

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
          <a target="_blank" href="https://www.youtube.com/watch?v=GTm3MxIekOk">
            https://www.youtube.com/watch?v=GTm3MxIekOk
          </a>
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading">How to use Razorpay with Zoho Commerce</h2>
          <p>To integrate Razorpay with your Zoho suite:</p>
          <ol>
            <li>
              <a
                href="https://razorpay.com/docs/payment-gateway/dashboard-guide/sign-up/"
                target="_blank"
              >
                Create and activate an account with Razorpay
              </a>
              .
            </li>
            <li>
              <div className="list-flex">
                <p>
                  Log into your Zoho Dashboard and click <strong>Settings</strong>.
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/zoho/link-razorpay-zoho-1.png" />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  Click <strong>Integrations</strong>.
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/zoho/link-razorpay-zoho-2.png" />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  Find <strong>Razorpay</strong> on the <strong>Customer Payments</strong> page and
                  click <strong>Setup Now</strong>.
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/zoho/link-razorpay-zoho-3.png" />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  You are redirected to the Razorpay login page. Enter your credentials on this page
                  and click <strong>Login</strong>.
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/zoho/link-razorpay-zoho-4.png" />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  Click <strong>Authorize</strong>.
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/zoho/link-razorpay-zoho-5.png" />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  The integration is complete, Razorpay is marked as <code>Active</code> and you can
                  start accepting payments from your customers using the Razorpay Payment Gateway.
                </p>
                <PaddedImage src="/dist/css/assets/app-store/content-assets/zoho/link-razorpay-zoho-6.png" />
              </div>
            </li>
          </ol>
          <div className="pad-y-1 text-center">
            <p>
              The short animation below shows you how to integrate your Zoho suite with Razorpay.
            </p>
            <PaddedImage src="/dist/css/assets/app-store/content-assets/zoho/integrate-razorpay-on-zoho.gif" />
          </div>
        </article>
      </div>
    </main>
  );
}

export default Data;
