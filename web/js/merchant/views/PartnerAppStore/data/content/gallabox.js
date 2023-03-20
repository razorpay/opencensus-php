function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  return (
    <main>
      <div className="description">
        <p>
          Gallabox is a single, collaborative conversational platform that helps SMBs and users
          manage communication channels such as WhatsApp, Facebook Messenger, Instagram, Live Chat,
          and Email all in one place.
        </p>
        <p>
          <strong>Why Gallabox?</strong>
        </p>
        <p>
          It help manage contacts and conversations from various channels and make it easier for you
          to respond through our collaborative dashboard. Using our shared inbox, team members can
          assign conversations, @mention through private notes, send template notifications to
          streamline sales, customer support, and marketing workflows while integrating with tools
          including eCommerce platforms and payment gateways to automate the customer journey with
          ease
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <section>
            <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
              How to use Gallabox with Razorpay?
            </h2>
            <p style={brandStyles.brandBorderLeft}>
              You will need an active Razorpay account and a Gallabox account for this integration
              to work.
            </p>
            <p>
              There are a large number of use cases that are possible to use with Gallabox and
              Razorpay Integration few of them are listed below:-
            </p>
            <ul>
              <li>
                Converse and engage with customers throughout their order/ payment journey with
                Gallabox’s sophisticated features like bot flows, automated messaging, etc.,
              </li>
              <li>
                Trigger customized and automated WhatsApp message notifications based on the payment
                status.
              </li>
              <li>
                Easily integrate with your CRMs, dynamically generate and send payment links with
                our ready-to-use bot flows.
              </li>
              <li>
                May whatever your use case be - orders/payment confirmation, offers, new product
                alerts, abandoned cart recovery, Invoice clearance & more. Save time by automating
                it with Gallabox
              </li>
              <li>
                In short, Razorpay + Gallabox = efficient payment workflows increased productivity,
                and happier customers.
              </li>
            </ul>

            <p>
              In case you face any issues, reach out to us at
              <a href="mailto:yathin@mangoleap.com "> yathin@mangoleap.com </a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}
export default Data;
