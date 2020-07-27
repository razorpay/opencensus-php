import React from 'react';
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import DataList from 'merchant/components/OnBoarding/Slides/DataList';
import {
  changeActiveState,
  fetchLoanApplicationMeta,
  fetchSeedData,
  getApplications,
  getLenderDetails,
  registerNewLoanApplication,
} from 'merchant/reducers/capital';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import LoanEntity from './LoanEntity';
import ApplicationStatusOverview from './ApplicationStatusOverview';
import CircularProgress from 'common/new-ui/CircularProgress';
import getApplicationProgressPercentage from '../utils/ProgressPercentageCalculator';
import ApplicationOverviewLoadingSkeleton from '../components/ApplicationOverviewLoadingSkeleton';
import { APPLICATION_STATE_DESCRIPTIONS, CAPITAL_LINKS } from './constants';

export const PROS = [
  <React.Fragment>
    <i className="i i-bullet" />
    <span>Get competitive interest rates for your risk profile</span>
  </React.Fragment>,
  <React.Fragment>
    <i class="i i-bullet" />
    <span>Apply online in 5 minutes with support when you need</span>
  </React.Fragment>,
  <React.Fragment>
    <i class="i i-bullet" />
    <span>Repay easily from daily settlements with more options</span>
  </React.Fragment>,
];

@withRouter
@connect(
  state => ({
    user: state.session.user,
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    fetchSeedData,
    fetchLoanApplicationMeta,
    registerNewLoanApplication,
    openModal,
    closeModal,
    changeActiveState,
    getApplications,
    getLenderDetails,
  }
)
class LoanApplicationOverview extends React.Component {
  state = {
    applications: [],
  };

  gaEventDispatcher = eventObject => {
    eventObject['eventCategory'] = 'Dashboard - WCL LOS';
    window.rzpAnalytics(eventObject);
  };

  componentDidMount() {
    this.props
      .getApplications({
        owner_type: 'MERCHANT',
        owner_id: this.props.user.current,
      })
      .then(res => {
        if (res && !res.errors && res.data.applications) {
          this.setState({
            applications: res.data.applications,
          });
          const activeApplications = res.data.applications.filter(
            application =>
              application.status !== 'RZP_REJECTED' &&
              application.status !== 'CLOSED'
          );
          if (activeApplications.length > 0 && activeApplications[0].id) {
            this.fetchApplicationDetails(activeApplications[0].id);
          } else {
            this.props.registerNewLoanApplication();
          }
        } else {
          this.props.registerNewLoanApplication();
        }
      })
      .catch(_ => {
        this.props.registerNewLoanApplication();
      });
    this.fetchSeedData();
  }

  fetchApplicationDetails = id => {
    if (id && id !== 'new') {
      this.props.fetchLoanApplicationMeta(id);
    }
  };

  fetchSeedData = () => {
    this.props.fetchSeedData();
  };

  _getToBeRenderedState = () => {
    const { meta, context } = this.props.loanApplicationDetails;
    const defaultState = 'NOT_STARTED';
    if (context) {
      return context.activeState
        ? context.activeState
        : meta.data.application
        ? meta.data.application.status
        : defaultState;
    } else {
      return meta.data.application
        ? meta.data.application.status
        : defaultState;
    }
  };

  handleModalClose = () => {
    const tobeRenderedState = this._getToBeRenderedState();
    const activeStepLabel = APPLICATION_STATE_DESCRIPTIONS[tobeRenderedState];
    this.gaEventDispatcher({
      eventAction: 'Top | Save & Close',
      eventLabel: `${activeStepLabel} | ${this.getProgressPercentage()}%`,
    });
    this.props.closeModal();
  };

  openLoanEntity = (applicationId, state, _targetStepTitle, _cta) => {
    if (state) {
      this.props.changeActiveState(state);
      this.gaEventDispatcher({
        eventAction: `Landing Steps | ${_cta}`,
        eventLabel: `${_targetStepTitle} | ${
          APPLICATION_STATE_DESCRIPTIONS[state].short_description
        } | ${this.getProgressPercentage()}% | ${
          this.state.applications.length
        }`,
      });
    } else {
      const { meta } = this.props.loanApplicationDetails;
      const status = meta.data.application.status;
      this.props.changeActiveState(status);
      this.gaEventDispatcher({
        eventAction: `Landing Steps | ${_cta}`,
        eventLabel: `${_targetStepTitle} | ${
          APPLICATION_STATE_DESCRIPTIONS[status].short_description
        } | ${this.getProgressPercentage()}% | ${
          this.state.applications.length
        }`,
      });
    }
    this.fetchApplicationDetails(applicationId);
    this.props.openModal({
      size: 'full-screen',
      component: (
        <LoanEntity
          onClose={this.handleModalClose}
          applicationId={applicationId}
        />
      ),
      style: {
        content: {
          top: 0,
          bottom: 0,
        },
      },
    });
  };

  getProgressPercentage = () => {
    const { meta } = this.props.loanApplicationDetails;
    if (!meta.data.application.status) return 0;
    return getApplicationProgressPercentage(meta.data.application.status);
  };

  render() {
    const { loanApplicationDetails } = this.props;

    return (
      <OnBoardingWrapper class="Loans">
        <div className="Landing--Image">
          <div class="image-wrapper">
            <img
              src={'/dist/css/assets/capital/los_onboarding_hero.svg'}
              alt="landing-image"
            />
          </div>
        </div>

        <div className="Product--Details">
          <div className="Details-title">
            Business Loans for you
            <div className="divider" />
          </div>

          <div className="Details-desc">
            Achieve your goals by financing your business needs effectively. Get
            a collateral-free Working Capital Loan in as fast as two days.
          </div>

          <div className="Details-desc privileges">
            As a privileged member of Razorpay, you get the following benefits:
          </div>

          <DataList>{PROS}</DataList>
        </div>

        <div className="loan-application-home">
          {!loanApplicationDetails.meta.data.application ? (
            <ApplicationOverviewLoadingSkeleton />
          ) : (
            <div className="status-overview">
              {loanApplicationDetails.meta.data.application.owner_id && (
                <div className="loan-application-overview-header flex">
                  <div class="loan-meta-wrapper">
                    <h4>
                      <strong>Your Loan Application</strong>
                    </h4>
                    <p className="text--secondary">
                      Application ID:{' '}
                      {loanApplicationDetails.meta.data.application.id}
                    </p>
                  </div>
                  <div className="loan-application-progress-wrapper flex">
                    <CircularProgress
                      progress={this.getProgressPercentage()}
                      size={22}
                      showPercentage={false}
                    />
                    <div className="m-l">
                      <h4 className="no-margin">
                        <strong>{this.getProgressPercentage()}%</strong>
                        &nbsp;
                        <span class="text-small">Completed</span>
                      </h4>
                    </div>
                  </div>
                </div>
              )}
              <div>
                <ApplicationStatusOverview
                  openLoanEntity={this.openLoanEntity}
                />
              </div>
            </div>
          )}
          <div className="footer">
            <div className="btn-toolbar">
              <a
                className="link m-r m-l"
                href={CAPITAL_LINKS['check_credit_score']}
              >
                <i className="i i-lightbulb" />
                <strong>Check free Credit Report</strong>
              </a>
              <a
                className="m-l link"
                href={CAPITAL_LINKS['faqs']}
                target="_blank"
              >
                <strong>Show FAQ's</strong>
                <i className="i i-question-circle-o m-l" />
              </a>
            </div>
            <img
              src="/dist/css/assets/capital/capital_logo.svg"
              alt="Loading icon"
            />
          </div>
        </div>
      </OnBoardingWrapper>
    );
  }
}

export default LoanApplicationOverview;
