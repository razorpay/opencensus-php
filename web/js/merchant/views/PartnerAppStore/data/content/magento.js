export default (brandColor) => {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  return (
    <main>
      <div className="description">
        <p>
          No other platform gives you the power to create unique and engaging shopping experiences.
          Enjoy rich, out-of-the-box features, an unlimited ability to customize, a flexible
          headless architecture, and seamless third-party integrations. With our eCommerce
          platforms, a global ecosystem of implementation partners, and a vast marketplace of
          extensions, it’s time to bring your commerce vision to life.
        </p>

        <p>
          Marry content with commerce to customer demands for flawless brand interactions. Magento
          Commerce features are ever evolving with the consumer in mind. Own your customer
          experience, craft personalized content and promotions, and deliver a smooth path to
          purchase. Page Builder is a simple drag and drop solution to eCommerce website builders.
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <h2
            className="how-to-use-heading"
            // eslint-disable-next-line no-sequences
            style={({ marginBottom: '10px' }, brandStyles.brandBorderLeft)}
          >
            How to use Razorpay with Magento
          </h2>
          <div>
            <p>
              Integrating your Magento store with Razorpay allows you to accept payments on your
              Magento store via the Razorpay Payment Gateway. You can accept payments via debit
              card, credit card, netbanking (supports 3D Secure), UPI or through any of our
              supported wallets, without redirecting your customer away from your Magento store.
            </p>
            <p>
              The extension offers seamless integration, allowing the customer to pay on your
              website without being redirected. This allows the extension to work across all
              browsers and ensures compatibility with the latest versions of Magento.
            </p>
            <p>
              We also support the display of international currencies at the time of checkout on
              your Magento store. However, all pricing calculations and settlements are done in INR.
              To activate international payments, raise a request on our{' '}
              <a href="https://razorpay.com/support/" target="_blank" rel="noreferrer noopener">
                Support Portal
              </a>
            </p>
            <p>Currently, we support the following versions of Magento:</p>
            <ul>
              <li>
                <a
                  href="/docs/ecommerce-plugins/magento/1.x/"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Magento 1.x
                </a>{' '}
                extension
              </li>
              <li>
                <a
                  href="/docs/ecommerce-plugins/magento/2.x/"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Magento 2.x
                </a>{' '}
                extension
              </li>
            </ul>
            <p>
              Our extensions are supported on all operating systems, such as Windows, Mac OS and
              Linux.
            </p>
          </div>
        </article>
      </div>
    </main>
  );
};
