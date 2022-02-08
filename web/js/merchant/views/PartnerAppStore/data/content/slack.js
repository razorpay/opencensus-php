function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  const howToVideoUrl = 'https://www.youtube.com/embed/fCncEZiN1xc';

  return (
    <main>
      <div className="description">
        <p>
          Razorpay Slack app comes in super handy to receive your Payments and Banking alerts or
          perform actions right within your Slack channels
        </p>
        <p>
          If you are a <b>Payments</b> user, you can use this app to:
        </p>
        <ol>
          <li>
            Receive Razorpay payment success and failed on Slack channels for Payment Gateway,
            Payment Links and Payment Pages
          </li>
          <li>
            Receive Payment downtimes and resolution updates in Slack channels so you and your team
            can monitor them real time
          </li>
          <li>Create Payment Links without leaving Slack</li>
        </ol>
        <p>
          If you are a <b>Banking</b> user, you can use this app to:
        </p>
        <ol>
          <li>Get alerts on pending Payouts</li>
          <li>Approve and reject pending payouts</li>
          <li>View account balance</li>
        </ol>
        <p>
          To view the list of all Razorpay Slack app commands, check out our{' '}
          <a
            href="https://razorpay.com/docs/app-store/slack/"
            target="_blank"
            rel="noopener noreferrer"
          >
            documentaion
          </a>
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use the Razorpay app on Slack?
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            You will need an activated <a href="https://dashboard.razorpay.com">Razorpay account</a>{' '}
            and a Slack account for this integration to work.
          </p>
          <section className="how-to-use-video">
            <p>Watch this quick video tutorial to start using Razorpay with Slack.</p>
            <iframe width="560" height="315" src={howToVideoUrl} frameBorder="0" allowFullScreen />
          </section>
          <section className="queries-section">
            <p>
              For any issues related to this app, please contact our support team{' '}
              <a href="https://razorpay.com/support/">here.</a>
            </p>

            <p>
              Have any feedback, please feel free to share it with us here -{' '}
              <a href="https://razorpay.typeform.com/to/ImLpWrDc">Share feedback.</a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
