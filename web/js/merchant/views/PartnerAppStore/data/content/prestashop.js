// import PadImage from '../PadImage.js';

function Data(brandColor) {
  // const PaddedImage = PadImage(brandColor);
  const brandStyles = {
    listBg: { backgroundColor: brandColor },
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };

  return (
    <main>
      <div className="description">
        <p>
          PrestaShop is an eCommerce website builder to create and manage your online business. You
          can launch your online store right now and start to sell online!
        </p>

        <p>
          PrestaShop is an efficient and innovative e-commerce solution with all the features you
          need to create an online store and grow your business
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with PrestaShop
          </h2>
          <ol>
            <li className="number-label-top">
              <div className="list-counter" style={brandStyles.listBg} />
              Download the Source code zip file of the required version of the plugin from the
              Releases section in GitHub.
              <ul>
                <li>
                  For PrestaShop 1.6, download releases tagged 1.x.y. The latest release for
                  PrestaShop 1.6 is{' '}
                  <a
                    href="https://github.com/razorpay/razorpay-prestashop/releases/download/1.3.1/razorpay.zip"
                    target="_blank"
                    rel="noreferrer noopener"
                  >
                    version 1.3.1
                  </a>
                  .
                </li>
                <li>
                  For PrestaShop 1.7, download releases tagged 1.x.y. The latest release for
                  PrestaShop 1.7 is{' '}
                  <a
                    href="https://github.com/razorpay/razorpay-prestashop/releases/tag/2.1.0"
                    target="_blank"
                    rel="noreferrer noopener"
                  >
                    version 2.1.0
                  </a>
                  .
                </li>
              </ul>
            </li>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Log into{' '}
              <a href="https://addons.prestashop.com/en/" target="_blank" rel="noreferrer noopener">
                PrestaShop account
              </a>
              .
            </li>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Navigate to the <strong>Modules</strong> tab and click{' '}
              <strong>Add a New Module</strong>.
            </li>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Click <strong>Browse</strong> to open the dialogue box enabling you to search your
              computer. Select the zip file that you have downloaded and click <strong>OK</strong>.
            </li>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Click <strong>Upload this Module</strong>.
            </li>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Click <strong>Install</strong> to install the module.
            </li>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Click <strong>Configure</strong> to configure the module.
            </li>
          </ol>
          <callout warn>
            <p>
              <strong>Note</strong>:
              <br />
              If the store is open while the module has not been fully configured, it might be a
              good idea to deactivate it, by clicking on the green check. Once the module is
              configured, click on the red X to reactivate it.
            </p>{' '}
          </callout>
          <callout info>
            <p>
              <strong>Note</strong>:
              <br />
              If you face any errors, refer to the{' '}
              <a
                href="https://addons.prestashop.com/en/content/21-how-to"
                target="_blank"
                rel="noreferrer noopener"
              >
                PrestaShop guide
              </a>
              .
            </p>
          </callout>
        </article>
      </div>
    </main>
  );
}

export default Data;
