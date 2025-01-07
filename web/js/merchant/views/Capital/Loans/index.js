/* eslint-disable react/no-unsafe */
import React from 'react';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import CircularProgress from 'common/new-ui/CircularProgress';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import DataList from 'merchant/components/OnBoarding/Slides/DataList';
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
import lazy from 'merchant/routes/LazyLoader';
import {
  CASH_ADVANCE_BASE_URL,
  LINE_OF_CREDIT_BASE_URL,
  CASH_ADVANCE_SECTIONS,
} from 'merchant/views/Capital/CashAdvance/constants';
import ApplicationOverviewLoadingSkeleton from 'merchant/views/Capital/components/ApplicationOverviewLoadingSkeleton';
import Spinner from 'merchant/views/Capital/components/Spinner';
import {
  getDisabledReasons,
  getProductNames,
  isCashAdvanceProduct,
  isLOCEMIProduct,
  isLoanProduct,
  isCashAdvanceProductActive,
  canViewCashAdvanceProduct,
  canViewLOCEMIProduct,
} from 'merchant/views/Capital/utils';
import getApplicationProgressPercentage from 'merchant/views/Capital/utils/ProgressPercentageCalculator';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import ApplicationStatusOverview from './ApplicationStatusOverview';
import EditPanModal from './EditPanModal';
import ApplicationOnboardingForm from './Forms/ApplicationOnboardingForm';
import LoanEntity from './LoanEntity';
import api from './LoansCollections/api';
import { PLAN_STATUS } from './LoansCollections/constants';
import {
  CAPITAL_PRODUCT_NAME_CODE_MAP,
  HOTJAR_TRIGGERS,
  GA_CATEGORY_BY_PRODUCT,
  APPLICATION_STATES,
  LOANS_BASE_URL,
  LOANS_SECTIONS,
  CAPITAL_PRODUCT_CODES,
} from './constants';
import { LoanConfigImages } from '../loaders/constants';

const CashAdvanceV2 = lazy(() =>
  import(/* webpackChunkName: 'CashAdvanceV2' */ '../CashAdvanceV2'),
);

const PreApprovedOnboarding = lazy(() =>
  import(/* webpackChunkName: 'PreApprovedOnboarding' */ '../PreApprovedOnboarding'),
);

const REDIRECTABLE_APPLICATION_STATES = [
  APPLICATION_STATES.CREDIT_DISBURSED,
  APPLICATION_STATES.RZP_REJECTED,
  APPLICATION_STATES.CLOSED,
];

export const PROS = [
  <React.Fragment key={1}>
    <i className="i i-bullet" />
    <span>Get competitive interest rates for your risk profile</span>
  </React.Fragment>,
  <React.Fragment key={2}>
    <i className="i i-bullet" />
    <span>Apply online in 5 minutes with support when you need</span>
  </React.Fragment>,
  <React.Fragment key={3}>
    <i className="i i-bullet" />
    <span>Repay easily from daily settlements with more options</span>
  </React.Fragment>,
];

const parseApplicationMetaData = (loanApplicationDetails) => {
  const { meta: { product, loading, data: { application: { status = '' } = {} } } = {} } =
    loanApplicationDetails;

  return { loading, product, status };
};

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
  constructor(props) {
    super(props);
    this.state = {
      applications: [],
      loanDisabledReason: {
        fetching: this.isLoanDisabled,
        reasons: [], // product types eg:- [PRODUCT_TYPE_CARDS, ...]
      },
    };
    this.onLoadHandlers = [];
  }

  get isLoanDisabled() {
    return isLoanProduct(this.getProductCode()) && this.props.user.isLoansDisabled; // any capital products in dpd as per config
  }

  gaEventDispatcher = (eventObject) => {
    const { state: { eventCategory = null } = {} } = this.props.location;
    eventObject.eventCategory = eventCategory
      ? eventCategory
      : GA_CATEGORY_BY_PRODUCT[this.getProductCode()];
    window.rzpAnalytics?.(eventObject);
  };

  componentDidMount() {
    this.props.closeModal();
    this.initApplication(this.getProductCode());
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOAN_APPLICATION_PAGE_OPEN);
    document.querySelector('.pagefooter').style.display = 'none';
    this.isLoanDisabled &&
      getDisabledReasons(this.props.user).then((reasons) => {
        this.setState({
          loanDisabledReason: {
            fetching: false,
            reasons,
          },
        });
      });
  }

  componentWillUnmount() {
    document.querySelector('.pagefooter').style.display = 'block';
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
      this.onLoadHandlers.push(() => {
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
    const allowedProducts = [
      CAPITAL_PRODUCT_CODES.LOAN,
      CAPITAL_PRODUCT_CODES.CASH_ADVANCE,
      CAPITAL_PRODUCT_CODES.LOC_EMI,
    ];
    const productCode = this.getProductCode();
    if (!productCode || !allowedProducts.includes(productCode)) this.redirectToHome();
  };

  //eslint-disable-next-line
  validateFeatureAccess = () => {
    const { history, user } = this.props;

    const params = new URLSearchParams(history.location.search);
    const action = params.get('action');
    const productCode = this.getProductCode();

    if (isLoanProduct(productCode)) {
      return !user.isLoansEnabled && this.redirectToHome();
    }
    // early exit condition for non cash advance and loc emi since we dont want below conditions execute for other products such as loans.
    if (!isLOCEMIProduct(productCode) && !isCashAdvanceProduct(productCode)) {
      return this.redirectToHome();
    }
    const isCashAdvanceEligible = canViewCashAdvanceProduct(user);
    const isLOCEMIEligible = canViewLOCEMIProduct(user);

    // When cash-advance is clicked in the left nav, we take the user to
    // for cash-advance application page. But if the user's application
    // process is already completed user will land on cash-advance/withdrawals
    // page.
    // But even after loan application completion, in some cases user might want
    // to see the application details for some reason. Ideally we must show the
    // application instead of redirecting user to cash-advance because the
    // application process is completed. Hence check url params to validate
    // before redirection.
    if (isCashAdvanceProductActive(user) && !action) {
      return history.push(`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.OVERVIEW}`);
    }
    if (isCashAdvanceEligible && user.isCashAdvanceStage2Enabled && !action) {
      return history.push(CASH_ADVANCE_BASE_URL);
    }
    if (isCashAdvanceEligible && !isCashAdvanceProduct(productCode)) {
      // Edge case: incase merchant directly visits line of credit apply page and not eligible
      return history.push(`${CASH_ADVANCE_BASE_URL}apply`);
    }
    if (isLOCEMIEligible && !isLOCEMIProduct(productCode)) {
      // Edge case: incase merchant directly visits cash advance apply page and not eli
      return history.push(`${LINE_OF_CREDIT_BASE_URL}apply`);
    }

    return false;
  };

  redirectLoansOverview() {
    this.props.history.push(`${LOANS_BASE_URL}${LOANS_SECTIONS.OVERVIEW}`);
  }

  fetchApplications = () => {
    const productDetails = this.getProductDetails();

    if (!productDetails) this.redirectToHome();

    let latestApplication = null;
    this.props
      .getApplications({
        owner_type: 'MERCHANT',
        owner_id: this.props.user.current,
        product_id: productDetails.id,
      })
      .then(({ data: { applications = [] } = {} } = {}) => {
        if (!applications.length) return Promise.reject();
        this.setState({ applications });
        latestApplication = applications[0];
        const requests = [this.fetchApplicationDetails(latestApplication.id)];
        if (isLoanProduct(this.getProductCode())) {
          requests.push(api.getPlans());
        }
        return Promise.all(requests);
      })
      .then(
        ([
          { data: { application: { status = '' } = {} } = {} },
          { data: { plans = [] } = {} } = {},
        ]) => {
          this.onLoadHandlers.forEach((callback) => {
            callback(latestApplication);
          });
          const isRedirectable = REDIRECTABLE_APPLICATION_STATES.includes(status);
          const isPlanActive = plans.length
            ? plans.find((p) => p.status === PLAN_STATUS.CREATED)
            : null;
          const isPlanClosed = plans.length
            ? plans.find((p) => p.status === PLAN_STATUS.COMPLETED)
            : null;

          if (isRedirectable) {
            if (isPlanActive) {
              this.redirectLoansOverview();
            }
            if (isPlanClosed) {
              this.props.registerNewLoanApplication();
            }
          }
        },
      )
      .catch(() => {
        this.props.registerNewLoanApplication();
      });
  };

  //eslint-disable-next-line
  fetchApplicationDetails = (id) => {
    return id && id !== 'new' && this.props.fetchLoanApplicationMeta(id);
  };

  fetchSeedData = () => {
    this.props.fetchSeedData();
  };

  getProductDetails = () => {
    return (
      this.props.loanApplicationDetails?.products?.data?.find(
        (p) => p?.name === this.getProductCode(),
      ) || {}
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
    const productDetails = this.getProductDetails();
    const currentStatus = state ? state : meta.data.application.status;

    this.gaEventDispatcher({
      eventAction: `Landing Steps | ${_cta}`,
      eventLabel: `${
        meta?.configuration?.getApplicationStateDescriptions()[currentStatus]?.short_description
      } | ${_targetStepTitle} | ${this.getProgressPercentage()}% | ${
        this.state.applications.length
      }`,
    });

    this.props.changeActiveState(currentStatus);
    this.fetchApplicationDetails(applicationId);
    this.props.openModal({
      size: 'full-screen',
      component: (
        <LoanEntity
          onClose={this.handleModalClose}
          productDetails={productDetails}
          applicationId={applicationId}
        />
      ),
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
    if (!meta.data.application || !meta.data.application.status) return 0;
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
    const isProductCashAdvance = window.location.pathname.includes('cash-advance');

    const productDetails = this.getProductDetails();

    if (!productDetails) console.error('No Corresponding Product found');

    if (!loanApplicationDetails.meta.data.application) {
      return (
        <ApplicationOnboardingForm
          productId={productDetails.id}
          applicantPan={this.state.applicantPan}
          isPANLinkedWithPG={this.isPANLinkedWithPG()}
          isProductCashAdvance={isProductCashAdvance}
          handleCoApplicantPan={this.handleCoApplicantPan}
        />
      );
    }

    const { status } = parseApplicationMetaData(loanApplicationDetails);
    const applicationRejected = status === APPLICATION_STATES.RZP_REJECTED;
    const applicationClosed = status === APPLICATION_STATES.CLOSED;

    return (
      <div className="status-overview">
        {loanApplicationDetails.meta.data.application.owner_id && (
          <div className="loan-application-overview-header flex">
            <div className="loan-meta-wrapper">
              <h4>
                <strong>
                  Your{' '}
                  {isCashAdvanceProduct(loanApplicationDetails?.meta?.product)
                    ? 'Cash Advance'
                    : 'Loan'}{' '}
                  Application
                </strong>
              </h4>
              <p className="text--secondary">
                <i className="i i-document" />
                Application ID: {loanApplicationDetails.meta.data.application.id}
              </p>
            </div>

            {!(applicationRejected || applicationClosed) && (
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
            )}
          </div>
        )}
        <div
          className={
            applicationRejected || applicationClosed
              ? 'loan-application-disabled-body'
              : 'loan-onboarding-content-body'
          }
        >
          <ApplicationStatusOverview openLoanEntity={this.openLoanEntity} />
        </div>
      </div>
    );
  };

  getUIConfig = () => {
    const { loanApplicationDetails } = this.props;

    return loanApplicationDetails.meta.configuration?.ui;
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    const nextProduct = CAPITAL_PRODUCT_NAME_CODE_MAP[nextProps.match.params.product];
    if (this.getProductCode() !== nextProduct) {
      this.initApplication(nextProduct);
    }
  }

  //eslint-disable-next-line
  isLoanApplicationDisabled = (loanApplicationDetails) => {
    if (!loanApplicationDetails.meta.data.application) return false;
    const { status } = parseApplicationMetaData(loanApplicationDetails);
    if (status === APPLICATION_STATES.RZP_REJECTED || status === APPLICATION_STATES.CLOSED)
      return true;
    return false;
  };

  renderLoanApplicationDisabledDueToDPDUI = () => {
    const { loanApplicationDetails } = this.props;
    const { reasons } = this.state.loanDisabledReason;
    return (
      <div className="status-overview">
        <div className="loan-application-overview-header flex">
          <div className="loan-meta-wrapper">
            <h4>
              <strong>Your Loan Application</strong>
            </h4>
            {loanApplicationDetails.meta.data.application?.id && (
              <p className="text--secondary">
                <i className="i i-document" />
                Application ID: {loanApplicationDetails.meta.data.application.id}
              </p>
            )}
          </div>
        </div>
        <div className="loan-application-disabled-body loan-application-disabled-body--dpd">
          <div className="loan-application-disabled-wrapper">
            <div className="flex title-wrapper">
              <i className="i i-error" />
              <p className="title">Your loan application is on hold</p>
            </div>
            <p className="description">
              Your current loan application is temporarily put on hold due to missed repayments on{' '}
              {getProductNames(reasons)}.
            </p>
            <div className="subtitle">What should you do next?</div>
            <p className="action-point description">
              Please pay your current outsanding to continue with your application. If you have
              already repaid your pending dues, then your loan application will be enabled back
              within 24 - 48 working hours.
            </p>
          </div>
        </div>
      </div>
    );
  };

  render() {
    const { loanApplicationDetails } = this.props;

    if (loanApplicationDetails.products.loading || loanApplicationDetails.meta.loading)
      return (
        <div className="capital-landing-spinner-container">
          <Spinner />
        </div>
      );

    const UIConfig = this.getUIConfig();
    const isLoanApplicationDisabledDueToDPD = this.isLoanDisabled;
    const isFetchingLoanDisabledReason = this.state.loanDisabledReason.fetching;

    const hasApplication = loanApplicationDetails?.meta?.data?.application;
    const productCode = this.getProductCode();
    const isProductCashAdvance = isCashAdvanceProduct(productCode);
    const isProductLOCEMI = isLOCEMIProduct(productCode);

    if (isProductCashAdvance || isProductLOCEMI) {
      if (hasApplication) {
        return (
          <SuspenseWithLoader>
            <CashAdvanceV2
              productCode={productCode}
              showApplyNow={!hasApplication}
              applicationId={hasApplication?.id}
            />
          </SuspenseWithLoader>
        );
      } else {
        return (
          <SuspenseWithLoader>
            <PreApprovedOnboarding productCode={productCode} />
          </SuspenseWithLoader>
        );
      }
    } else
      return (
        <OnBoardingWrapper className="Loans">
          <div className="Landing--Image">
            <div className="image-wrapper">
              <img src={LoanConfigImages[UIConfig.product.heroImageSource]} alt="landing-image" />
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

          <div
            className={`loan-application-home ${
              this.isLoanApplicationDisabled(loanApplicationDetails) &&
              'loan-application-home-top-border'
            }`}
          >
            {loanApplicationDetails.meta.loading ||
            loanApplicationDetails.products.loading ||
            isFetchingLoanDisabledReason ? (
              <ApplicationOverviewLoadingSkeleton />
            ) : isLoanApplicationDisabledDueToDPD ? (
              this.renderLoanApplicationDisabledDueToDPDUI()
            ) : (
              this.getApplicationOverview()
            )}
          </div>
        </OnBoardingWrapper>
      );
  }
}

export default withRouter(LoanApplicationOverview);
