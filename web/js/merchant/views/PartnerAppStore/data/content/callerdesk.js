function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  const howToVideoUrl = 'https://www.youtube.com/embed/N_flr2qm9A0  ';

  return (
    <main>
      <div className="description">
        <p>
          CallerDesk allows you to auto trigger VoIP calls when a new Invoice link or Payment link
          is created. You can choose either a standard voice clip or create your own for these
          triggers.
        </p>
        <p>
          With this you can notify your customers much effectively and improve your paid rate. All
          of this is possible with zero programming effort.
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with CallerDesk
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            You will need an activated <a href="https://dashboard.razorpay.com">Razorpay account</a>{' '}
            and a <a href="https://callerdesk.io/">CallerDesk account</a> for this integration to
            work.
          </p>
          <section className="how-to-use-video">
            <p>Watch this quick video tutorial to start using Razorpay with CallerDesk.</p>
            <iframe width="560" height="315" src={howToVideoUrl} frameBorder="0" allowFullScreen />
          </section>
          <section className="queries-section">
            <p>
              For any issues related to this app, please contact our support team{' '}
              <a href="https://razorpay.com/support/">here.</a>
            </p>

            <p>
              If you have any queries related to CallerDesk product or pricing please reach out to
              CallerDesk team here -{' '}
              <a href="https://callerdesk.io/contact.php">Contact CallerDesk</a>
            </p>
            <p>
              Have any feedback, please feel free to share it with us here -{' '}
              <a href="https://razorpay.typeform.com/to/Gb7Ou3tA">Share feedback.</a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
