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
        text: 'Make recurring and international payments',
      },
      {
        iconPath: 'corporate-cards/card-money',
        text: 'Avail business rewards and cheaper FX charges',
      },
      {
        iconPath: 'corporate-cards/tick-money',
        text: 'Custom limits, permissions and easy repayments',
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
            Personal credit cards
            <br />
            for businesses expenses ?
            <br />
            <span>Not anymore!</span>
            <h6>
              Avoid Liability and audit hassles.
              <br />
              switch to RazorpayX Corporate Cards without any collateral.
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
          <AsyncBtn.Primary type="submit" class="btn btn-color" onClick={save}>
            I am Interested ✨
          </AsyncBtn.Primary>
        </div>
      </div>
    </div>
  );
};

export default compose(connect(null, { closeModal: closeModalProp }))(NitroCCCampaignModal);
