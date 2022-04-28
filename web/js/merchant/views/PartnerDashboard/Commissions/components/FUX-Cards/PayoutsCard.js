import { useEffect, useState } from 'react';
import { setItem, getItem } from 'common/utils/localStorage';
import rTracking from 'react-tracking';

const PayoutsCard = ({ tracking }) => {
  const [closed, setClosed] = useState(true);
  const handleCardClose = () => {
    setClosed(true);
    setItem('fux-payout-card-closed', true);
    tracking?.trackEvent(window?.rzpQ?.onbr()?.interaction('fux.payouts-card.close'));
  };

  useEffect(() => {
    const isCardClosed = getItem('fux-payout-card-closed') || false;
    setClosed(isCardClosed);
  }, []);

  const assetBase = `${window.cdnBaseUrl}/static/assets/partner-dashboard/fux-cards/commission-guide`;
  const payoutsIcon = `${assetBase}/payouts-card-icon.svg`;

  if (closed) return null;
  return (
    <div className="payouts-card fux-cards-close">
      <i className="fa fa-times close-icon" role="button" onClick={handleCardClose} />
      <div className="card-header">
        <div className="header-bg">
          <img src={payoutsIcon} alt="payout icon" />
        </div>
        <div className="header-title">
          <div> Lightning</div> Commission Payouts
        </div>
      </div>
      <div className="card-body">
        <div className="card-title">
          Understand how you get commissions paid to your bank account
        </div>
        <div className="card-subtext">
          Get commission invoices on 3rd of every month with your commissions transferred to your
          account within a week post invoice generation
        </div>
      </div>
      <div className="card-cta-wrapper">
        <div className="read-more">
          <a
            href="https://razorpay.com/docs/partners/commissions/"
            target="_blank"
            rel="noopener noreferrer"
          >
            Read More <i className="fa fa-arrow-right" />
          </a>
        </div>
      </div>
    </div>
  );
};

export default rTracking(() => window.rzpQ.component('PayoutsCard'))(PayoutsCard);
