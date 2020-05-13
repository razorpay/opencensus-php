import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { makePopup } from '@typeform/embed';

import Button from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';
import SwitchField from 'common/ui/Forms/SwitchField';
import { InternationalStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';

import User from 'merchant/models/User';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateSession } from 'merchant/reducers/session';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';

import { merchantFetch } from 'merchant/utils/ajax';
import { isPresent } from 'common/utils/rzp-utils';
import LocalStorageService from 'common/utils/localStorage';

const PROPRIETORSHIP = 1;
const NGO = 7;
const TRUST = 9;
const SOCIETY = 10;

const RequestSubmittedModal = ({ closeModal }) => (
  <div>
    <ModalHeader title="Request Submitted" onCloseClick={closeModal} />
    <div class="modal-body">
      <div>
        We have received your request for enabling international payments for
        Payment Pages, Payment Links & Invoices.
      </div>
      <br />
      <div>We would get back to you shortly with an update on your request</div>
      <div class="Modal__actions text-right">
        <Button.Primary onClick={closeModal}>Got it</Button.Primary>
      </div>
    </div>
  </div>
);

const RequestInitiateModal = ({ closeModal, openTypeForm }) => (
  <div>
    <ModalHeader
      title="Enable International Payments"
      onCloseClick={closeModal}
    />
    <div class="modal-body">
      <div>
        To enable international payments we would need some details about your
        business
      </div>
      <div class="Modal__actions text-right">
        <Button.Primary
          onClick={() => {
            closeModal();
            openTypeForm();
          }}
        >
          Provide Details
        </Button.Primary>
      </div>
    </div>
  </div>
);

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    openModal,
    closeModal,
    showNotification,
    updateSession,
    fetchAddWebsiteWorkflowStatus,
  }
)
@RTracking(() => window.rzpQ.component('InternationalConfig'))
class InternationalConfig extends Component {
  constructor(props) {
    super(props);
    this.state = {
      isAccessRequested: false,
      isWebsiteInWorkflow: null,
    };
    this.initializeTypeForm();
  }

  initializeTypeForm = () => {
    const currentMID = this.props.user.current;
    const IntlEnableTypeForm = makePopup(
      `https://razorpay.typeform.com/to/jJCZoO?mid=${currentMID}`,
      {
        mode: 'popup',
        autoClose: 3000,
        hideHeaders: true,
        hideFooters: true,
        onSubmit: this.handleTypeFormSubmitted,
      }
    );
    this.IntlEnableTypeForm = IntlEnableTypeForm; // saving reference typeform
  };

  componentDidMount() {
    const internationalEnabled = this.props.user.international;
    const currentMID = this.props.user.current;
    const isAccessRequested = LocalStorageService.getItem(
      `international-access-requested-${currentMID}`
    );

    if (isPresent(isAccessRequested)) {
      if (internationalEnabled) {
        LocalStorageService.removeItem(
          `international-access-requested-${currentMID}`
        );
      } else {
        this.setState({
          isAccessRequested,
        });
      }
    }

    this.props
      .fetchAddWebsiteWorkflowStatus()
      .then(({ data }) => {
        this.setState({
          isWebsiteInWorkflow: data,
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: 'Could not fetch website workflow status.',
        });
      });
  }

  analytics = action => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - International card payments`,
    });
  };

  closeModal = () => {
    this.props.closeModal();
  };

  openRequestInitiateModal = () => {
    this.props.openModal({
      component: (
        <RequestInitiateModal
          closeModal={this.closeModal}
          openTypeForm={this.openTypeForm}
        />
      ),
      size: 'medium',
    });
  };

  openTypeForm = () => {
    this.IntlEnableTypeForm.open();
  };

  handleTypeFormSubmitted = () => {
    const currentMID = this.props.user.current;
    LocalStorageService.setItem(
      `international-access-requested-${currentMID}`,
      true
    );
    this.setState({ isAccessRequested: true }, () => {
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
    })
  )
  toggleInternationalization = (enableInternational, postActionCB) => {
    this.analytics(enableInternational ? 'Enable' : 'Disable');

    return merchantFetch({
      url: 'merchant/international',
      method: 'PATCH',
      data: {
        international: enableInternational ? 1 : 0,
      },
    })
      .then(resp => {
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
            'We are unable to process this request. Please reach out to support@razorpay.com'
          ); // This code is ideally unreachable as per business logic. However, since Api silently fails here, hence handling explicitly.
        }
      })
      .catch(err => {
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

  renderHeaderActions = () => {
    const isAccessRequested = this.state.isAccessRequested;
    const isRequestAccessAllowed = this.isRequestAccessAllowed;
    const isRequestButtonDisabled =
      !this.props.user.has_key_access ||
      (this.isKycCompleteRequired && !this.props.user.isAccepted);

    if (isRequestAccessAllowed) {
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

    if (isAccessRequested || this.props.user.international) {
      return (
        <span class="pull-right">
          <InternationalStatusLabel
            status={isAccessRequested ? 'access-requested' : 'enabled'}
          />
        </span>
      );
    }

    return null;
  };

  get isKycCompleteRequired() {
    const KYC_COMPLETE_BIZ_TYPE = [PROPRIETORSHIP, NGO, TRUST, SOCIETY];
    const bizType = Number(this.props.user.business_type);

    if (KYC_COMPLETE_BIZ_TYPE.includes(bizType)) return true;

    return false;
  }

  get isRequestAccessAllowed() {
    const { user } = this.props;
    const { isAccessRequested } = this.state;

    return (
      !user.international && this.isInternationalGreyList && !isAccessRequested
    );
  }

  get isTogglerVisible() {
    return (
      this.props.user.international_activation_flow === 'whitelist' &&
      this.props.user.has_key_access
    );
  }

  get isInternationalPaymentsAllowed() {
    const { user } = this.props;
    return (
      user.international_activation_flow !== 'blacklist' &&
      !user.isUnregisteredBusiness
    );
  }

  get isInternationalGreyList() {
    return !!(
      this.props.user.international_activation_flow &&
      this.props.user.international_activation_flow === 'greylist'
    );
  }

  get isInternationalWhiteList() {
    return !!(
      this.props.user.international_activation_flow &&
      this.props.user.international_activation_flow === 'whitelist'
    );
  }

  get description() {
    const { user } = this.props;
    const _isInternationalPaymentsAllowed = this.isInternationalPaymentsAllowed;

    let line1 =
      'Accept international payments in nearly 100 foreign currencies from your customers.';
    let line2 = '';

    if (
      this.isKycCompleteRequired &&
      this.isInternationalGreyList &&
      !user.isAccepted
    ) {
      if (user.activation_status === 'under_review') {
        line2 = 'Your KYC form is under review.';
      } else {
        line2 = 'Please submit your KYC form to request access.';
      }
    } else {
      if (!user.has_key_access) {
        if (!user.business_website && !this.state.isWebsiteInWorkflow) {
          if (this.isInternationalWhiteList) {
            line2 = 'Add a website to enable international payments.';
          } else if (this.isInternationalGreyList) {
            line2 =
              'You need to add your website to request access for international payments.';
          }
        } else {
          line2 = 'Your website is currently under review.';
        }
      }
    }

    if (_isInternationalPaymentsAllowed) {
      return (
        <>
          {line1}
          <br />
          {line2}
        </>
      );
    }

    return (
      <>International card payments is not supported for your business model.</>
    );
  }

  render() {
    const internationalEnabled = this.props.user.international;
    const isTogglerVisible = this.isTogglerVisible;
    const description = this.description;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">International Payments</span>
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

          {this.isInternationalGreyList && this.renderHeaderActions()}
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            <div class="description">{description}</div>
            <div class="form-group">
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <div class="col-sm-10">
                  <a
                    class="highlight"
                    target="_blank"
                    href="https://razorpay.com/payment-gateway/#go-international"
                  >
                    Know more
                    <i
                      class="i i-external-link"
                      style={{ marginLeft: '5px' }}
                    />
                  </a>
                </div>
              </ShowWhen>
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default InternationalConfig;
