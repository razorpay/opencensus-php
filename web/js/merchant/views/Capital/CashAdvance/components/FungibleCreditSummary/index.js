import React from 'react';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import CardsDashboardRedirectionModal from '../CardsDashboardRedirectionModal';
import { getXCardsBaseURL } from '../../../utils/index';
import './style.styl';

export default function FungibleCreditSummary({ openModal, closeModal, data, isMerchantNew }) {
  const openRedirectModal = () => {
    if (isMerchantNew) {
      openModal({
        component: <CardsDashboardRedirectionModal closeModal={closeModal} />,
        size: 'large',
      });
    } else {
      window.open(getXCardsBaseURL());
    }
  };

  return (
    <div className="credit-summary-wrapper">
      <div className="amount-wrapper-credit-summary">
        <div className="large-amount">
          <Amount
            className="available-balance-amount"
            value={data?.cash_advance?.available_balance || 0}
            parentQuerySelector=".withdrawals__top-summary"
          />
        </div>
      </div>
      <div className="available-balance-text">
        <span>Available cash balance </span>
        <span>
          <img
            className="info-icon"
            src={`${window.cdnBaseUrl}/static/assets/capital/cash_on_card/info_blue.svg`}
            alt="info icon"
          />
          <Popover className="info-popover" align="bottom" theme="dark">
            <PopoverBody>
              <div className="fungible-tooltip flex">
                <div className="symbols">=</div>
                <div className="flex-col text-box-wrapper">
                  <Amount
                    className="top-part tooltip-amount-text"
                    value={data?.cash_advance?.limit || 0}
                  />
                  <div className="bottom-part">Cash Limit</div>
                </div>
                <div className="symbols">-</div>
                <div className="flex-col text-box-wrapper">
                  <Amount
                    className="top-part  tooltip-amount-text"
                    value={data?.cash_advance?.outstanding_balance || 0}
                  />
                  <div className="bottom-part">Total Withdrawn</div>
                </div>
              </div>
            </PopoverBody>
          </Popover>
        </span>
      </div>
      <div className="total-credit-text">
        Total Credit Limit &nbsp;
        <Amount
          value={data?.cash_advance?.limit || 0}
          parentQuerySelector=".withdrawals__top-summary"
        />
      </div>
      <div className="horizontal-divider" />
      <div className="credit-summary-footer" onClick={openRedirectModal}>
        <img
          className="card-icon"
          src={`${window.cdnBaseUrl}/static/assets/capital/cash_on_card/card_icon.svg`}
          alt="card icon"
        />
        <Button.Transparent>Corporate Card Dashboard</Button.Transparent>
        <img
          className="redirect-icon"
          src={`${window.cdnBaseUrl}/static/assets/capital/cash_on_card/redirect.svg`}
          alt="redirect icon"
        />
      </div>
    </div>
  );
}
