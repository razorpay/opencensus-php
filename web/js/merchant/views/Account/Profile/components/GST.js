import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { fetchGST } from 'merchant/reducers/profile';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import AddGST from 'merchant/views/Account/Profile/components/AddGST';
import ShowWhen from 'merchant/components/ShowWhen';
import { merchantFetch } from 'merchant/utils/ajax';
import ConfirmAddressUpdate from './ConfirmAddressUpdate';
import { showNotification } from 'merchant_common/reducers/notifications';
import { bindActionCreators, compose } from 'redux';
import moment from 'moment';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { WORKFLOWS } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';

const THRESHOLD_DATE_GSTIN = `01/01/2021`;

class GSTDetails extends Component {
  state = {
    business_suggested_address: null,
    business_suggested_pin: null,
    newAddressFetchFailed: false,
    optOutSuccess: null,
    activationResponse: null,
    selfServeStatusDetails: {},
    isLoading: true,
  };

  GSTSection = React.createRef(null);

  componentWillMount() {
    this.props.fetchGST();
  }

  componentDidMount() {
    this.fetchNewAddress();
    this.getSelfServeStatus();

    // scroll directly to GST section
    if (location.hash.startsWith('#gst') && this.GSTSection.current)
      this.GSTSection.current.scrollIntoView();
  }

  openAddGSTModal = () => {
    const { business_suggested_address, business_suggested_pin, activationResponse } = this.state;

    analyticsTrack({
      objectName: 'Merchant clicks on add gstin',
      actionName: 'Add GSTIN clicked',
      screen: 'My account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    return this.props.openModal({
      size: 'medium',
      component: (
        <AddGST
          flow="Add"
          suggestedAddress={business_suggested_address}
          suggestedPin={business_suggested_pin}
          showGSTINSelfServe={this.showGSTINSelfServe}
          showNotification={this.props.showNotification}
          activationData={activationResponse}
          fetchStatus={this.getSelfServeStatus}
        />
      ),
    });
  };

  openEditGSTModal = () => {
    const { business_suggested_address, business_suggested_pin, activationResponse } = this.state;

    analyticsTrack({
      objectName: 'Merchant clicks on edit gstin',
      actionName: 'Edit GSTIN clicked',
      screen: 'My account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    return this.props.openModal({
      size: 'medium',
      component: (
        <AddGST
          flow="Edit"
          suggestedAddress={business_suggested_address}
          suggestedPin={business_suggested_pin}
          showGSTINSelfServe={this.showGSTINSelfServe}
          showNotification={this.props.showNotification}
          activationData={activationResponse}
          fetchStatus={this.getSelfServeStatus}
        />
      ),
    });
  };

  fetchNewAddress = async () => {
    try {
      const { data, success } = await merchantFetch(`merchant/activation`);
      // Truly successful
      if (data && success) {
        this.setState({
          activationResponse: data,
          business_suggested_address: data.business_suggested_address
            ? data.business_suggested_address
            : '',
          business_suggested_pin: data.business_suggested_pin ? data.business_suggested_pin : '',
        });
      }
    } catch (error) {
      this.setState({
        newAddressFetchFailed: true,
      });
      const msg = error.errors.join(' ');
      this.props.showNotification({
        type: 'error',
        message: `${msg}`,
      });
    }
  };

  clickOptOut = () =>
    merchantFetch({
      url: `merchants/me/features?features[suggested_address_opt_in]=0&should_sync=1`,
      method: 'POST',
    });

  getSelfServeStatus = async () => {
    try {
      const response = await merchantFetch(`merchant/gstin_update_self_serve/details`);
      if (response) {
        this.setState({
          selfServeStatusDetails: response?.data,
          isLoading: false,
        });
      }
    } catch (error) {
      const msg = error?.errors?.join(' ');
      this.props.showNotification({
        type: 'error',
        message: `${msg}`,
      });
      this.setState({
        isLoading: false,
      });
    }
  };

  updateOptOutSuccess = (value) => this.setState({ optOutSuccess: value });

  confirmDontUpdate = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <ConfirmAddressUpdate
          closeModal={this.props.closeModal}
          handleUpdate={this.clickOptOut}
          updateOptOutSuccess={this.updateOptOutSuccess}
          showNotification={this.props.showNotification}
        />
      ),
    });
  };

  showGSTOptOutFlow = () => {
    if (this.props.user.features) {
      const show = this.props.user.features.filter((f) => f.feature === `suggested_address_opt_in`);

      if (show.length > 0) return true;
      else;
      return false;
    } else {
      return false;
    }
  };

  showGSTINChangeBlock = () => {
    const { newAddressFetchFailed, optOutSuccess } = this.state;

    if (optOutSuccess) return false;
    else if (newAddressFetchFailed) return false;
    else return true;
  };

  renderNote = () => {
    const { business_suggested_address, business_suggested_pin } = this.state;

    if (business_suggested_address && business_suggested_pin) return `address & pincode`;
    else if (business_suggested_address) return `address`;
    else if (business_suggested_pin) return 'pincode';

    return '';
  };

  isWorkFlowRejected = () => {
    const { selfServeStatusDetails } = this.state;

    if (
      selfServeStatusDetails.workflow_status &&
      selfServeStatusDetails.workflow_status === 'rejected' &&
      selfServeStatusDetails?.request_under_validation !== true
    ) {
      return true;
    }

    return false;
  };

  isRequestUnderReview = () => {
    const { selfServeStatusDetails } = this.state;

    // request under validation
    if (
      (selfServeStatusDetails?.workflow_status &&
        ['open', 'approved'].includes(selfServeStatusDetails?.workflow_status) &&
        !selfServeStatusDetails?.needs_clarification) ||
      selfServeStatusDetails?.request_under_validation === true
    ) {
      return true;
    }

    return false;
  };

  didCustomerRespond = () => {
    const { selfServeStatusDetails } = this.state;

    if (
      selfServeStatusDetails?.workflow_status &&
      ['open', 'approved'].includes(selfServeStatusDetails?.workflow_status) &&
      selfServeStatusDetails?.needs_clarification &&
      selfServeStatusDetails?.tags?.includes('customer-responded')
    ) {
      return true;
    }

    return false;
  };

  isCustomerResponseAwaited = () => {
    const { selfServeStatusDetails } = this.state;

    if (
      selfServeStatusDetails.workflow_status &&
      ['open', 'approved'].includes(selfServeStatusDetails.workflow_status) &&
      selfServeStatusDetails.needs_clarification &&
      selfServeStatusDetails.tags?.includes('awaiting-customer-response')
    ) {
      return true;
    }

    return false;
  };

  addClarification = () => {
    const { selfServeStatusDetails } = this.state;

    analyticsTrack({
      objectName: 'Needs GSTIN clarification respond',
      actionName: 'Clicked',
      screen: 'My account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    this.props.openModal({
      size: 'small',
      component: (
        <NeedsClarificationModal
          workflowType={WORKFLOWS.UPDATE_GSTIN}
          clarificationReason={selfServeStatusDetails.needs_clarification}
          onResponseSubmit={this.getSelfServeStatus}
        />
      ),
    });
  };

  handleRetry = () => {
    const hasGstin = this.props.merchant_gst.gstin && true;
    const { user } = this.props;

    if (!hasGstin && user.isGstinAddFlowEnabled) {
      this.openAddGSTModal();
    }

    if (hasGstin && user.isGstinEditFlowEnabled && this.signedUpAfterThreshold()) {
      this.openEditGSTModal();
    }
  };

  signedUpAfterThreshold = () => {
    return (
      new Date(moment.unix(this.props.user.created_at).format('DD/MM/YYYY')) >=
      new Date(THRESHOLD_DATE_GSTIN)
    );
  };

  render() {
    const { merchant_gst, rzp_gst, user } = this.props;
    const {
      business_suggested_address,
      business_suggested_pin,
      selfServeStatusDetails,
      isLoading,
    } = this.state;

    const hasGstin = merchant_gst.gstin && true;
    const isSignedUpAfterThreshold = this.signedUpAfterThreshold();

    return (
      <div className="panel panel-default gst-details-block" ref={this.GSTSection}>
        <div className="panel-heading" style={{ overflow: 'scroll' }}>
          GST Details
          <ShowWhen
            myRole="owner admin"
            additionalCondition={(usr) => usr.isAllowedEdit('profile')}
          >
            <span className="pull-right">
              {!hasGstin &&
                isLoading === false &&
                user.isGstinAddFlowEnabled &&
                !this.isRequestUnderReview() &&
                !this.isCustomerResponseAwaited() &&
                !this.didCustomerRespond() &&
                !this.isWorkFlowRejected() && <a onClick={this.openAddGSTModal}>Add GST details</a>}

              {hasGstin &&
                isLoading === false &&
                user.isGstinEditFlowEnabled &&
                !this.isRequestUnderReview() &&
                !this.isWorkFlowRejected() &&
                !this.isCustomerResponseAwaited() &&
                !this.didCustomerRespond() &&
                isSignedUpAfterThreshold && (
                  <a onClick={this.openEditGSTModal}>Update GST details</a>
                )}

              {/* Request was rejected flow  */}
              {this.isWorkFlowRejected() && isLoading === false && (
                <a onClick={this.handleRetry}>Request rejected (retry)</a>
              )}

              {/* Request is under review flow */}
              {this.isRequestUnderReview() && isLoading === false && (
                <span className="pull-right" style={{ opacity: '0.5' }}>
                  Request under review
                </span>
              )}

              {/* Needs clarification flow */}
              {this.isCustomerResponseAwaited() && isLoading === false && (
                <a onClick={this.addClarification}>Add reply</a>
              )}
            </span>
          </ShowWhen>
          {this.isWorkFlowRejected() && (
            <div className="workflow-status rejected">
              {selfServeStatusDetails.rejection_reason_message}
            </div>
          )}
          {this.isCustomerResponseAwaited() && isLoading === false && (
            <div className="workflow-status rejected">
              {selfServeStatusDetails.needs_clarification}
            </div>
          )}
          {/* Customer has replied with clarification flow */}
          {this.didCustomerRespond() && isLoading === false && (
            <div className="workflow-status inprogress">
              Thank you for providing us with further information. Our team is going through the
              information provided by you and will help resolve this issue.
            </div>
          )}
        </div>

        <div className="list-group details-row-container">
          <div className="list-group-item">
            <span>GST Details</span>

            {merchant_gst.gstin ? (
              <span>{merchant_gst.gstin}</span>
            ) : (
              <span className="text-danger">Not Updated</span>
            )}
          </div>

          {user.isOrgRZP && (
            <div className="list-group-item">
              <span>Razorpay&#39;s GST Number</span>
              <span>{rzp_gst.gstin}</span>
            </div>
          )}

          {this.showGSTINChangeBlock() && this.showGSTOptOutFlow() === true && (
            <div className="gst-opt-out-cta">
              {business_suggested_address && (
                <div className="address-section">
                  <span>Business Address</span>
                  <span>{business_suggested_address}</span>
                </div>
              )}
              {business_suggested_pin && (
                <div className="pincode-section">
                  <span>Business Pincode</span>
                  <span>{business_suggested_pin}</span>
                </div>
              )}
              <div className="action">
                <span>
                  <p onClick={this.confirmDontUpdate}>Don&#39;t update</p>
                </span>
              </div>
              <span className="dot-loader">.</span>
            </div>
          )}
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    ...state.profile,
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchGST, openModal, closeModal, showNotification }, dispatch);

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('GSTDetails')),
)(GSTDetails);
