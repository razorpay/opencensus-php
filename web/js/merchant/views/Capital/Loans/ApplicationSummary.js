import React, { Component } from 'react';
import { connect } from 'react-redux';
import CircularProgress from 'common/new-ui/CircularProgress';
import {
  getAcceptedOffer,
  fetchCreditOffers,
  changeActiveState,
} from 'merchant/reducers/capital';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import getApplicationProgressPercentage from '../utils/ProgressPercentageCalculator';
import { isPreceedingState } from '../utils';
import {
  APPLICATION_STATE_DESCRIPTIONS,
  APPLICATION_STATES,
  SIDE_NAVIGATION_STATE_GROUPS,
  TENURE_UNIT_LABELS,
  TOOLTIP_DESCRIPTIONS,
} from './constants';

@connect(
  state => ({
    user: state.session.user,
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    getAcceptedOffer,
    fetchCreditOffers,
    changeActiveState,
  }
)
class ApplicationSummary extends Component {
  _getParentStepLabel = step => {
    return Object.values(SIDE_NAVIGATION_STATE_GROUPS).filter(meta =>
      Object.values(meta.steps)
        .reduce((acc, curr) => [...acc, ...curr], [])
        .includes(step)
    )[0].description;
  };

  handleLoanOfferChange = () => {
    const { meta, context } = this.props.loanApplicationDetails;
    const canModify = isPreceedingState(
      meta.data.application.status,
      APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING
    );

    const { activeState } = context;

    this.gaEventDispatcher({
      eventAction: `Right Info | ${canModify ? 'Modify' : 'View More'}`,
      eventLabel: `${this._getParentStepLabel(activeState)}:${
        APPLICATION_STATE_DESCRIPTIONS[activeState].short_description
      }`,
    });
    this.props.changeActiveState('BUSINESS_INFO_PENDING');
  };

  getBusinessSection = () => {
    const { user, loanApplicationDetails } = this.props;
    const { business } = loanApplicationDetails.business_details.data;
    if (business) {
      return (
        <>
          <hr />
          <div className="section">
            <p className="title">{business.legal_name}</p>
            <p className="content">{business.emails[0].email_id}</p>
            <p className="sub-title">Business Details</p>
          </div>
        </>
      );
    } else {
      return (
        <>
          <hr />
          <div className="section">
            <p className="title">{user.business_name}</p>
            <p className="content">{user.email}</p>
            <p className="sub-title">Business Details</p>
          </div>
        </>
      );
    }
  };

  getLoanDetails = () => {
    const { loanApplicationDetails } = this.props;
    const { meta, seed_data, loan_attributes } = loanApplicationDetails;

    if (
      meta.data.application &&
      !meta.data.application.requested_product_attributes &&
      !loan_attributes
    )
      return null;

    const { amount, expected_tenure, credit_request_purpose } =
      meta.data.application &&
      meta.data.application.requested_product_attributes
        ? meta.data.application.requested_product_attributes
        : loan_attributes;

    const seedData = seed_data.data;

    const canModify = isPreceedingState(
      meta.data.application.status,
      APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING
    );

    return [
      <hr />,
      <div className="modify-offer">
        <p>Offer Details</p>
        <a className="text-primary" onClick={this.handleLoanOfferChange}>
          {canModify ? 'Modify' : 'View More'}
        </a>
      </div>,
      <div className="section">
        <p className="title">{seedData.amount_labels[amount]}</p>
        <p className="sub-title">Expected Loan Amount</p>
      </div>,
      <div className="section">
        <p className="title">
          {seedData.tenure_labels[
            meta.data.application.requested_product_attributes
              ? meta.data.application.requested_product_attributes.tenure
              : expected_tenure
          ] || '--'}
        </p>
        <p className="sub-title">Tenure</p>
      </div>,
      ...(credit_request_purpose
        ? [
            <div className="section">
              <p className="title">{credit_request_purpose || '--'}</p>
              <p className="sub-title">Purpose of the Loan</p>
            </div>,
          ]
        : []),
    ];
  };

  gaEventDispatcher = eventObject => {
    //TODO:remove this
    eventObject['eventCategory'] = 'Dashboard - WCL LOS';
    window.rzpAnalytics(eventObject);
  };

  trackMouseOver = type => {
    const { meta } = this.props.loanApplicationDetails;
    this.gaEventDispatcher({
      eventAction: `TOOLTIP | ${type.toUpperCase()}`,
      eventLabel: `Right Info | ${getApplicationProgressPercentage(
        meta.data.application.status
      )}`,
    });
  };

  getAcceptedOfferDetails = () => {
    const {
      accepted_offer_details,
      credit_offer_details,
      meta,
    } = this.props.loanApplicationDetails;

    if (credit_offer_details.data && !credit_offer_details.data.credit_offers) {
      this.props.getAcceptedOffer({
        application_id: meta.data.application.id,
      });
    }

    if (
      accepted_offer_details.data &&
      !accepted_offer_details.data.credit_offer_id
    ) {
      this.props.fetchCreditOffers({
        application_id: meta.data.application.id,
      });
    }

    if (
      !accepted_offer_details.data ||
      !accepted_offer_details.data.credit_offer_id ||
      !credit_offer_details.data ||
      !credit_offer_details.data.credit_offers
    ) {
      return [
        <hr />,
        ...[1, 2, 3].map(_ => (
          <div className="section">
            <p className="title PlaceholderLoader" />
            <p className="sub-title PlaceholderLoader" />
          </div>
        )),
      ];
    }

    const creditOffer = credit_offer_details.data.credit_offers.find(
      offer => offer.id === accepted_offer_details.data.credit_offer_id
    );

    return [
      <hr />,
      <div className="section">
        <p className="title">
          <Amount value={creditOffer.loan_attributes.credit_offered} />
        </p>
        <p className="sub-title">Total Loan Amount</p>
      </div>,
      <div className="section">
        <p className="title">
          {creditOffer.installment.tenure} &nbsp;
          {creditOffer.installment.tenure === 1
            ? TENURE_UNIT_LABELS[creditOffer.installment.tenure_unit][0]
            : TENURE_UNIT_LABELS[creditOffer.installment.tenure_unit][1]}
        </p>
        <p className="sub-title">Tenure</p>
      </div>,
      <div className="section">
        <p className="title">{creditOffer.loan_attributes.interest_rate}%</p>
        <p className="sub-title">Interest Rate</p>
      </div>,
      <div class="section-group">
        <div className="section">
          <p className="title">
            <Amount value={creditOffer.installment.amount * 7} />
          </p>
          <p className="sub-title">
            EWI
            <small className="help-content" style={{ paddingLeft: '4px' }}>
              <i
                className="i i-info-outline"
                onMouseOver={() => this.trackMouseOver('ewi')}
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
        </div>
        <div class="divider" />
        <div className="section">
          <p className="title">
            <Amount value={creditOffer.installment.amount} />
          </p>
          <p className="sub-title">
            EDI
            <small className="help-content" style={{ paddingLeft: '4px' }}>
              <i
                className="i i-info-outline"
                onMouseOver={() => this.trackMouseOver('edi')}
              />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div style={{ textAlign: 'left' }}>
                    {TOOLTIP_DESCRIPTIONS['edi']}
                  </div>
                </PopoverBody>
              </Popover>
            </small>
          </p>
        </div>
      </div>,
    ];
  };

  isOfferAccepted = () => {
    const { meta } = this.props.loanApplicationDetails;
    return !isPreceedingState(
      meta.data.application.status,
      APPLICATION_STATES.NACH_CREATION_PENDING
    );
  };

  render() {
    const { meta } = this.props.loanApplicationDetails;

    return (
      <div class="summary__wrapper">
        <div class="progress-container flex">
          <CircularProgress
            progress={getApplicationProgressPercentage(
              meta.data.application.status
            )}
            size={100}
            helpMsg={'completed'}
          />
        </div>
        {this.isOfferAccepted() ? (
          this.getAcceptedOfferDetails()
        ) : (
          <React.Fragment>
            {this.getLoanDetails()}
            {this.getBusinessSection()}
          </React.Fragment>
        )}
      </div>
    );
  }
}

export default ApplicationSummary;
