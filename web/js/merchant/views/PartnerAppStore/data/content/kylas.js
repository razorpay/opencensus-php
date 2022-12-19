import PadImage from 'merchant/views/PartnerAppStore/data/PadImage';
import Kylas1 from 'assets/app-store/content-assets/kylas/1.jpg';
import Kylas2 from 'assets/app-store/content-assets/kylas/2.jpg';
import Kylas3 from 'assets/app-store/content-assets/kylas/3.jpg';
import Kylas4 from 'assets/app-store/content-assets/kylas/4.jpg';
import Kylas5 from 'assets/app-store/content-assets/kylas/5.jpg';
import Kylas6 from 'assets/app-store/content-assets/kylas/6.jpg';
import Kylas7 from 'assets/app-store/content-assets/kylas/7.jpg';

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
          Kylas is an enterprise-grade Sales CRM uniquely designed for growing businesses. It helps
          SMBs identify the right sales opportunities & supports their sales efforts to increase
          their chances of success.
        </p>

        <p>
          With Kylas, SMBs can keep track of their sales activities, increase their sales efficiency
          and improve their overall productivity
        </p>

        <p>
          It is easy to use, even for CRM beginners and comes with a suite of features to solve all
          sales-related problems.
        </p>

        <p>
          On Kylas, businesses can capture leads, update deals, automate workflows, get detailed
          reports and monitor activities without breaking a sweat. Plus, it integrates with over 50
          popular business apps!
        </p>

        <p>
          Currently, Kylas integrates with over 50 popular business apps and is trusted by over
          3800+ businesses across industries.
        </p>
      </div>

      <div className="how-to-use-razorpay">
        <article>
          <h2 style={brandStyles.brandBorderLeft}>How to use Razorpay with Kylas</h2>
          <p>
            Razorpay and Kylas integration can help businesses generate payment links from Kylas CRM
            itself. You can also track the list of payment links sent to each customer and get
            status update as a note on the payment link.
          </p>
          <p>
            Here's how you can connect your Razorpay Account with Kylas to generate Payment links:
          </p>
          <ol>
            <li>
              <div className="list-counter" style={brandStyles.listBg} />
              Visit Kylas{' '}
              <a href="https://kylas.io/" target="_blank" rel="noreferrer noopener">
                Website
              </a>
              .
              <br />
              If you don't have a Kylas Account then click on 'Sign Up for Free' button shown and
              create a free account on Kylas.
              <br />
              If you already have a Kylas Account then click on 'Login' button on the website or
              directly go to Kylas Sign In Page and login using your Kylas Account credentials.
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click on the <strong>top left burger menu</strong> →{' '}
                  <strong>Choose Kylas Marketplace</strong>.
                </p>
                <PaddedImage src={Kylas1} isWebP alt="Choose Kylas marketplace" />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Search for the <strong>Razorpay - Payment Link generation</strong>app from the
                  list of available apps.
                </p>
                <PaddedImage alt="add payments methods" src={Kylas2} isWebP />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Click on the app and then click on the <strong>Install</strong> button on the app
                  details page as shown below.
                </p>
                <PaddedImage alt="add razorpay" src={Kylas3} isWebP />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  After successful installation, Create an Account on the Razorpay - Payment link
                  generation application. And Sign in to the app
                </p>
                <PaddedImage alt="add razorpay" src={Kylas4} isWebP />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Setup your Razorpay application as mentioned in the <strong>About</strong> section
                  of the app.
                </p>
                <PaddedImage alt="add razorpay" src={Kylas5} isWebP />
              </div>
            </li>
            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  After the app has been setup, navigate to Kylas Sales and Go to a Lead or Deal
                  details page
                  <br />
                  Click on the More actions button -> Click Generate payment link
                </p>
                <PaddedImage alt="add razorpay" src={Kylas6} isWebP />
              </div>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  Enter the amount and other details that are required and click
                  <strong>Create Link</strong>
                </p>
                <PaddedImage alt="add razorpay" src={Kylas7} isWebP />
              </div>
            </li>

            <li>
              <div className="list-flex">
                <p>
                  <div className="list-counter" style={brandStyles.listBg} />
                  This will create a payment link and you can view the link under Payment link logs.
                  <br />
                  Along with creating payment links, you can automate certain actions that you want
                  the system to perform e.g. Changing the pipeline stage after a payment is
                  successful
                </p>
              </div>
            </li>
          </ol>
        </article>
      </div>
    </main>
  );
}

export default Data;
