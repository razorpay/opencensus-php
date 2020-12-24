import PadImage from '../PadImage.js';

function Data(brandColor) {
  const PaddedImage = PadImage(brandColor);

  return (
    <main>
      <div className="description">
        <p>
          With your accounting data organized on the cloud, you can track sales, create and send
          invoices, and know how your business is doing at any time.
        </p>

        <p>
          What's more, it's easy to use and you need not be an accounting or finance expert to use
          QuickBooks Accounting Software.
        </p>

        <p>
          Connect your bank account to automatically import and categorize transactions. Sync with
          popular apps and easily snap photos of your receipts to store them with QuickBooks Mobile.{' '}
        </p>

        <p>
          Powerful invoicing features such as invoice tracking, payment reminders are at your
          fingertips. What's more, is that you can access dozens of reports & manage your expenses.
          All power packed in one single solution — QuickBooks Accounting Software. What's more, you
          can also use our mobile app to manage your business on the go!
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <h2 className="how-to-use-heading">How to use Razorpay with QuickBooks</h2>
        <ol>
          <li>Select the + New icon on the left and select Invoice.</li>
          <li>
            <div className="list-flex">
              <p>
                In a new Invoice, you will see the option to make Online payments. Click Setup now.
              </p>
              <PaddedImage
                alt="Online Payments option highlighted in a green box with Razorpay logo selected"
                src="/dist/css/assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-1.png"
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                A decision box will appear asking whether you want to leave without saving. Click
                Yes.
              </p>
              <PaddedImage
                alt="A popup with text Do you want to leave without saving and Yes and No Buttons. Mouse pointing on the Yes."
                src="/dist/css/assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-2.png"
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                You will be directed to a Razorpay QuickBooks integration page. Under Connect your
                Razorpay Account, click one to create a new Razorpay account.
              </p>
              <PaddedImage
                alt="Page with Heading, Accept Payments with Razorpay, 3 infographics about the process and Let's get started button"
                src="/dist/css/assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-3.png"
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                Once your account is set up, go back to QuickBooks and click{' '}
                <strong className>Let’s get started</strong>.
              </p>
              <PaddedImage
                src="/dist/css/assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-13.png"
                alt="Click Let's get started Button"
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                If you are not signed in to your Razorpay account in the same browser window, you
                will be asked to log in and thereafter you will be prompted to give QuickBooks
                permission to connect with Razorpay. Click <strong>Allow</strong>.
              </p>
              <PaddedImage
                src="/dist/css/assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-14.png"
                alt="Click Allow"
              />
            </div>
          </li>
          <li>Check if any pop-ups are blocked and unblock them.</li>
          <li>
            <div className="list-flex">
              <p>
                Click <strong>Authorise </strong>to let QuickBooks access your Razorpay account.
              </p>
              <PaddedImage
                src="/dist/css/assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-15.png"
                alt="Click Authorize"
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>You will get a success notification.</p>
              <PaddedImage
                src="/dist/css/assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-16.png"
                alt="Success Message that reads- Razorpay is Connected"
              />
            </div>
          </li>
        </ol>
      </div>
    </main>
  );
}

export default Data;
