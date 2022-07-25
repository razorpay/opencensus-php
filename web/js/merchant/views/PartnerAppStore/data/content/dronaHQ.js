function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };
  const howToVideoUrl = 'https://www.youtube.com/watch?v=BcuvGkKt2J8';

  return (
    <main>
      <div className="description">
        <p>
          Drag - Drop Low-Code Platform to build web and mobile apps at speed. Democratising
          internal app development and relieving engineering teams for frontend development needs of
          admin panels, GUIs, dashboards, forms, portals, CRUD apps.
        </p>
        <p>
          <strong>Why DronaHQ?</strong>
        </p>
        <p>
          DronaHQ is a leading SaaS based comprehensive low-code app development platform to build
          secure mobile & web applications, using a drag and drop builder with pre-built UI
          elements, integrations, and add-ons. DronaHQ is a recognized global leader in enterprise
          low-code tech.
        </p>
        <br />
        <p>
          It gives engineering teams the ability to build rich user interfaces. The only platform to
          offer both web and mobile app development, and usage-based pricing plans (unlimited users
          in each plan).
        </p>
        <br />
        <p>
          Trusted by CTOs and engineering teams of startups and Fortune 500 enterprises and loved by
          developers.
        </p>
        <br />
        <p>
          With Razopray plus DronaHQ integration, you can easily build a webpage or mobile app that
          can allow you to accept payments via payment links.
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <section>
            <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
              How to use DronaHQ with Razorpay?
            </h2>
            <p style={brandStyles.brandBorderLeft}>
              You will need an activated Razorpay account and a DronaHQ account for this integration
              to work.
            </p>
            <p>
              Please follow the steps &nbsp;
              <a
                href="https://community.dronahq.com/t/integrating-razorpay-with-dronahq/839"
                rel="noopener nofollow"
              >
                here
              </a>
              &nbsp; or follow the video to integrate
            </p>

            <section className="how-to-use-video">
              <p>Watch this quick video tutorial to start using Razorpay with DronaHQ.</p>
              <iframe
                width="560"
                height="315"
                src={howToVideoUrl}
                frameBorder="0"
                allowFullScreen
              />
            </section>

            <p>
              You can contact us at{' '}
              <a href="mailto:friends@dronamobile.com">friends@dronamobile.com</a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
