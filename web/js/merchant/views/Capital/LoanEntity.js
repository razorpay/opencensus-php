import React, { Component } from 'react';
import FormSectionRenderer from './FormSectionRenderer';
import { connect } from 'react-redux';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';
import ApplicationSummary from './ApplicationSummary';
import HelpSection from './components/HelpSection';
import SideNavigation from './SideNavigation';
import Button from 'common/new-ui/Button';

@connect(
  state => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    fetchLoanApplicationMeta,
  }
)
class LoanEntity extends Component {
  render() {
    const { onClose } = this.props;

    return (
      <div class="loan-details-container">
        <div className="loan-application-modal-header">
          <div className="wrapper">
            <div className="logo">
              <img
                src="/dist/css/assets/capital/capital_logo.svg"
                alt="Loading icon"
              />
            </div>
            <div className="title">Business Loan Application</div>
            <Button.Transparent onClick={onClose}>
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
            onClose={onClose}
          />
          <div className="application-summary">
            <ApplicationSummary />
            <HelpSection
              applicationId={
                this.props.loanApplicationDetails.meta.data.application.id
              }
            />
          </div>
        </div>
      </div>
    );
  }
}

export default LoanEntity;
