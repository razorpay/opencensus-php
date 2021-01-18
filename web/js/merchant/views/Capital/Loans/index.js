import React from 'react';
import { connect } from 'react-redux';
import { Redirect, withRouter } from 'react-router-dom';
import getApplicationProgressPercentage from '../utils/ProgressPercentageCalculator';
import ApplicationOverviewLoadingSkeleton from '../components/ApplicationOverviewLoadingSkeleton';
import { isCashAdvanceProduct, isLoanProduct } from '../utils';
import Spinner from '../components/Spinner';
import ApplicationOnboardingForm from './Forms/ApplicationOnboardingForm';
import ApplicationStatusOverview from './ApplicationStatusOverview';
import LoanEntity from './LoanEntity';
import {
  CAPITAL_LINKS,
  CAPITAL_PRODUCT_NAME_CODE_MAP,
  HOTJAR_TRIGGERS,
  TOOLTIP_DESCRIPTIONS,
  GA_CATEGORY_BY_PRODUCT,
} from './constants';
import EditPanModal from './EditPanModal';
import CircularProgress from 'common/new-ui/CircularProgress';
import Button from 'common/new-ui/Button';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import {
  changeActiveState,
  fetchLoanApplicationMeta,
  fetchSeedData,
  getApplications,
  getLenderDetails,
  registerNewLoanApplication,
  fetchProducts,
  registerProduct,
  resetCapitalLendingData,
} from 'merchant/reducers/capital';
import DataList from 'merchant/components/OnBoarding/Slides/DataList';
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import Popover, { PopoverBody } from 'common/ui/Popover';

export const PROS = [
  <React.Fragment key={1}>
    <i className="i i-bullet" />
    <span>Get competitive interest rates for your risk profile</span>
  </React.Fragment>,
  <React.Fragment key={2}>
    <i class="i i-bullet" />
    <span>Apply online in 5 minutes with support when you need</span>
  </React.Fragment>,
  <React.Fragment key={3}>
    <i class="i i-bullet" />
    <span>Repay easily from daily settlements with more options</span>
  </React.Fragment>,
];

@withRouter
@connect(
  (state) => ({
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
    fetchProducts,
    registerProduct,
    resetCapitalLendingData,
  },
)
class LoanApplicationOverview extends React.Component {
  constructor() {
    super();
    this.state = {
      applications: [],
    };
    this.onLoadHandlers = [];
  }

  gaEventDispatcher = (eventObject) => {
    const { state: { eventCategory = null } = {} } = this.props.location;
    eventObject.eventCategory = eventCategory
      ? eventCategory
      : GA_CATEGORY_BY_PRODUCT[this.getProductCode()];
    window.rzpAnalytics(eventObject);
  };

  componentDidMount() {
    this.props.closeModal();
    this.initApplication(this.getProductCode());
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOAN_APPLICATION_PAGE_OPEN);
  }

  initApplication = (product) => {
    this.setState({
      applicantPan: this.props.user.promoter_pan,
      availablePans: [this.props.user.promoter_pan],
    });
    const searchParams = this.props.history.location.search;

    this.validateProduct();
    this.validateFeatureAccess();
    this.fetchSeedData();
    this.props.resetCapitalLendingData();
    this.props.registerProduct(product);
    this.props.fetchProducts().then(this.fetchApplications);

    if (searchParams) {
      const params = new URLSearchParams(searchParams);
      const action = params.get('action');
      this.onLoadHandlers.push((activeApplication) => {
        if (action === 'open') {
          this.openLoanEntity();
        }
      });
    }
  };

  getProduct = () => {
    return this.props.match.params.product;
  };

  getProductCode = () => {
    return CAPITAL_PRODUCT_NAME_CODE_MAP[this.getProduct()];
  };

  redirectToHome = () => {
    this.props.history.push('/');
  };

  validateProduct = () => {
    const allowedProducts = ['LOAN', 'LOC'];
    const productCode = this.getProductCode();
    if (!productCode || !allowedProducts.includes(productCode)) this.redirectToHome();
  };

  validateFeatureAccess = () => {
    const { history, user } = this.props;

    const params = new URLSearchParams(history.location.search);
    const action = params.get('action');

    // When cash-advance is clicked in the left nav, we take the user to
    // for cash-advance application page. But if the user's application
    // process is already completed user will land on cash-advance/withdrawals
    // page.
    // But even after loan application completion, in some cases user might want
    // to see the application details for some reason. Ideally we must show the
    // application instead of redirecting user to cash-advance because the
    // application process is completed. Hence check url params to validate
    // before redirection.
    if (isCashAdvanceProduct(this.getProductCode()) && user.isWithdrawFeatureEnabled && !action) {
      this.redirectToCashAdvanceHome();
    }
    if (isCashAdvanceProduct(this.getProductCode()) && user.isCashAdvanceStage2Enabled && !action) {
      this.redirectToCashAdvanceHome();
    }
    if (isCashAdvanceProduct(this.getProductCode()) && !(user.isLOCEnabled && user.isLOSEnabled)) {
      return this.redirectToHome();
    }
    if (isLoanProduct(this.getProductCode()) && !user.isLoansEnabled) {
      return this.redirectToHome();
    }
  };

  redirectToCashAdvanceHome() {
    this.props.history.push('/capital/cash-advance/');
  }

  fetchApplications = () => {
    const productDetails = this.getProductDetails();

    if (!productDetails) this.redirectToHome();

    this.props
      .getApplications({
        owner_type: 'MERCHANT',
        owner_id: this.props.user.current,
        product_id: productDetails.id,
      })
      .then((res) => {
        if (res && !res.errors && res.data.applications) {
          this.setState({
            applications: res.data.applications,
          });
          const activeApplications = res.data.applications.filter(
            (application) =>
              application.status !== 'RZP_REJECTED' && application.status !== 'CLOSED',
          );
          if (activeApplications.length > 0 && activeApplications[0].id) {
            this.fetchApplicationDetails(activeApplications[0].id).then(() => {
              this.onLoadHandlers.forEach((callback) => {
                callback(activeApplications[0]);
              });
            });
          } else {
            this.props.registerNewLoanApplication();
          }
        } else {
          this.props.registerNewLoanApplication();
        }
      })
      .catch((_) => {
        this.props.registerNewLoanApplication();
      });
  };

  fetchApplicationDetails = (id) => {
    if (id && id !== 'new') {
      return this.props.fetchLoanApplicationMeta(id);
    }
  };

  fetchSeedData = () => {
    this.props.fetchSeedData();
  };

  getProductDetails = () => {
    return this.props.loanApplicationDetails.products.data.find(
      (p) => p.name === this.getProductCode(),
    );
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
      return meta.data.application ? meta.data.application.status : defaultState;
    }
  };

  handleModalClose = () => {
    const { meta } = this.props.loanApplicationDetails;
    const tobeRenderedState = this._getToBeRenderedState();
    const activeStepLabel = meta.configuration.getApplicationStateDescriptions()[tobeRenderedState];
    this.gaEventDispatcher({
      eventAction: 'Top | Save & Close',
      eventLabel: `${activeStepLabel} | ${this.getProgressPercentage()}%`,
    });
    this.props.closeModal();
  };

  openLoanEntity = (applicationId, state, _targetStepTitle, _cta) => {
    const { meta } = this.props.loanApplicationDetails;
    const currentStatus = state ? state : meta.data.application.status;

    this.gaEventDispatcher({
      eventAction: `Landing Steps | ${_cta}`,
      eventLabel: `${
        meta.configuration.getApplicationStateDescriptions()[currentStatus].short_description
      } | ${_targetStepTitle} | ${this.getProgressPercentage()}% | ${
        this.state.applications.length
      }`,
    });

    this.props.changeActiveState(currentStatus);
    this.fetchApplicationDetails(applicationId);
    this.props.openModal({
      size: 'full-screen',
      component: <LoanEntity onClose={this.handleModalClose} applicationId={applicationId} />,
      style: {
        content: {
          top: 0,
          bottom: 0,
        },
      },
      className: 'loan-application-modal',
    });
  };

  getProgressPercentage = () => {
    const { meta } = this.props.loanApplicationDetails;
    if (!meta.data.application.status) return 0;
    return getApplicationProgressPercentage(
      meta.data.application.status,
      meta.configuration.getApplicationStateGroups(),
    );
  };

  isPANLinkedWithPG = () => this.props.user.promoter_pan === this.state.applicantPan;

  registerCoApplicantPan = (selectedPAN) => {
    this.props.closeModal();
    this.setState((prevState) => {
      const personalPANNumberSet = new Set([]);
      prevState.availablePans.forEach((panNumber) => personalPANNumberSet.add(panNumber));
      personalPANNumberSet.add(selectedPAN);
      return {
        applicantPan: selectedPAN,
        availablePans: Array.from(personalPANNumberSet),
      };
    });
  };

  handleCoApplicantPan = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <EditPanModal
          handleSubmit={this.registerCoApplicantPan}
          closeModal={this.props.closeModal}
          availablePans={this.state.availablePans}
          selected={this.state.applicantPan}
          pgLinkedPan={this.props.user.promoter_pan}
          parentSelector=".add-applicant-pan-modal"
        />
      ),
      className: 'modal-white-background add-applicant-pan-modal',
    });
  };

  getApplicationOverview = () => {
    const { loanApplicationDetails } = this.props;
    const { contact_email, promoter_pan } = this.props.user;

    const productDetails = this.getProductDetails();

    if (!productDetails) console.error('No Corresponding Product found');

    if (!loanApplicationDetails.meta.data.application) {
      return (
        <div className="status-overview">
          <div className="loan-application-overview-header onboarding-header flex">
            <div className="loan-meta-wrapper">
              <h4>
                <strong>Check Eligibility</strong>
              </h4>
              <span class="text-strong text-faded">{contact_email}</span>
            </div>
            <div className="personal-pan-summary flex">
              <div className="left-section">
                <p className="text-strong text-faded">PAN Number</p>
                <span className="pan-info text-strong">
                  {this.state.applicantPan}
                  {!this.isPANLinkedWithPG() && (
                    <small className="help-content">
                      &nbsp;
                      <i className="i i-info-outline" />
                      <Popover align="top" theme="dark">
                        <PopoverBody>
                          <div class="text-left">
                            This PAN Number is different from the one connected with the Payment
                            Gateway.
                          </div>
                        </PopoverBody>
                      </Popover>
                    </small>
                  )}
                </span>
              </div>
              <div>
                <span>
                  We verify the details with your central PAN database, So please ensure to enter
                  the correct details.&nbsp;
                  <Button.Transparent onClick={this.handleCoApplicantPan}>
                    Apply using different PAN
                  </Button.Transparent>
                </span>
              </div>
            </div>
          </div>
          <div class="loan-onboarding-content-body">
            <ApplicationOnboardingForm
              productId={productDetails.id}
              applicantPan={this.state.applicantPan}
            />
          </div>
        </div>
      );
    }

    return (
      <div className="status-overview">
        {loanApplicationDetails.meta.data.application.owner_id && (
          <div className="loan-application-overview-header flex">
            <div className="loan-meta-wrapper">
              <h4>
                <strong>Your Loan Application</strong>
              </h4>
              <p className="text--secondary">
                Application ID: {loanApplicationDetails.meta.data.application.id}
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
                  <span className="text-small">Completed</span>
                </h4>
              </div>
            </div>
          </div>
        )}
        <div class="loan-onboarding-content-body">
          <ApplicationStatusOverview openLoanEntity={this.openLoanEntity} />
        </div>
      </div>
    );
  };

  getUIConfig = () => {
    const { loanApplicationDetails } = this.props;

    return loanApplicationDetails.meta.configuration.ui;
  };

  componentWillReceiveProps(nextProps) {
    const nextProduct = CAPITAL_PRODUCT_NAME_CODE_MAP[nextProps.match.params.product];
    if (this.getProductCode() !== nextProduct) {
      this.initApplication(nextProduct);
    }
  }

  render() {
    const { loanApplicationDetails, user } = this.props;
    if (loanApplicationDetails.products.loading || loanApplicationDetails.meta.loading)
      return (
        <div class="capital-landing-spinner-container">
          <Spinner />
        </div>
      );

    const UIConfig = this.getUIConfig();
    return (
      <OnBoardingWrapper class="Loans">
        <div className="Landing--Image">
          <div class="image-wrapper">
            <img src={UIConfig.product.heroImageSource} alt="landing-image" />
          </div>
        </div>

        <div className="Product--Details">
          <div className="Details-title">
            {UIConfig.product.title}
            <div className="divider" />
          </div>

          {UIConfig.product.summary}
          <hr />

          <DataList>{UIConfig.product.pros}</DataList>
        </div>

        <div className="loan-application-home">
          {loanApplicationDetails.meta.loading || loanApplicationDetails.products.loading ? (
            <ApplicationOverviewLoadingSkeleton />
          ) : (
            this.getApplicationOverview()
          )}
        </div>
      </OnBoardingWrapper>
    );
  }
}

export default LoanApplicationOverview;
