function Data(brandColor) {
  const brandStyles = {
    listBg: { backgroundColor: brandColor },
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  const howToVideoUrl = 'https://www.youtube.com/playlist?list=PLgffPJ6GjbaJI59pvLMrn_zHXqyRXkhRX';

  return (
    <main>
      <div className="description">
        <p>
          Pabbly Connect provides you the ability to integrate multiple applications by managing the
          data flow smoothly with no technical skills required. It holds more than 850+ integration.
        </p>
        <p>
          <strong>Why Pabbly Connect?</strong>
        </p>
        <p>
          Pabbly Connect is a workflow automation platform that connects multiple applications
          together.
        </p>
        <br />
        <p>
          You can send data from one application to another application and sync your data across
          multiple applications.
        </p>
        <br />
        <p>
          Currently, Pabbly Connect has over 850+ applications integrated and has more than 9,000+
          customers worldwide.
        </p>
        <br />
        <br />
        <p>
          You will find various easy-to-use inbuilt tools that help you create automation workflows
          with advanced capabilities like scheduling, delay, router, and many more. Pabbly Connect
          supports all the popular apps for CRM, Marketing, E-Commerce, Helpdesk, Payments, Web
          forms, Collaboration, and much more.
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <section>
            <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
              How to use Pabbly with Razorpay
            </h2>
            <p style={brandStyles.brandBorderLeft}>
              You will need an active Razorpay account and a Pabbly Connect account for this
              integration to work.
            </p>
            <br />
            <br />
            <p>
              There are a large number of use cases that are possible to use with Pabbly and
              Razorpay Integration few of them are listed below:-
            </p>
            <ol>
              <li>
                <div className="list-counter" style={brandStyles.listBg} />
                When a new order/invoice is paid in Razorpay, Add the customer details to Google
                Sheets immediately via Pabbly Connect.
              </li>
              <li>
                <div className="list-counter" style={brandStyles.listBg} />
                When a payment is failed in RazorPay, Send the customer notification on their
                WhatsApp for retrying the payment.
              </li>
              <li>
                <div className="list-flex">
                  <p>
                    <div className="list-counter" style={brandStyles.listBg} />
                    When Invoice is paid in the Razorpay, Create an Order in Woocommerce.
                  </p>
                </div>
              </li>
              <li>
                <div className="list-flex">
                  <p>
                    <div className="list-counter" style={brandStyles.listBg} />
                    When Invoice is paid in the Razorpay, Add the meeting registrant in Zoom.
                  </p>
                </div>
              </li>
            </ol>
            <p>
              You can refer to our video library that is created by our team to see how Razorpay can
              be integrated with different applications using Pabbly Connect. The video library
              contains more than 100+ videos for integrating RazorPay with different applications.
            </p>

            <section className="how-to-use-video">
              <p>Watch this quick video tutorial to start using Razorpay with Pabbly Connect.</p>
              <iframe
                width="560"
                height="315"
                src={howToVideoUrl}
                frameBorder="0"
                allowFullScreen
              />
            </section>

            <p>
              You can contact us at <a href="mailto:admin@pabbly.com">admin@pabbly.com</a>
              &nbsp;or call us at <a href="tel:9926465653">+91 9926465653</a> or{' '}
              <a href="tel:9926255956">+91 9926255956</a>
            </p>

            <br />
            <p>
              You can also post your questions on the community{' '}
              <a href="https://forum.pabbly.com" rel="noopener nofollow">
                forum
              </a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
