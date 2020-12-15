import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { makePopup } from '@typeform/embed';
import { withRouter } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import SwitchField from 'common/ui/Forms/SwitchField';
import { InternationalStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';

import User from 'merchant/models/User';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateSession } from 'merchant/reducers/session';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';
import { fetchSchedule } from 'merchant/reducers/settlements/details';

import { merchantFetch } from 'merchant/utils/ajax';
import { isPresent } from 'common/utils/rzp-utils';
import LocalStorageService from 'common/utils/localStorage';

import RequestInitiateModal from './components/InternationalConfigComponents/RequestInitiateModal.js';
import RequestSubmittedModal from './components/InternationalConfigComponents/RequestSubmittedModal.js';
import ProductInfo from './components/InternationalConfigComponents/ProductInfo.js';

const StatusMap = {
  no_action_received: 'disabled',
  in_review: 'access_requested',
  approved: 'enabled',
  rejected: 'request_rejected',
};

const NO_ACTION_RECEIVED = 'no_action_received';
const IN_REVIEW = 'in_review';
const APPROVED = 'approved';
const REJECTED = 'rejected';

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    settlement: state.settlement,
  }),
  {
    openModal,
    closeModal,
    fetchSchedule,
    showNotification,
    updateSession,
    fetchAddWebsiteWorkflowStatus,
  },
)
@RTracking(() => window.rzpQ.component('InternationalConfig'))
class InternationalConfig extends Component {
  constructor(props) {
    super(props);
    this.internationalSection = React.createRef();
    this.state = {
      isAccessRequested: false,
      pgProductStatus: '', // Payment Gateway
      otherProductsStatus: '', // otherProducts = Payment Pages, Payment Links, Invoices
      requestedAccessFrom: '',
      isWebsiteInWorkflow: null,
      showStatusLabel: props.user.international,
    };
    this.initializeTypeForm();
  }

  initializeTypeForm = () => {
    const currentMID = this.props.user.current;
    const IntlEnableTypeForm = makePopup(
      `https://razorpay.typeform.com/to/jJCZoO?mid=${currentMID}`,
      {
        mode: 'popup',
        hideHeaders: true,
        hideFooters: true,
        onSubmit: this.handleTypeFormSubmitted,
      },
    );
    this.IntlEnableTypeForm = IntlEnableTypeForm; // saving reference typeform
  };

  async componentDidMount() {
    const currentMID = this.props.user.current;
    const isAccessRequested = LocalStorageService.getItem(
      `international-access-requested-${currentMID}`,
    );

    try {
      const fetchStatusRes = await merchantFetch({
        url: 'merchants/product_international/workflow/status/all',
        mode: 'live',
        method: 'GET',
      });

      if (fetchStatusRes.success) {
        this.setInternationalFlowStatusForProducts(fetchStatusRes.data.data);
      }
    } catch (err) {
      this.props.showNotification({
        type: 'error',
        message: 'Could not fetch international payments feature status!',
      });
    }

    this.setState({
      isAccessRequested: !!isAccessRequested,
    });

    this.props
      .fetchAddWebsiteWorkflowStatus()
      .then(({ data }) => {
        this.setState({
          isWebsiteInWorkflow: data,
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: 'Could not fetch website workflow status.',
        });
      });

    if (
      this.internationalSection.current &&
      this.props.location.hash === '#request-international'
    ) {
      this.internationalSection.current.scrollIntoView();
    }
    this.props.fetchSchedule();
  }

  setInternationalFlowStatusForProducts = (internationalWorkflowStatus) => {
    const currentMID = this.props.user.current;

    const isPGStatusSetInLocalStorage = !!LocalStorageService.getItem(
      `international-pg-${currentMID}`,
    );
    const isOtherProductsStatusInLocalStorage = !!LocalStorageService.getItem(
      `international-otherProducts-${currentMID}`,
    );

    let pgProductStatus = '';
    let otherProductsStatus = '';

    pgProductStatus = internationalWorkflowStatus['payment_gateway'];

    if (pgProductStatus === NO_ACTION_RECEIVED && isPGStatusSetInLocalStorage) {
      pgProductStatus = IN_REVIEW;
    }

    otherProductsStatus = internationalWorkflowStatus['payment_links'];

    if (otherProductsStatus === NO_ACTION_RECEIVED && isOtherProductsStatusInLocalStorage) {
      otherProductsStatus = IN_REVIEW;
    }

    this.setState({ pgProductStatus, otherProductsStatus });
  };

  analytics = (action) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - International card payments`,
    });
  };

  closeModal = () => {
    this.props.closeModal();
  };

  openRequestInitiateModal = ({ triggerSource = '' }) => {
    this.props.openModal({
      component: (
        <RequestInitiateModal
          closeModal={this.closeModal}
          openTypeForm={this.openTypeForm}
          triggerSource={triggerSource}
          showNotification={this.props.showNotification}
        />
      ),
      size: 'medium',
    });
  };

  openTypeForm = (triggerSource) => {
    this.setState(
      {
        requestedAccessFrom: triggerSource ? triggerSource : 'requestedFromHeader',
      },
      () => {
        this.IntlEnableTypeForm.open();
      },
    );
  };

  handleTypeFormSubmitted = () => {
    const { requestedAccessFrom } = this.state;
    const currentMID = this.props.user.current;

    if (requestedAccessFrom === 'requestedFromHeader') {
      LocalStorageService.setItem(`international-access-requested-${currentMID}`, true);
    } else {
      LocalStorageService.setItem(`international-${requestedAccessFrom}-${currentMID}`, true);
    }

    let stateKeyToUpdate = '';

    if (requestedAccessFrom === 'pg') {
      stateKeyToUpdate = 'pgProductStatus';
    } else if (requestedAccessFrom === 'otherProducts') {
      stateKeyToUpdate = 'otherProductsStatus';
    } else stateKeyToUpdate = 'isAccessRequested';

    this.setState({ [stateKeyToUpdate]: IN_REVIEW }, () => {
      this.IntlEnableTypeForm.close();
      this.openRequestSubmittedModal();
    });
  };

  openRequestSubmittedModal = () => {
    this.props.openModal({
      component: <RequestSubmittedModal closeModal={this.closeModal} />,
      size: 'medium',
    });
  };

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Toggle_International_Payments',
    }),
  )
  toggleInternationalization = (enableInternational, postActionCB) => {
    this.analytics(enableInternational ? 'Enable' : 'Disable');

    this.setState({ showStatusLabel: enableInternational });

    return merchantFetch({
      url: 'merchant/international',
      method: 'PATCH',
      data: {
        international: enableInternational ? 1 : 0,
      },
    })
      .then((resp) => {
        // Check if the response sets international as intended in this request
        if (resp.data.international === !!enableInternational) {
          postActionCB(true);
          // Update user in store
          const user = new User({
            ...this.props.user,
            international: resp.data.international,
          });

          this.props.updateSession({ user });
        } else {
          throw new Error(
            'We are unable to process this request. Please reach out to support@razorpay.com',
          ); // This code is ideally unreachable as per business logic. However, since Api silently fails here, hence handling explicitly.
        }
      })
      .catch((err) => {
        postActionCB(false);
        let error = 'Something went wrong!';

        if (typeof err === 'object' && err.hasOwnProperty('message')) {
          error = err.message;
        } else if (err.errors) {
          error = err.errors[1];
        } else {
          error = err;
        }

        this.props.showNotification({
          type: 'error',
          message: error,
        });
      });
  };

  hasRequestedAccessForProduct = (product) => {
    const currentMID = this.props.user.current;

    const isProductAccessRequested = LocalStorageService.getItem(
      `international-${product}-${currentMID}`,
    );

    return this.isStatusUpdatedForProduct(product) || isPresent(isProductAccessRequested);
  };

  get currentStatusOnHeader() {
    const { pgProductStatus, otherProductsStatus } = this.state;

    if (!(pgProductStatus || otherProductsStatus) || this.isAnyProductIntlApproved) {
      return null;
    }

    if (!this.isStatusUpdatedForAnyProduct && this.state.isAccessRequested) {
      return IN_REVIEW;
    }

    const isAnyProductInReview = pgProductStatus === IN_REVIEW || otherProductsStatus === IN_REVIEW;

    if (isAnyProductInReview) {
      return IN_REVIEW;
    }

    const allProductsRejected = pgProductStatus === REJECTED && otherProductsStatus === REJECTED;

    if (allProductsRejected) {
      return REJECTED;
    }

    return null;
  }

  isStatusUpdatedForProduct = (product) => {
    const { pgProductStatus, otherProductsStatus } = this.state;

    if (!pgProductStatus && !otherProductsStatus) return false;

    if (product === 'pg' && pgProductStatus !== NO_ACTION_RECEIVED) {
      return true;
    }

    if (product === 'otherProducts' && otherProductsStatus !== NO_ACTION_RECEIVED) {
      return true;
    }

    return false;
  };

  get isStatusUpdatedForAnyProduct() {
    const { pgProductStatus, otherProductsStatus } = this.state;

    return pgProductStatus !== NO_ACTION_RECEIVED || otherProductsStatus !== NO_ACTION_RECEIVED;
  }

  get isRequestAccessAllowed() {
    const { isAccessRequested } = this.state;

    return this.isInternationalGreyList && !isAccessRequested && !this.isStatusUpdatedForAnyProduct;
  }

  get isInternationalPaymentsAllowed() {
    const { user } = this.props;

    if (user.isUnregisteredBusiness && !this.isAnyProductIntlApproved) {
      return false;
    }

    if (this.isInternationalBlackList) {
      return false;
    }

    return true;
  }

  get isInternationalGreyList() {
    return !!(
      this.props.user.international_activation_flow &&
      this.props.user.international_activation_flow === 'greylist'
    );
  }

  get isAnyProductIntlApproved() {
    return this.state.pgProductStatus === APPROVED || this.state.otherProductsStatus === APPROVED;
  }

  get isInternationalWhiteList() {
    return !!(
      this.props.user.international_activation_flow &&
      this.props.user.international_activation_flow === 'whitelist'
    );
  }

  get isInternationalBlackList() {
    return !!(
      this.props.user.international_activation_flow &&
      this.props.user.international_activation_flow === 'blacklist'
    );
  }

  get isKycComplete() {
    return this.props.user.isAccepted;
  }

  get description() {
    const { user } = this.props;
    const _isInternationalPaymentsAllowed = this.isInternationalPaymentsAllowed;

    let line = '';

    if (this.isInternationalGreyList && !this.isKycComplete) {
      if (
        user.activation_status === 'under_review' ||
        user.activation_status === 'needs_clarification'
      ) {
        line =
          'Your KYC form is under review. You will be able to request access for international payments once your KYC is approved.';
      } else {
        line = 'Please submit your KYC form to request access.';
      }
    } else {
      if (!this.isWebsiteAdded) {
        if (this.isInternationalWhiteList) {
          line = 'Add a website to enable international payments.';
        } else if (this.isInternationalGreyList) {
          line = 'You need to add your website to request access for international payments.';
        }
      } else {
        // Intl. whitelist but added website after L1 completion
        if (this.isInternationalWhiteList && user.isAccepted) {
          line =
            'Your website is currently in review. International payments will be enabled once website is approved.';
        } else if (this.isInternationalWhiteList && user.instantActivation.isL1Submitted) {
          line = 'Please complete your KYC to enable international payments.';
        }
      }
    }

    if (_isInternationalPaymentsAllowed) {
      return <>{!this.isAnyProductIntlApproved && line}</>;
    }

    return <>International card payments is not supported for your business model.</>;
  }

  get isWebsiteAdded() {
    return this.props.user.business_website || this.state.isWebsiteInWorkflow;
  }

  renderProductsSection() {
    if (!this.isAnyProductIntlApproved || this.isInternationalBlackList) {
      return null;
    }

    const { pgProductStatus, otherProductsStatus, showStatusLabel } = this.state;
    return (
      <ul class="product-list">
        <ProductInfo
          title="Payment Gateway"
          status={StatusMap[pgProductStatus]}
          showRequestAccessBtn={!this.hasRequestedAccessForProduct('pg')}
          onRequestAccessClick={(e) => {
            e.preventDefault();
            this.openRequestInitiateModal({ triggerSource: 'pg' });
          }}
          isWebsiteAdded={this.isWebsiteAdded}
          isKycComplete={this.isKycComplete}
          product="pg"
          showStatusLabel={showStatusLabel}
        />

        <ProductInfo
          title="Payment Pages, Payment Links & Invoices"
          status={StatusMap[otherProductsStatus]}
          showRequestAccessBtn={!this.hasRequestedAccessForProduct('otherProducts')}
          onRequestAccessClick={(e) => {
            e.preventDefault();
            this.openRequestInitiateModal({ triggerSource: 'otherProducts' });
          }}
          isWebsiteAdded={this.isWebsiteAdded}
          isKycComplete={this.isKycComplete}
          product="otherProducts"
          showStatusLabel={showStatusLabel}
        />
      </ul>
    );
  }

  renderInternationalAccessOrStatus() {
    const isRequestButtonDisabled = !this.isWebsiteAdded || !this.isKycComplete;

    if (this.isRequestAccessAllowed) {
      return (
        <Button.Primary
          class="pull-right"
          onClick={this.openRequestInitiateModal}
          disabled={isRequestButtonDisabled}
        >
          Request Access
        </Button.Primary>
      );
    }

    const currentStatus = this.currentStatusOnHeader;

    if (currentStatus) {
      return (
        <span class="access-status">
          <InternationalStatusLabel status={StatusMap[currentStatus]} />
        </span>
      );
    }

    return null;
  }

  render() {
    const internationalEnabled = this.props.user.international;
    const isTogglerVisible = this.isAnyProductIntlApproved;
    const description = this.description;
    const { settlement, user } = this.props;
    const { pgProductStatus, otherProductsStatus } = this.state;

    const settlementCycle = settlement.schedule.data.filter(
      (item) => item.method === null && item.international === 1,
    );

    return (
      <div ref={this.internationalSection} class="international-card">
        <div class="heading">
          <li class="title">International Card</li>

          {isTogglerVisible && (
            <span class="toggler-btn">
              <SwitchField
                defaultChecked={internationalEnabled}
                onChange={(isChecked, postActionCB) => {
                  this.toggleInternationalization(isChecked, postActionCB);
                }}
                type="prime"
              />
              {internationalEnabled ? (
                <b class="text-primary">Enabled</b>
              ) : (
                <b class="text-faded">Disabled</b>
              )}
            </span>
          )}

          {this.renderInternationalAccessOrStatus()}
        </div>

        <div class="body">
          <form class="form-horizontal">
            <div class="description">
              <div>
                {this.isInternationalPaymentsAllowed && (
                  <span>Card payments on payment gateway, payment pages, links & invoices</span>
                )}
              </div>
              <div>{description}</div>
            </div>
            <div class="description" style={{ marginBottom: '20px' }}>
              {this.renderProductsSection()}
              {(pgProductStatus === 'approved' || otherProductsStatus === 'approved') && (
                <div>
                  <span>
                    Limit per transaction:&nbsp;
                    <Amount value={user.merchant.max_payment_amount} currency={'INR'} />
                  </span>
                  &nbsp;&nbsp;
                  <span>
                    {settlementCycle.length && settlementCycle[0].delay && (
                      <>|&nbsp;&nbsp;Settlement Cycle: T+{settlementCycle[0].delay} days</>
                    )}
                  </span>
                </div>
              )}
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default InternationalConfig;
