import PadImage from '../PadImage';

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
          Discover the platform that gives you the freedom to create, design, manage and develop
          your web presence exactly the way you want.
        </p>

        <p>
          Design and build your own high-quality websites. Whether you’re promoting your business,
          showcasing your work, opening your store or starting a blog—you can do it all with the Wix
          website builder.
        </p>

        <p>
          Start from scratch or choose from over 500 designer-made templates to make your own
          website. With the world’s most innovative drag and drop website builder, you can customize
          or change anything. Make your site come to life with video backgrounds, scroll effects and
          animation. With the Wix Editor, you can create your own professional website that looks
          stunning.
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <h2 style={brandStyles.brandBorderLeft}>How to use Razorpay with Wix</h2>
          <ol>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Navigate to your Wix website and switch to editor mode.
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click <strong>Settings</strong> → <strong>Accept Payments</strong>.
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-1.png"
                  alt="accept payments"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click <strong>Add Payment Methods</strong>.
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-2.png"
                  alt="add payments methods"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Select <strong>Razorpay</strong> and click <strong>Add</strong>.
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-3.png"
                  alt="add razorpay"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Once added, a success message appears as shown
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-4.png"
                  alt="success message"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Razorpay appears added as a payment method. Click{' '}
                  <strong>Set up Account to Activate</strong>.
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-5.png"
                  alt="account activation"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  In the dialog box that appears, select the appropriate action
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-6.png"
                  alt="dialog box"
                />
              </div>
            </li>
            <li className="number-label-top">
              <div className="list-flex">
                <ul>
                  <li>
                    If you have not signed up for a Razorpay account, click{' '}
                    <strong>Create an Account</strong> and follow the steps mentioned on-screen.
                    Once the account has been created, return to this screen and select{' '}
                    <strong>Connect Existing Account</strong>.
                  </li>
                  <li>
                    If you already have an account, click <strong>Connect Existing Account</strong>{' '}
                    and enter your API Key and Secret.
                    <br />
                    <callout info>
                      <strong>Note</strong>:
                      <br />
                      Do not select the <strong>Enable sandbox mode</strong> option.
                    </callout>
                  </li>
                </ul>

                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-8.png"
                  alt="Connect Existing Account"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click <strong>Connect My Account</strong>. A success message appears on screen.
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-4.png"
                  alt="Connect My Account"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Razorpay now appears under <strong>Payment Methods</strong>. Ensure{' '}
                  <strong>Active on your Checkout</strong> is enabled.
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/wix-9.png"
                  alt="Active on Checkout"
                />
              </div>
            </li>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              This completes the integration on Wix Dashboard.
            </li>
          </ol>

          <div>
            <h3>Setting up Webhook</h3>
            You must integrate with Razorpay webhooks to receive notifications whenever a payment is
            made on your website. To setup webhooks:
            <ul>
              <li>
                Log into your{' '}
                <a
                  href="https://dashboard.razorpay.com/#/access/signin"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Razorpay Dashboard
                </a>{' '}
                and navigate to <strong>Settings</strong> → <strong>Webhooks</strong>.
              </li>
              <li>
                Click <strong>Setup Webhook</strong>.
              </li>
              <li>
                Enter the following details:
                <ul>
                  <li>
                    Enter the <strong>Website URL</strong> as{' '}
                    <code>https://express.razorpay.com/wix/v1/webhook-handler</code>.
                  </li>
                  <li>
                    Enter a <strong>Secret</strong> for the webhook endpoint. The secret is used for
                    validation purposes.{' '}
                    <callout info>
                      <strong>Note</strong>:
                      <br />
                      The secret that you enter here can be used to validate that the webhook is
                      from Razorpay. Do not expose the secret publicly.
                    </callout>
                  </li>
                  <li>
                    Select the following events from the list of <strong>Active Events</strong>:
                  </li>
                </ul>
                <ul>
                  <li>
                    <code>order.paid</code>
                  </li>
                  <li>
                    <code>refund.processed</code>
                  </li>
                  <li>
                    <code>payment.failed</code>
                  </li>
                </ul>
              </li>
              <li>
                Click <strong>Save</strong> to enable webhooks.
              </li>
            </ul>
          </div>
          <callout info>
            <p>
              <strong>Note</strong>: <br />
              If you are using this Razorpay account to accept payments on your Wix site, you can
              set up only one webhook on the Razorpay Dashboard.
            </p>
          </callout>
          <ol>
            <li>
              <div className="list-flex">
                <p>
                  Watch the short animation below for more details.
                  <p>This completes your integration.</p>
                </p>
                <PaddedImage
                  src="/dist/css/assets/app-store/content-assets/wix/final-webhook.gif"
                  alt="webhook"
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
