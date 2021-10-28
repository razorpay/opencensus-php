function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };

  return (
    <main>
      <div className="description">
        <p>
          Razorpay app for Slack will notify you on Payment downtimes as and when they happen in any
          channel you prefer.
        </p>
        <p>
          Just connect your slack account with your Razorpay merchant account and subscribe to
          downtime alerts using - <b>/razorpay</b> commands
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <section>
            <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
              How to use the Razorpay app on Slack?
            </h2>
            <p style={brandStyles.brandBorderLeft}>
              You will need an activated{' '}
              <a href="https://dashboard.razorpay.com">Razorpay account</a> and a Slack account for
              this integration to work.
            </p>
          </section>
          <section className="how-to-use-video">
            <p>Here is how you can use this app with four simple steps</p>
            <ul>
              <li>
                Click on{' '}
                <a
                  href="https://rzp-labs-slack.razorpay.com/slack-connect"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  ‘Get Started’
                </a>{' '}
                and log in to your Slack account
              </li>
              <li>
                If you are admin of the Slack workspace you wish to install, then click authorise in
                next step, else request your admin to authorize
                <br />
                <br />
                Once authorized, Razorpay will be added to your workspace.
              </li>
              <li>
                Type <b>“/razorpay connect” </b> in any channel and send. As a response you will see
                a welcome message with the <b>“Connect to Razorpay”</b> button. Click on the same
                and follow the steps as it is.
                <br />
                <br />
                You will need owner access to a Razorpay account to make this step work. Once done,
                you should see a success message from Razorpay
              </li>
              <li>
                Once connected, type <b>‘/razorpay subscribe payment_downtimes’</b> in a channel
                where you wish to get notifications.
              </li>
            </ul>
            <p>
              That’s it! Your Razorpay payment downtime updates on slack are configured. Now sit
              back and relax. When there is downtime next time we will alert you!
            </p>
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
