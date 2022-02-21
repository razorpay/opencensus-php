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
        <p>A flexible, open-source eCommerce platform. Built on WordPress.</p>

        <p>
          Whether you’re launching a business, taking an existing brick and mortar store online, or
          designing sites for clients, get started quickly and build exactly the store you want.
        </p>

        <p>
          The first decisions you need to make when setting up a store are about homepage design,
          menus, site structure, and payment and shipping options. If you have a WordPress site,
          adding WooCommerce takes just minutes!
        </p>

        <p>
          Built on WordPress, the WooCommerce dashboard is a familiar interface for store managers
          to update products and fulfill orders. Save time with automated tax calculations, live
          shipping rates from leading carriers, options to print labels at home, and the mobile app
          for iOS and Android.
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <h2 style={brandStyles.brandBorderLeft}>How to use Razorpay with WooCommerce</h2>
          <ul>
            <li>
              <a href="#step-1-install-plugin">Install Plugin</a>.
            </li>
            <li>
              <a href="#step-2-configure-woocommerce">Configure WooCommerce</a>.
            </li>
            <li>
              <a href="#step-3-set-up-webhooks">Set Up Webhooks</a>.
            </li>
            <li>
              <a href="#step-4-accept-live-payments">Accept Live Payments</a>.
            </li>
          </ul>
          <section id="step-1-install-plugin">
            <h3>Step 1: Install Plugin</h3>
            <p>
              There are two methods to install the Razorpay WooCommerce plugin:
              <ul>
                <li>Download the plugin from WordPress plugin directory</li>
                <li>
                  <strong>OR</strong> Install plugin manually
                </li>
              </ul>
            </p>
            <h4>Install via the WordPress Plugin Directory</h4>
            <ol>
              <li>
                <a
                  href="https://wordpress.org/plugins/woo-razorpay/"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Download the plugin
                </a>{' '}
                and install it from the WordPress Plugin Directory.
              </li>
            </ol>
            <h4>Manual Installation</h4>
            <ol>
              <li>
                Download the{' '}
                <a
                  href="https://github.com/razorpay/razorpay-woocommerce/releases/latest"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  latest Source code zip file
                </a>{' '}
                from the Releases section in GitHub.
              </li>
              <li>
                Unzip and upload contents of the extension to your
                <code>/wp-content/plugins/</code> directory.
              </li>
            </ol>
          </section>
          <section id="step-2-configure-woocommerce">
            <h3>Step 2: Configure WooCommerce</h3>
            <ol>
              <li>
                Log into your{' '}
                <a href="https://wordpress.com/log-in" target="_blank" rel="noreferrer noopener">
                  WordPress account
                </a>{' '}
                and activate the Razorpay plugin in the <strong>WordPress Plugin Manager</strong>.
              </li>
              <li>
                Log into your{' '}
                <a href="https://woocommerce.com/" target="_blank" rel="noreferrer noopener">
                  WooCommerce account
                </a>{' '}
                , navigate to <strong>Settings</strong> and click the{' '}
                <strong>Checkout/Payment Gateways</strong> tab.
              </li>
              <li>
                Click <strong>Razorpay</strong> to edit the settings.
              </li>
              <li>
                Enable the Payment Method, name it Credit Card / Debit Card / Internet Banking (This
                is shown on the Payment page your customer sees.).
              </li>
              <li>
                Add in your <code>&lt;key_id&gt;</code> and
                <code>&lt;key_secret&gt;</code> generated from the Razorpay Dashboard.
              </li>
              <li>
                Set the <strong>Payment Action</strong> to <strong>Authorize and Capture</strong> to
                auto-capture payments. If you want to capture payments manually from the Dashboard
                after manual verification, set the Payment Method to <strong>Authorize</strong>.
              </li>
            </ol>
          </section>
          <section id="step-3-set-up-webhooks">
            <h3>Step 3: Set Up Webhooks</h3>
            <p>
              Webhooks are triggered when certain events occur. Subscribe to webhook events to
              receive notification (in the form of a webhook payload) when these events occur.
              Setting up webhooks makes your integration more robust, and guards again issues
              arising from poor connectivity. The webhook URL is available on the plugin's settings
              page. You must copy it from there and use it to set up webhook on the Razorpay
              Dashboard.
            </p>
            <p>You can set up multiple URLs to receive webhook notifications.</p>
            <p>To setup webhooks:</p>
            <ol>
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
                <div className="list-flex">
                  <p>
                    Click <strong>+ Add New Webhook</strong>.
                  </p>
                  <PaddedImage
                    alt="Add new webhook button on top right of the screen"
                    src="/dist/css/assets/app-store/content-assets/woocommerce/webhook-creation-1.png"
                  />
                </div>
              </li>
              <li className="number-label-top">
                <div className="list-flex">
                  <div>
                    In the <strong>Webhook Setup</strong> modal:
                    <ul>
                      <li>
                        Enter the <strong>URL</strong> where you want to receive the webhook payload
                        when an event is triggered. We recommended using an HTTPS URL.{' '}
                        <callout info>
                          <strong>Note</strong>: <br />
                          Webhooks can only be delivered to public URLs. If you attempt to save a
                          localhost endpoint as part of a webhook set-up, you will notice an error.
                          Please refer to the{' '}
                          <a
                            href="/docs/webhooks/test/#on-an-application-running-on-localhost"
                            target="_blank"
                            rel="noreferrer noopener"
                          >
                            test webhooks
                          </a>{' '}
                          section for alternatives to localhost.
                        </callout>
                      </li>
                      <li>
                        Enter a <strong>Secret</strong> for the webhook endpoint. The secret is used
                        to validate that the webhook is from Razorpay. Do not expose the secret
                        publicly.{' '}
                        <a href="/docs/webhooks/#validation">Learn more about Webhook Secret.</a>
                      </li>
                      <li>
                        In the <strong>Alert Email</strong> field, enter the email address to which
                        notifications must be sent in case of webhook failure.
                      </li>
                      <li>
                        Select the required events from the list of <strong>Active Events</strong>.{' '}
                        <a
                          href="/docs/webhooks/webhook-payloads/"
                          target="_blank"
                          rel="noreferrer noopener"
                        >
                          Sample payloads for all events are available
                        </a>
                        .
                      </li>
                    </ul>
                  </div>
                  <PaddedImage
                    alt="Webhook setup page"
                    src="/dist/css/assets/app-store/content-assets/woocommerce/webhook-creation-2.png"
                  />
                </div>
              </li>
              <li>
                Click <strong>Create Webhook</strong>.
              </li>
              <li>
                <div className="list-flex">
                  <p>
                    Once created, it appears on the list of webhooks: <br />
                  </p>
                  <PaddedImage
                    alt="Table of webhooks with created webhook listed on it"
                    src="/dist/css/assets/app-store/content-assets/woocommerce/webhooks-list.png"
                  />
                </div>
              </li>
              <li>
                <div className="list-flex">
                  <p>Watch the short animation below for more details.</p>
                  <PaddedImage
                    alt="Animations of above steps"
                    src="/dist/css/assets/app-store/content-assets/woocommerce/webhook-creation.gif"
                  />
                </div>
              </li>
            </ol>

            <h4>List of Events to Subscribe</h4>
            <p>You must subscribe to the following events:</p>
            <div style={{ overflowY: 'auto', marginBottom: '70px' }}>
              <table>
                <tbody>
                  <tr>
                    <th>
                      <p>Plugin</p>
                    </th>
                    <th>
                      <p>Webhook Events Supported</p>
                    </th>
                  </tr>
                  <tr>
                    <td>
                      <p>WooCommerce</p>
                    </td>
                    <td>
                      <p>
                        <code>payment.authorized</code> and <code>payment.failed</code>
                      </p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
          <section id="step-4-accept-live-payments">
            <h3>Step 4: Accept Live Payments</h3>
            <p>After testing your WooCommerce store, when you are ready to accept live payments:</p>
            <ol>
              <li>
                <div className="list-flex">
                  <p>
                    Generate the &lt;key_id&gt; and &lt;key_secret&gt; in the{' '}
                    <strong>Live mode</strong> on your Razorpay Dashboard.
                  </p>
                  <PaddedImage
                    alt="Animations of clicking buttons in following format: Settings then API Keys then Generate Live Keys"
                    src="/dist/css/assets/app-store/content-assets/woocommerce/generate-api-keys.gif"
                  />
                </div>
              </li>
              <li>
                Enter the Live mode <code>&lt;key_id&gt;</code> and
                <code>&lt;key_secret&gt;</code> in your WooCommerce store.
              </li>
            </ol>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
