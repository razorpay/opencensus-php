import { useEffect } from 'react';

function Zaps() {
  useEffect(() => {
    const widget = document.createElement('script');
    widget.type = 'text/javascript';
    widget.async = true;
    widget.src =
      'https://zapier.com/apps/embed/widget.js?services=razorpay-1&limit=10&html_id=zapier-widget';
    document.body.appendChild(widget);
  }, []);

  return <div id="zapier-widget" />;
}

function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  const howToVideoUrl = 'https://www.youtube.com/embed/8dVBLa8PEZs';

  return (
    <main>
      <div className="description">
        <p>
          With the Zapier app, users can integrate Razorpay with other web apps by moving the data
          automatically using triggers and actions. With this you can automate tedious tasks and
          save time to focus on your most important work.
        </p>
        <p>
          <strong>Why Zapier?</strong>
        </p>
        <p>
          Here are some sample workflows that can significantly reduce your effort while working
          with Razorpay products -
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
          wide variety of Zapier actions
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with Zapier
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            You will need an activated <a href="https://dashboard.razorpay.com">Razorpay account</a>{' '}
            and a <a href="https://zapier.com/">Zapier account</a> for this integration to work.
          </p>
          <section className="how-to-use-video">
            <p>Watch this quick video tutorial to start using Razorpay with Zapier.</p>
            <iframe width="560" height="315" src={howToVideoUrl} frameBorder="0" allowFullScreen />
          </section>
          <section className="queries-section">
            <p>
              <h2>Try our ready-to-use Zap templates</h2>
            </p>
            <Zaps />
          </section>
          <section className="queries-section">
            <p>
              For any issues related to this app, please contact our support team{' '}
              <a href="https://razorpay.com/support/">here.</a>
            </p>

            <p>
              Have any feedback, please feel free to share it with us here -{' '}
              <a href="https://razorpay.typeform.com/to/o4Gv6b3k">Share feedback.</a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
