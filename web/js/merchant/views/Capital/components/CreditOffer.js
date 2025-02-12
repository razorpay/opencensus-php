import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import {
  calculatePercentageAmount,
  getDisbursalAmount,
  isCashAdvanceProduct,
  isLoanProduct,
} from '../utils';
import {
  CAPITAL_PRODUCT_CODES,
  TENURE_UNIT_LABELS,
  TOOLTIP_DESCRIPTIONS,
} from '../Loans/constants';

const getOfferData = (offerDetails, product) => {
  if (!offerDetails) return {};
  if (product === CAPITAL_PRODUCT_CODES.LOAN) {
    const offer = {
      ...offerDetails.loan_attributes,
      ...offerDetails.installment,
      ...offerDetails.charges,
    };
    return {
      credit_offered: offer.credit_offered,
      processing_fee_percentage: offer.processing_fee_percentage,
      tax_percentage: offer.tax_percentage,
      installment_tenure: offer.tenure,
      installment_tenure_unit: offer.tenure_unit,
      interest_rate: offer.interest_rate,
      installment_amount: offer.amount,
    };
  } else if (product === CAPITAL_PRODUCT_CODES.CASH_ADVANCE) {
    return {
      credit_offered: offerDetails.max_credit_offered,
      processing_fee_percentage: offerDetails.processing_fee_percentage,
      tax_percentage: offerDetails.tax_percentage,
      installment_tenure: offerDetails.tenure,
      installment_tenure_unit: offerDetails.tenure_type,
      interest_rate: offerDetails.interest_rate_daily,
    };
  } else {
    return {};
  }
};

const CreditOffer = ({
  offerDetails,
  approved = false,
  showInstallmentDetails = true,
  isDisbursal = false,
  disbursedAmount,
  trackGAEvents = true,
  _fromWhere,
  product,
  highlightCreditAmount = true,
}) => {
  const trackMouseOver = (type) => {
    if (!trackGAEvents) return;

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction: `TOOLTIP | ${type.toUpperCase()}`,
      eventLabel: `${_fromWhere}`,
    });
  };

  const {
    credit_offered,
    processing_fee_percentage = 0,
    tax_percentage = 0,
    installment_tenure,
    installment_tenure_unit,
    interest_rate,
    installment_amount,
  } = getOfferData(offerDetails, product);

  return (
    <div className={`loan-offer-details-wrapper ${approved ? 'approved' : ''}`}>
      <div
        className={`section loan-amount-details-wrapper ${
          highlightCreditAmount ? 'highlight' : 'no-highlight'
        }`}
      >
        {isDisbursal ? (
          <React.Fragment>
            <p className="loan-offer-detail-title">Net Disbursal Amount</p>
            <p className="loan-offer-amount">
              <Amount value={disbursedAmount} />
            </p>
          </React.Fragment>
        ) : (
          <React.Fragment>
            <p className="loan-offer-detail-title">
              {isLoanProduct(product) ? 'Total Loan Amount' : 'Credit Limit'}
            </p>
            <p className="loan-offer-amount">
              <Amount value={credit_offered} />
            </p>
          </React.Fragment>
        )}
      </div>
      <hr />
      <div className="loan-offer-details">
        <div className="section">
          <p className="loan-offer-detail-title">Processing Fee</p>
          <p className="loan-offer-value">
            <Amount value={calculatePercentageAmount(processing_fee_percentage, credit_offered)} />
          </p>
        </div>
        <div className="section">
          <p className="loan-offer-detail-title">Taxes(Including GST)</p>
          <p className="loan-offer-value">
            <Amount
              value={calculatePercentageAmount(
                tax_percentage,
                calculatePercentageAmount(processing_fee_percentage, credit_offered),
              )}
            />
          </p>
        </div>
        {isLoanProduct(product) && (
          <div className="section">
            {isDisbursal ? (
              <React.Fragment>
                <p className="loan-offer-detail-title">Total Loan Amount</p>
                <p className="loan-offer-value">
                  <Amount value={credit_offered} />
                </p>
              </React.Fragment>
            ) : (
              <React.Fragment>
                <p className="loan-offer-detail-title">Net Disbursal Amount</p>
                <p className="loan-offer-value">
                  <Amount
                    value={getDisbursalAmount(
                      credit_offered,
                      processing_fee_percentage,
                      tax_percentage,
                    )}
                  />
                </p>
              </React.Fragment>
            )}
          </div>
        )}
      </div>
      {showInstallmentDetails && isLoanProduct(product) && (
        <React.Fragment>
          <hr />
          <div className="section loan-offer-summary-section">
            <div className="loan-offer-summary-wrapper">
              <p className="loan-offer-summary-title">Rate of Interest</p>
              <p className="loan-offer-value">{interest_rate / 100}%</p>
            </div>
            <vr />
            <div className="loan-offer-summary-wrapper">
              <p className="loan-offer-summary-title">
                Tenure&nbsp;
                <small className="help-content">
                  <i className="i i-info-outline" onMouseOver={() => trackMouseOver('tenure')} />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div className="text-left">
                        {isCashAdvanceProduct(product)
                          ? TOOLTIP_DESCRIPTIONS.ca_tenure
                          : TOOLTIP_DESCRIPTIONS.tenure}
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </p>
              {/*TODO:put this in constants*/}
              <p className="loan-offer-value no-padding">
                {installment_tenure} &nbsp;
                {installment_tenure === 1
                  ? TENURE_UNIT_LABELS[installment_tenure_unit][0]
                  : TENURE_UNIT_LABELS[installment_tenure_unit][1]}
              </p>
            </div>
            <vr />
            <div className="loan-offer-summary-wrapper">
              <p className="loan-offer-summary-title">
                EWI&nbsp;
                <small className="help-content">
                  <i className="i i-info-outline" onMouseOver={() => trackMouseOver('ewi')} />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div className="text-left">{TOOLTIP_DESCRIPTIONS.ewi}</div>
                    </PopoverBody>
                  </Popover>
                </small>
              </p>
              <p className="loan-offer-value no-padding">
                <Amount value={installment_amount * 7} />
              </p>
            </div>
          </div>
        </React.Fragment>
      )}

      {showInstallmentDetails && isCashAdvanceProduct(product) && (
        <React.Fragment>
          <hr />
          <div className="section loan-offer-summary-section">
            <div className="loan-offer-summary-wrapper no-border">
              <p className="loan-offer-summary-title">Rate of Interest</p>
              <p className="loan-offer-value">{interest_rate / 100}% per day</p>
            </div>
            <div className="loan-offer-summary-wrapper">
              <p className="loan-offer-summary-title">
                Tenure
                <small className="help-content">
                  <i className="i i-info-outline" onMouseOver={() => trackMouseOver('tenure')} />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div style={{ textAlign: 'left' }}>{TOOLTIP_DESCRIPTIONS.tenure}</div>
                    </PopoverBody>
                  </Popover>
                </small>
              </p>
              {/*TODO:put this in constants*/}
              <p className="loan-offer-value no-padding">
                {installment_tenure} &nbsp;
                {installment_tenure === 1
                  ? TENURE_UNIT_LABELS[installment_tenure_unit][0]
                  : TENURE_UNIT_LABELS[installment_tenure_unit][1]}
              </p>
            </div>
          </div>
        </React.Fragment>
      )}
    </div>
  );
};

export default CreditOffer;
