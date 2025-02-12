import { closeModal } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import { Link } from 'react-router-dom';
import { isMobileDevice } from 'merchant/components/Home/data';

const typeList = {
  FL: {
    header: 'Users abandoning their cart at checkout page?',
    subHeader: 'Reach out to such users with Payment Links.',
    list: [
      'Retarget dropped-off leads',
      'Boost sales by clubbing promotional offers',
      'Set expiry on payment links and create urgency',
      'Get 1-click checkout with UPI payment links',
      'Improve ROI of your remarketing campaigns',
    ],
  },
  EF: {
    header: 'Give your online revenue a boost of up to 40%',
    subHeader: 'Use Payment Links for instant payment collection for big-ticket purchases',
    list: [
      'Enable partial payment options',
      'Collect digital payment at the time of delivery',
      'Offer 100+ payment modes',
      'Schedule automatic reminders',
      'Incentivize purchase by clubbing promotional offers',
    ],
  },
  G: {
    header: 'Give your online revenue a boost of up to 40%',
    subHeader: 'Use Payment Links for instant payment collection anytime, anywhere!',
    list: [
      'Enable partial payment options',
      'Improve collections with payment reminders',
      'Offer 100+ payment modes',
      'Boost sales by clubbing promotional offers',
      'Get 1-click checkout with UPI payment links',
    ],
  },
};

const listMobile = [
  'Convert COD to online payments',
  'Share via SMS, WhatsApp and Chatbot',
  'Send timely reminders to customers',
];
const PLFeaturesModal = ({ closeModalBanner, type }) => {
  const header = typeList[type]?.header;
  let subHeader = typeList[type]?.subHeader;
  subHeader = isMobileDevice()
    ? 'Recover your lost revenue by up to 40% with Payment Links'
    : subHeader;

  let bulletPoint = [];
  if (isMobileDevice()) {
    bulletPoint = listMobile?.map((item) => {
      return <li key={item}>{item}</li>;
    });
  } else {
    bulletPoint = typeList[type]?.list?.map((item) => {
      return <li key={item}>{item}</li>;
    });
  }

  return (
    <div className="pl-catalyst-modal">
      <div className="white-circle" />
      <button type="button" className="close" onClick={closeModalBanner}>
        <i className="i i-close" />
      </button>
      <div className="modal-body">
        <h3 className="heading nomob">
          <img
            className="pl-bullet"
            src="https://cdn.razorpay.com/static/assets/payment-links/pl-bullet.svg"
            alt="Point"
          />
          {header}
        </h3>
        <h4 className="sub-heading">
          <img
            className="pl-bullet mob"
            src="https://cdn.razorpay.com/static/assets/payment-links/pl-bullet.svg"
            alt="Point"
          />
          {subHeader}
        </h4>
        <div className="pl-planes-group mob">
          <img
            src="https://cdn.razorpay.com/static/assets/payment-links/pl-plane-purple.svg"
            className="pl-plane__rel"
            id="purple-mob-top"
          />
          <img
            src="https://cdn.razorpay.com/static/assets/payment-links/pl-plane-green.svg"
            className="pl-plane__rel"
            id="green-mob-middle"
          />
          <img
            src="https://cdn.razorpay.com/static/assets/payment-links/pl-plane-purple.svg"
            className="pl-plane__rel"
            id="purple-mob-bottom"
          />
        </div>
        <div className="section">
          <div className="left-section">
            <ul>{bulletPoint}</ul>
            <Link to="/paymentlinks/new" className="modal-cta" onClick={closeModalBanner}>
              <Button.Primary className="btn btn-primary" type="button">
                Create a Payment Link
              </Button.Primary>
            </Link>
          </div>
          <div className="right-section nomob">
            <div className="x-dashboard-view">
              <div className="tab-section">
                <span className="dot" id="red" />
                <span className="dot" id="orange" />
                <span className="dot" id="green" />
              </div>
              <img
                src="https://cdn.razorpay.com/static/assets/payment-links/pl-creation-steps.gif"
                alt="Steps to create a Payment Link"
              />
            </div>
          </div>
        </div>
        <div className="footer">
          <div className="title">Trusted by</div>
          <div className="company-list">
            <div className="company-group">
              <div className="logo">
                <img
                  src="https://cdn.razorpay.com/static/assets/external-products/zivame.svg"
                  alt="Zivame"
                />
              </div>
              <div className="logo">
                <img
                  src="https://cdn.razorpay.com/static/assets/external-products/furlenco.svg"
                  alt="Furlenco"
                />
              </div>
            </div>
            <div className="company-group">
              <div className="logo">
                <img
                  src="https://cdn.razorpay.com/static/assets/external-products/mamaearth.svg"
                  alt="Mamaearth"
                />
              </div>
              <div className="logo">
                <img
                  src="https://cdn.razorpay.com/static/assets/external-products/kama-ayurveda.svg"
                  alt="Kama Ayurveda"
                />
              </div>
              <div className="logo">
                <img
                  src="https://cdn.razorpay.com/static/assets/external-products/vijay-sales.svg"
                  alt="Vijay Sales"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="nomob">
        <img
          src="https://cdn.razorpay.com/static/assets/payment-links/pl-plane-green.svg"
          className="pl-plane"
          id="green-top-left"
        />
        <img
          src="https://cdn.razorpay.com/static/assets/payment-links/pl-plane-purple.svg"
          className="pl-plane"
          id="purple-top-left"
        />
        <img
          src="https://cdn.razorpay.com/static/assets/payment-links/pl-plane-green.svg"
          className="pl-plane"
          id="green-bottom-right"
        />
      </div>
    </div>
  );
};

export default compose(connect(null, { closeModalBanner: closeModal }))(PLFeaturesModal);
