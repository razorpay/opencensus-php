import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import { calculatePercentageAmount, getDisbursalAmount } from '../utils';
import { TENURE_UNIT_LABELS, TOOLTIP_DESCRIPTIONS } from '../Loans/constants';

const CreditOffer = ({
  offerDetails,
  approved = false,
  showInstallmentDetails = true,
  isDisbursal = false,
  disbursedAmount,
  trackGAEvents = true,
  _fromWhere,
}) => {
  const { loan_attributes, installment, charges } = offerDetails;

  const offer = {
    ...loan_attributes,
    ...installment,
    ...charges,
  };

  const trackMouseOver = type => {
    if (!trackGAEvents) return;

    window.rzpAnalytics({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction: `TOOLTIP | ${type.toUpperCase()}`,
      eventLabel: `${_fromWhere}`,
    });
  };

  const {
    credit_offered,
    processing_fee_percentage,
    tax_percentage,
    tenure: installment_tenure,
    tenure_unit: installment_tenure_unit,
    interest_rate,
    amount: installment_amount,
  } = offer;
  return (
    <div className={`loan-offer-details-wrapper ${approved ? 'approved' : ''}`}>
      <div
        className={`section loan-amount-details-wrapper ${
          approved ? 'approved' : ''
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
            <p className="loan-offer-detail-title">Total Loan Amount</p>
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
            <Amount
              value={calculatePercentageAmount(
                processing_fee_percentage,
                credit_offered
              )}
            />
          </p>
        </div>
        <div className="section">
          <p className="loan-offer-detail-title">Taxes(Including GST)</p>
          <p className="loan-offer-value">
            <Amount
              value={calculatePercentageAmount(
                tax_percentage,
                calculatePercentageAmount(
                  processing_fee_percentage,
                  credit_offered
                )
              )}
            />
          </p>
        </div>
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
                    tax_percentage
                  )}
                />
              </p>
            </React.Fragment>
          )}
        </div>
      </div>
      {showInstallmentDetails && (
        <React.Fragment>
          <hr />
          <div className="section loan-offer-summary-section">
            <div className="loan-offer-summary-wrapper">
              <p className="loan-offer-summary-title">Rate of Interest</p>
              <p className="loan-offer-value">{interest_rate}%</p>
            </div>
            <vr />
            <div className="loan-offer-summary-wrapper">
              <p className="loan-offer-summary-title">
                Tenure
                <small className="help-content" style={{ paddingLeft: '4px' }}>
                  <i
                    className="i i-info-outline"
                    onMouseOver={() => trackMouseOver('tenure')}
                  />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div style={{ textAlign: 'left' }}>
                        {TOOLTIP_DESCRIPTIONS['tenure']}
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
                EWI
                <small className="help-content" style={{ paddingLeft: '4px' }}>
                  <i
                    className="i i-info-outline"
                    onMouseOver={() => trackMouseOver('ewi')}
                  />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div style={{ textAlign: 'left' }}>
                        {TOOLTIP_DESCRIPTIONS['ewi']}
                      </div>
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
    </div>
  );
};

export default CreditOffer;
