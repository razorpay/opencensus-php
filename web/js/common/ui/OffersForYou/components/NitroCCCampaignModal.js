import { classList } from 'common/utils/rzp-utils';
import { AsyncBtn } from 'common/new-ui/Button';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';

const NitroCCCampaignModal = ({ closeModal, save }) => {
  const content = {
    corporate_card: [
      {
        iconPath: 'corporate-cards/grow-money',
        text: 'Grow your limit without collateral',
      },
      {
        iconPath: 'corporate-cards/card-money',
        text: 'A card for your business spends',
      },
      {
        iconPath: 'corporate-cards/tick-money',
        text: 'Get Started Instantly',
      },
    ],
  };
  const contentToShow = content.corporate_card;

  return (
    <div className={classList('nitro-CC-details', 'corporate-cards')}>
      <div className="section">
        <img
          className="rx-logo"
          src="/dist/css/assets/razorpay-x-logo-white.svg"
          alt="Razorpay X"
        />
        <button type="button" className="closemodal" onClick={closeModal}>
          <i className="i i-close" />
        </button>
        <h3 className="heading">
          <>
            Guaranteed Corporate Card with
            <br />
            <span>a credit limit of Rs 5lacs</span> if you activate RazorpayX Current Accounts
            <h6>
              Rs. 25,000 card will be activated on documents submission with
              <br />
              evalution after opening the account
            </h6>
          </>
        </h3>
        <div className="feature-list">
          {contentToShow.map(({ iconPath, text }) => (
            <div key={text} className="feature-card">
              <img src={`https://cdn.razorpay.com/static/assets/${iconPath}.svg`} />
              <span>{text}</span>
            </div>
          ))}
        </div>
        <div className="btn-wrapper">
          <AsyncBtn.Primary type="submit" class="btn btn-primary" onClick={save}>
            Apply For Current Account
          </AsyncBtn.Primary>
        </div>
      </div>
    </div>
  );
};

export default compose(connect(null, { closeModal: closeModalProp }))(NitroCCCampaignModal);
