function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  const howToVideoUrl = 'https://www.youtube.com/embed/V8kAAum4i64 ';

  return (
    <main>
      <div className="description">
        <p>
          Raven allows you to send custom SMS and Emails for Razorpay events like Payment link
          creation, Payment successful, Refund processed etc.,
        </p>
        <p>
          With this app you can use your own SenderID for SMS or From address for Email along with
          notification content with your branding as you wish.
        </p>
        <p>
          This app suits small and large businesses who are looking for custom branding for Razorpay
          events with zero programming effort.
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with Raven
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            You will need an activated <a href="https://dashboard.razorpay.com">Razorpay account</a>{' '}
            and a <a href="https://ravenapp.dev/">Raven account</a> for this integration to work.
          </p>
          <section className="how-to-use-video">
            <p>Watch this quick video tutorial to start using Razorpay with Raven.</p>
            <iframe width="560" height="315" src={howToVideoUrl} frameBorder="0" allowFullScreen />
          </section>
          <section className="queries-section">
            <p>
              For more details, refer{' '}
              <a href="https://docs.ravenapp.dev/platform/extensions/razorpay">this</a> step by step
              guide from developer
            </p>
            <p>
              For any issues related to this app, please contact our support team{' '}
              <a href="https://razorpay.com/support/">here.</a>
            </p>

            <p>
              If you have any queries related to Raven product or pricing please reach out to Raven
              team at support@ravenapp.dev.
            </p>
            <p>
              Have any feedback, please feel free to share it with us here -{' '}
              <a href="https://razorpay.typeform.com/to/vcj2Odvo">Share feedback.</a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
