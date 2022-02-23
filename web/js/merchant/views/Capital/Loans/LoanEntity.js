import React, { Component } from 'react';
import FormSectionRenderer from './FormSectionRenderer';
import { connect } from 'react-redux';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';
import ApplicationSummary from './ApplicationSummary';
import HelpSection from '../components/HelpSection';
import SideNavigation from './SideNavigation';
import Button from 'common/new-ui/Button';
import getApplicationProgressPercentage from '../utils/ProgressPercentageCalculator';
import { isCashAdvanceProduct } from '../utils';

@connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    fetchLoanApplicationMeta,
  },
)
class LoanEntity extends Component {
  _getParentStepLabel = (step) => {
    const { loanApplicationDetails } = this.props;
    const { meta } = loanApplicationDetails;
    return Object.values(meta.configuration.getSideNavigationStateGroups()).filter((meta) =>
      Object.values(meta.steps)
        .reduce((acc, curr) => [...acc, ...curr], [])
        .includes(step),
    )[0].description;
  };

  handleClose = () => {
    const { onClose, loanApplicationDetails } = this.props;
    const {
      meta,
      context: { activeState },
    } = loanApplicationDetails;

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction: 'Application | Save&Close',
      eventLabel: `${this._getParentStepLabel(activeState)}:${
        meta.configuration.getApplicationStateDescriptions()[activeState].short_description
      } | ${getApplicationProgressPercentage(
        meta.data.application.status,
        meta.configuration.getApplicationStateGroups(),
      )}%`,
    });
    onClose();
  };

  sendDataToAnalytics = ({ status, majorStepTitle = '', cta = '', subpage = null, ...rest }) => {
    const {
      meta,
      context: { activeState },
    } = this.props.loanApplicationDetails;
    // eslint-disable-next-line no-unused-vars
    const currentStatus = status || activeState;

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction: `Landing Steps | ${cta}`,
      eventLabel: `${subpage ? `${subpage} | ` : ''}${
        meta.configuration.getApplicationStateDescriptions()[status].short_description
      } | ${majorStepTitle ? `${majorStepTitle} | ` : ''}${getApplicationProgressPercentage(
        meta.data.application.status,
        meta.configuration.getApplicationStateGroups(),
      )}%`,
      ...rest,
    });
  };

  render() {
    const { meta, context } = this.props.loanApplicationDetails;

    return (
      <div class="loan-details-container">
        <div className="loan-application-modal-header">
          <div className="wrapper">
            <div className="logo">
              <img src="/dist/css/assets/capital/capital_logo.svg" alt="Loading icon" />
            </div>
            <div className="title">
              {isCashAdvanceProduct(meta.product)
                ? 'Cash Advance Application'
                : 'Business Loan Application'}
            </div>
            <Button.Transparent onClick={this.handleClose}>
              Close
              <i className="i i-close" />
            </Button.Transparent>
          </div>
        </div>
        <div className="loan-application-modal-body">
          <div className="application-status-overview">
            <SideNavigation />
          </div>
          <FormSectionRenderer
            loanApplicationDetails={this.props.loanApplicationDetails}
            productDetails={this.props.productDetails}
            sendDataToAnalytics={this.sendDataToAnalytics}
            onClose={this.handleClose}
          />
          <div className="application-summary">
            <ApplicationSummary />
            <HelpSection
              applicationId={meta.data.application.id}
              activeState={context.activeState}
              currentState={meta.data.application.status}
              applicationConfiguration={meta.configuration}
              product={meta.product}
            />
          </div>
        </div>
      </div>
    );
  }
}

export default LoanEntity;
