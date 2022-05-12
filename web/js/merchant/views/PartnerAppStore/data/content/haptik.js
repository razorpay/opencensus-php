function Data(brandColor) {
  const brandStyles = {
    brandBorderLeft: { borderLeft: `8px solid ${brandColor}` },
  };

  return (
    <main>
      <div className="description">
        <p>
          Built on official WhatsApp Business APIs, Interakt enables fast-growing E-commerce and D2C
          brands to multiply sales via automated promotional & transactional nudges and also via a
          smooth checkout chat-flow, for your customers to place orders on WhatsApp.
          <br />
          <br />
          Haptik is a leader in Conversational AI, which helps businesses set up advanced chatbots
          on multiple channels. With the Razorpay integration, brands can use payment links in
          chatbot flows to improve collections efficiency and boost conversational commerce.
        </p>
        <p>
          <strong>Why Interakt?</strong>
        </p>
        <p>With Interakt, business owners can: </p>
        <ul>
          <li>
            Trigger automated WhatsApp notifications for abandoned cart recoveries, order / payment
            confirmations, offers, back in stock alerts etc
          </li>
          <li>
            Configure auto-replies to FAQs and build automated chat flows to help customers
            seamlessly place orders on WhatsApp
          </li>
          <li>
            Manage customer chats efficiently in a Shared Team Inbox by assigning and labeling chats
          </li>
          <li>
            Send the newly introduced Multi-product Catalog Messages to 1000s of customers at scale
            and get WhatsApp carts from them
          </li>
          <li>
            Integrate WhatsApp with their business workflows in other tools, like: Shopify,
            WooCommerce, Zoho, Google Sheets, Facebook Leads & more!
          </li>
          <li>Generate qualified leads by adding a WhatsApp chat widget on their websites</li>
        </ul>
        <p>With Razorpay integrated with Haptik, brands can: </p>
        <ul>
          <li>
            Reduce cart dropouts by giving customers a seamless payment experience during the
            conversational flow
          </li>
          <li>
            Accelerate time-to-value with Haptik's field-tested, Smart Skills and automate common
            use cases such as pay insurance premium, pay loan EMI, pay utility bills, and more
          </li>
          <li>
            Easily integrate with other systems like CRMs to pass on details to general easy Payment
            Links
          </li>
          <li>
            Build seamless revenue pipeline with reminders and notification for recurring payments
          </li>
        </ul>
      </div>
      <div className="how-to-use-razorpay">
        <article>
          <h2 className="how-to-use-heading" style={brandStyles.brandBorderLeft}>
            How to use the Razorpay with Interakt Haptik?
          </h2>
          <br />
          <p>To use Razorpay with Interakt: </p>
          <ul>
            <li>
              Set live your Razorpay integration by logging into your Razorpay account from the
              Integrations page in Interakt
            </li>
            <li>
              Then, simply set live your automated checkout flow from Interakt’s Commerce Settings.
              This flow will be triggered whenever a customer sends you a WhatsApp cart. A Razorpay
              payment link will be automatically sent in the chat flow after your customer has
              provided his shipping address
            </li>
            <li>
              To be able to send automatic WhatsApp notifications for payment confirmations &
              failures (payment link / invoice):
              <ul>
                <li>
                  copy the webhook URL from Interakt’s Integrations page and configure it in your
                  Razorpay dashboard.
                </li>
                <li>
                  Then, set live an Ongoing Campaign in Interakt and select the trigger as ‘Payment
                  Succeeded’ / ‘Payment Failed’. (You can add further more conditions & filters to
                  the campaign audience.)
                </li>
              </ul>
            </li>
          </ul>
          <p>To use Razorpay with Haptik: </p>
          <ul>
            <li>In the bot builder, use Razorpay based smart skills to build payment use cases</li>
            <li>
              Plug in key_id and key_secret from your Razorpay Merchant Dashboard into the bot flow
            </li>
            <li>
              Define the input sources and payment parameters for the Razorpay Payment Link like
              amount,expire_by, currency, payment labels, etc. as per the smart skill documentation.
              You are good to run your payment flows now.
            </li>
          </ul>
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
