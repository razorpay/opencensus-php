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
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import WorkflowStatus from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';

class GSTDetails extends Component {
  state = {
    business_suggested_address: null,
    business_suggested_pin: null,
    newAddressFetchFailed: false,
    optOutSuccess: null,
    activationResponse: null,
  };

  GSTSection = React.createRef(null);

  componentWillMount() {
    this.props.fetchGST();
  }

  componentDidMount() {
    this.fetchNewAddress();

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
          fetchStatus={this.props.fetchWorkflowStatus(WORKFLOW_TYPES.UPDATE_GSTIN)}
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
          fetchStatus={this.props.fetchWorkflowStatus(WORKFLOW_TYPES.UPDATE_GSTIN)}
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
    const { gstinWorfklow } = this.props;

    if (
      gstinWorfklow.workflow_status &&
      gstinWorfklow.workflow_status === 'rejected' &&
      gstinWorfklow?.request_under_validation !== true
    ) {
      return true;
    }

    return false;
  };

  isRequestUnderReview = () => {
    const { gstinWorfklow } = this.props;

    // request under validation
    if (
      (gstinWorfklow?.workflow_status &&
        ['open', 'approved'].includes(gstinWorfklow?.workflow_status) &&
        !gstinWorfklow?.needs_clarification) ||
      gstinWorfklow?.request_under_validation === true
    ) {
      return true;
    }

    return false;
  };

  didCustomerRespond = () => {
    const { gstinWorfklow } = this.props;

    if (
      gstinWorfklow?.workflow_status &&
      ['open', 'approved'].includes(gstinWorfklow?.workflow_status) &&
      gstinWorfklow?.needs_clarification &&
      gstinWorfklow?.tags?.includes('customer-responded')
    ) {
      return true;
    }

    return false;
  };

  isCustomerResponseAwaited = () => {
    const { gstinWorfklow } = this.props;

    if (
      gstinWorfklow.workflow_status &&
      ['open', 'approved'].includes(gstinWorfklow.workflow_status) &&
      gstinWorfklow.needs_clarification &&
      gstinWorfklow.tags?.includes('awaiting-customer-response')
    ) {
      return true;
    }

    return false;
  };

  addClarification = () => {
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
          workflowType={WORKFLOW_TYPES.UPDATE_GSTIN}
          workflowName="Update GSTIN Details"
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

    if (hasGstin && user.isGstinEditFlowEnabled) {
      this.openEditGSTModal();
    }
  };

  render() {
    const { merchant_gst, rzp_gst, user } = this.props;
    const { business_suggested_address, business_suggested_pin } = this.props;

    const hasGstin = merchant_gst.gstin && true;

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
                user.isGstinAddFlowEnabled &&
                !this.isRequestUnderReview() &&
                !this.isCustomerResponseAwaited() &&
                !this.didCustomerRespond() &&
                !this.isWorkFlowRejected() && <a onClick={this.openAddGSTModal}>Add GST details</a>}

              {hasGstin &&
                user.isGstinEditFlowEnabled &&
                !this.isRequestUnderReview() &&
                !this.isWorkFlowRejected() &&
                !this.isCustomerResponseAwaited() &&
                !this.didCustomerRespond() && (
                  <a onClick={this.openEditGSTModal}>Update GST details</a>
                )}

              {/* Request was rejected flow  */}
              {this.isWorkFlowRejected() ? (
                (hasGstin && user.isGstinEditFlowEnabled) ||
                (!hasGstin && user.isGstinAddFlowEnabled) ? (
                  <a onClick={this.handleRetry}>Request rejected (retry)</a>
                ) : (
                  <span className="text-danger">Request Rejected</span>
                )
              ) : null}

              {/* Request is under review flow */}
              {this.isRequestUnderReview() && (
                <span className="pull-right" style={{ opacity: '0.5' }}>
                  Request under review
                </span>
              )}

              {/* Needs clarification flow */}
              {this.isCustomerResponseAwaited() && <a onClick={this.addClarification}>Add reply</a>}
            </span>
          </ShowWhen>
          <WorkflowStatus
            roles={[rolesList.OWNER]}
            workflowType={WORKFLOW_TYPES.UPDATE_GSTIN}
            showReviewStatus={false}
            showRejectedStatus={false}
            showAddReplyButton={false}
          />
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
    gstinWorfklow: state.workflows[WORKFLOW_TYPES.UPDATE_GSTIN],
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchGST,
      openModal,
      closeModal,
      showNotification,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
    dispatch,
  );

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('GSTDetails')),
)(GSTDetails);
