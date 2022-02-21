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
          One platform with all the ecommerce and point of sale features you need to start, run, and
          grow your business.
        </p>
        <p>
          Use one platform to sell products to anyone, anywhere—in person with Point of Sale and
          online through your website, social media, and online marketplaces.
        </p>
        <p>
          Take the guesswork out of marketing with built-in tools that help you create, execute, and
          analyze digital marketing campaigns.
        </p>
        <p>
          Gain the insights you need to grow—use a single dashboard to manage orders, shipping, and
          payments anywhere you go.
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <section>
            <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
              How to use Razorpay with Shopify
            </h2>
            <p style={brandStyles.brandBorderLeft}>
              To integrate your Shopify store with Razorpay:
            </p>
            <ol>
              <li>
                <div className="list-counter" style={brandStyles.listBg} />
                Sign into your{' '}
                <a href="https://www.shopify.in/" target="_blank" rel="noreferrer noopener">
                  Shopify store
                </a>
                .
              </li>
              <li>
                <div className="list-counter" style={brandStyles.listBg} />
                Go to <strong>Settings</strong> → <strong>Payments</strong> →{' '}
                <strong>Alternative Payments</strong>.
              </li>
              <li>
                <div className="list-flex">
                  <p>
                    <div className="list-counter" style={brandStyles.listBg} />
                    Select <strong>Razorpay</strong> from the drop down list.
                  </p>
                  <PaddedImage
                    alt="Alternative Payments section, with razorpay in the dropdown list"
                    src="/dist/css/assets/app-store/content-assets/shopify/shopify-1.png"
                  />
                </div>
              </li>
              <li>
                <div className="list-flex">
                  <p>
                    <div className="list-counter" style={brandStyles.listBg} />
                    Enter the <code>&lt;YOUR_KEY_ID&gt;</code> and{' '}
                    <code>&lt;YOUR_KEY_SECRET&gt;</code> generated in the previous section.
                  </p>
                  <PaddedImage
                    alt="Textbox with Key ID and Key Secret Labels"
                    src="/dist/css/assets/app-store/content-assets/shopify/shopify-2.png"
                  />
                </div>
              </li>
              <li>
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click <strong>Activate</strong>. This activates your account to use Razorpay.
                </p>
                <div>
                  <p>
                    - To use your store in test mode, replace your configured live Razorpay API key
                    secret pair with the pair generated in test mode. With this, your store payments
                    will be routed to a mocked bank page and no real money will be deducted from the
                    customer's account.
                  </p>
                  <p>
                    - You can keep the <code>Test Mode</code> checkbox turned off in Shopify
                    settings, if you see one.
                  </p>
                </div>
              </li>
            </ol>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
