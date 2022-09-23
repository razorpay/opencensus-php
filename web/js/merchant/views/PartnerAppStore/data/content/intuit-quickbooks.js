import PadImage from '../PadImage';
import Img1 from 'assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-1.png';
import Img2 from 'assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-2.png';
import Img3 from 'assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-3.png';
import Img4 from 'assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-13.png';
import Img5 from 'assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-14.png';
import Img6 from 'assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-15.png';
import Img7 from 'assets/app-store/content-assets/intuit-quickbooks/intuit-quickbooks-16.png';

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
        <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
          How to use Razorpay with QuickBooks
        </h2>
        <ol>
          <li>
            <div className="list-counter" style={brandStyles.listBg} />
            Select the + New icon on the left and select Invoice.
          </li>
          <li>
            <div className="list-flex">
              <p>
                <div className="list-counter" style={brandStyles.listBg} />
                In a new Invoice, you will see the option to make Online payments. Click Setup now.
              </p>
              <PaddedImage
                alt="Online Payments option highlighted in a green box with Razorpay logo selected"
                src={Img1}
                isWebP
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                <div className="list-counter" style={brandStyles.listBg} />A decision box will
                appear asking whether you want to leave without saving. Click Yes.
              </p>
              <PaddedImage
                alt="A popup with text Do you want to leave without saving and Yes and No Buttons. Mouse pointing on the Yes."
                src={Img2}
                isWebP
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                <div className="list-counter" style={brandStyles.listBg} />
                You will be directed to a Razorpay QuickBooks integration page. Under Connect your
                Razorpay Account, click one to create a new Razorpay account.
              </p>
              <PaddedImage
                alt="Page with Heading, Accept Payments with Razorpay, 3 infographics about the process and Let's get started button"
                src={Img3}
                isWebP
              />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                <div className="list-counter" style={brandStyles.listBg} />
                Once your account is set up, go back to QuickBooks and click{' '}
                <strong className>Let’s get started</strong>.
              </p>
              <PaddedImage alt="Click Let's get started Button" src={Img4} isWebP />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                <div className="list-counter" style={brandStyles.listBg} />
                If you are not signed in to your Razorpay account in the same browser window, you
                will be asked to log in and thereafter you will be prompted to give QuickBooks
                permission to connect with Razorpay. Click <strong>Allow</strong>.
              </p>
              <PaddedImage alt="Click Allow" src={Img5} isWebP />
            </div>
          </li>
          <li>
            <div className="list-counter" style={brandStyles.listBg} />
            Check if any pop-ups are blocked and unblock them.
          </li>
          <li>
            <div className="list-flex">
              <p>
                <div className="list-counter" style={brandStyles.listBg} />
                Click <strong>Authorise </strong>to let QuickBooks access your Razorpay account.
              </p>
              <PaddedImage src={Img6} isWebP alt="Click Authorize" />
            </div>
          </li>
          <li>
            <div className="list-flex">
              <p>
                <div className="list-counter" style={brandStyles.listBg} />
                You will get a success notification.
              </p>
              <PaddedImage
                src={Img7}
                isWebP
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
