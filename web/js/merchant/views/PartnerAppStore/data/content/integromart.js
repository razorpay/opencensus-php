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
          With the Integromat app, users can integrate Razorpay with other web apps by moving the
          data automatically using triggers and actions. With this you can automate tedious tasks
          and save time to focus on your most important work.
        </p>
        <p>
          <strong>Why Integromart?</strong>
        </p>
        <p>
          Here are some sample workflows that can significantly reduce your effort while working
          with Razorpay products -{' '}
        </p>
        <ul>
          <li>
            Update a Google sheet or an excel sheet when a new transaction happens in your Razorpay
            account
          </li>
          <li>
            Send an automated email to your customers when a new payment is successful or failed
            through apps like Gmail, Sendgrid etc.,
          </li>
          <li>
            Create a GST invoice and send it to customer on a successful payment through accounting
            softwares like Zoho
          </li>
          <li>
            Create a customer or update details of existing customers in CRM tools like Hubspot,
            Salesforce etc., when a new payment is successful.
          </li>
        </ul>
        <p>
          Or use your imagination to achieve hundred other possible use cases Razorpay triggers plus
          wide variety of Integromat actions
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with Integromat?
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            You will need an activated <a href="https://dashboard.razorpay.com">Razorpay account</a>{' '}
            and an <a href="https://www.integromat.com/en/register">Integromart account</a> for this
            integration to work.
          </p>
          <section className="how-to-use-video">
            <p>
              Note: Integromat is in private launch right now. So you will have to accept the invite
              to get early access to it.
            </p>
            <p>Steps to use Integromat:</p>
          </section>
          <ol>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  <a
                    href="https://www.integromat.com/en/apps/invite/fb21279e0381a5a245f3d0102250b268"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Click on the invite here{' '}
                  </a>
                  for Integromat app .
                </p>
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click “Add to my Inventory on the landing page”
                </p>
                <PaddedImage
                  alt="Integromat add to my inventory screenshot"
                  src="/dist/css/assets/app-store/content-assets/integromart/add-to-inventory.png"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  If you are not signed in already, please proceed with sign in. If you do have an
                  account, create one by signing up.
                </p>
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  When you see a screen like this, click “Start using Razorpay”
                </p>
                <PaddedImage
                  alt="Start using Razorpay on integromat screenshot"
                  src="/dist/css/assets/app-store/content-assets/integromart/start-using-razorpay.png.png"
                />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  You can start creating scenarios in Integromat by searching for Razorpay
                </p>
                <PaddedImage
                  alt="Start creating scenarios"
                  src="/dist/css/assets/app-store/content-assets/integromart/create-scenarios.png"
                />
              </div>
            </li>
          </ol>
          <section className="queries-section">
            <p>
              For any issues related to this app, please contact our support team{' '}
              <a href="https://razorpay.com/support/" target="_blank" rel="noreferrer">
                here.
              </a>
            </p>

            <p>
              Have any feedback, please feel free to share it with us here -{' '}
              <a href="https://razorpay.typeform.com/to/HIi7IYHN" target="_blank" rel="noreferrer">
                Share feedback.
              </a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
