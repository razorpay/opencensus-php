function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  const howToVideoUrl = 'https://www.youtube.com/embed/cuNLNF6Pi8I';

  return (
    <main>
      <div className="description">
        <p>
          AiSensy allows you to create a Whatsapp business account and send customer notifications
          for Razorpay events to your customers.
        </p>
        <p>
          One of the popular use cases you can achieve with this app is to send Payment links via
          Whatsapp from your Whatsapp business handle without having to put any programming effort.
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use Razorpay with AiSensy
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            You will need an activated <a src="https://dashboard.razorpay.com">Razorpay account</a>{' '}
            and a <a href="https://m.aisensy.com/">AiSensy account</a> for this integration to work.
          </p>
          <section className="how-to-use-video">
            <p>Watch this quick video tutorial to start using Razorpay with AiSensy.</p>
            <iframe width="560" height="315" src={howToVideoUrl} frameBorder="0" allowFullScreen />
          </section>
          <section className="queries-section">
            <p>
              For any issues related to this app, please contact our support team{' '}
              <a href="https://razorpay.com/support/">here.</a>
            </p>

            <p>
              If you have any queries related to AiSensy product or pricing please reach out to
              AiSensy team <a href="https://m.aisensy.com/contact-us/">Contact AiSensy.</a>
            </p>
            <p>
              Have any feedback, please feel free to share it with us here -{' '}
              <a href="https://razorpay.typeform.com/to/EmuJy1aX">Share feedback.</a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
