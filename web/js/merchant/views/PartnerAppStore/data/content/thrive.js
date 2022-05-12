function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };

  return (
    <main>
      <div className="description">
        <p>
          Thrive helps restaurants set up online ordering systems and deliver food directly to their
          consumers. Today, over 9500 restaurants across 76 cities in India use Thrive, and have
          collectively fulfilled orders worth over 30Cr minus the hefty commissions charged by
          aggregators and with complete customer data ownership.
          <br />
          <br />
          Thrive unbundles the aggregator value proposition and allows restaurants to work with
          solutions of their choice thanks to its integrations with most major POS systems such as
          Petpooja, POSist and Posify along with logistics service providers such as Dunzo, Borzo
          and Shadowfax.
        </p>
        <p>
          <strong>Why Thrive Now?</strong>
        </p>
        <p>
          Thrive Now is built for restaurants along with restaurateurs itself, and fulfils all their
          needs to help them succeed!{' '}
        </p>
        <ul>
          <li>Low & fair commission of just 3% per order for delivery & takeaway.</li>
          <li>Commission-free dine-in solution</li>
          <li>Free DIY setup in 15 minutes</li>
          <li>Integrated with delivery service providers</li>
          <li>Integrated with major POS systems</li>
          <li>
            Holistic Marketing CRM to acquire & retain customers. Includes loyalty, feedback,
            referrals, Facebook/ Instagram ads and more.
          </li>
          <li>Business Dashboard to manage menu and monitor performance</li>
          <li>Easy to use Order Management Dashboard</li>
        </ul>
        <p>
          Visit our website to know more: ;
          <a href="https://about.thrivenow.in/" target="_blank" rel="noopener noreferrer">
            https://about.thrivenow.in/
          </a>
        </p>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use the Razorpay with Thrive Now?
          </h2>
          <p style={brandStyles.brandBorderLeft}>
            Thrive Now is integrated with Razorpay, all payments are enabled via the same itself.
            Hence, simply start using it by signing and setting up your restaurant ordering system
            via Thrive Now.
          </p>
          <section className="queries-section">
            <p>
              For any issues related to this app, please contact our support team{' '}
              <a href="https://razorpay.com/support/" target="_blank" rel="noopener noreferrer">
                here.
              </a>
            </p>

            <p>
              Have any feedback, please feel free to share it with us here -{' '}
              <a
                href="https://razorpay.typeform.com/to/ImLpWrDc"
                target="_blank"
                rel="noopener noreferrer"
              >
                Share feedback.
              </a>
            </p>
          </section>
        </article>
      </div>
    </main>
  );
}

export default Data;
