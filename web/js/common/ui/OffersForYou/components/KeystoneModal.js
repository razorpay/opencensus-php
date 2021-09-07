import { useEffect } from 'react';
import { classList } from 'common/utils/rzp-utils';
import { AsyncBtn } from 'common/new-ui/Button';

const KeystoneModal = ({ user, tracking, save }) => {
  const showCorporateCard = user.isProjectKeystoneCorporateCardsEnabled;
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
    cash_advance: [
      {
        iconPath: 'cash-advance/grow-money',
        text: 'Higher limits with increase in business on Razorpay',
      },
      {
        iconPath: 'cash-advance/247-money',
        text: '24/7 Instant Cash Withdrawals at ‘0’ Processing & Annual fee',
      },
      {
        iconPath: 'cash-advance/tick-money',
        text: 'Get Cash Advance line activated same day',
      },
    ],
  };
  const contentToShow = showCorporateCard ? content.corporate_card : content.cash_advance;

  return (
    <div
      className={classList(
        'keystone-details',
        showCorporateCard ? 'corporate-cards' : 'cash-advance',
      )}
    >
      <div className="section">
        <img
          className="rx-logo"
          src="/dist/css/assets/razorpay-x-logo-white.svg"
          alt="Razorpay X"
        />
        <h3 className="heading">
          {showCorporateCard ? (
            <>
              Pre-approved Corporate Card with <br />
              <span>a credit limit of Rs 25,000</span> if you activate RazorpayX Current Accounts
            </>
          ) : (
            <>
              Activate RazorpayX Current Account and get <span> a Rs 25,000 Cash Advance line</span>
            </>
          )}
        </h3>
        <div className="feature-list">
          {contentToShow.map(({ iconPath, text }) => (
            <div className="feature-card">
              <img src={`https://cdn.razorpay.com/static/assets/${iconPath}.svg`} />
              <span>{text}</span>
            </div>
          ))}
        </div>
        <div className="btn-wrapper">
          <AsyncBtn.Primary type="submit" class="btn btn-primary" onClick={save}>
            {showCorporateCard ? 'Apply For Current Account' : 'Apply Now'}
          </AsyncBtn.Primary>
        </div>
      </div>
    </div>
  );
};

export default KeystoneModal;
