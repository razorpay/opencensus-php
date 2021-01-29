import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { fetchGST } from 'merchant/reducers/profile';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import AddGST from 'merchant/views/Account/Profile/components/AddGST';
import ShowWhen from 'merchant/components/ShowWhen';
import { merchantFetch } from 'merchant/utils/ajax';
import ConfirmAddressUpdate from './ConfirmAddressUpdate';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect((state) => ({ ...state.profile, user: state.session.user }), {
  fetchGST,
  openModal,
  closeModal,
  showNotification,
})
@RTracking(() => window.rzpQ.component('GSTDetails'))
export default class GSTDetails extends Component {
  state = {
    business_suggested_address: null,
    business_suggested_pin: null,
    newAddressFetchFailed: false,
    optOutSuccess: null,
    activationResponse: null,
    selfServeStatus: null,
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

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.my_account_actions', {
      action: 'Add_GSTIN_Initiated',
    }),
  )
  openAddGSTModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <AddGST
          suggestedAddress={this.state.business_suggested_address}
          suggestedPin={this.state.business_suggested_pin}
          showGSTINSelfServe={this.showGSTINSelfServe}
          user={this.props.user}
          showNotification={this.props.showNotification}
          activationData={this.state.activationResponse}
          selfServeStatus={this.state.selfServeStatus}
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
      const response = await merchantFetch(`merchant/gstin_self_serve`);
      if (response) this.setState({ selfServeStatus: response.data.status });
    } catch (error) {
      // empty block
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
  };

  render() {
    let { merchant_gst, rzp_gst, user } = this.props;
    let { business_suggested_address, business_suggested_pin } = this.state;
    const title = merchant_gst.gstin ? `Update GST details` : `Add GST details`;

    return (
      <div class="panel panel-default" ref={this.GSTSection}>
        <div class="panel-heading">
          GST Details
          <ShowWhen
            additionalCondition={(user) =>
              user.isAllowedEdit('profile') && user.isFeatureEnabled(`gstin_self_serve`)
            }
          >
            {this.state.selfServeStatus === 'not_started' &&
              this.state.activationResponse !== null && (
                <span class="pull-right">
                  <a onClick={this.openAddGSTModal}>{title}</a>
                </span>
              )}
            {this.state.selfServeStatus === 'in_progress' && (
              <span class="pull-right">
                <a>Request under review</a>
              </span>
            )}
          </ShowWhen>
        </div>
        <div class="list-group details-row-container">
          <div class="list-group-item">
            <span>GST Details</span>

            {merchant_gst.gstin ? (
              <span>{merchant_gst.gstin}</span>
            ) : (
              <span class="text-danger">Not Updated</span>
            )}
          </div>

          {user.isOrgRZP && (
            <div class="list-group-item">
              <span>Razorpay's GST Number</span>
              <span>{rzp_gst.gstin}</span>
            </div>
          )}

          {this.showGSTINChangeBlock() && this.showGSTOptOutFlow() === true && (
            <div class="list-group-item" style={{ borderBottom: 'none' }}>
              <span class="gst-invoice-note">
                Your business {this.renderNote()} you provided to Razorpay does not match with your
                address details on your GST certificate. On Jan 25, 2021, we will automatically
                update your address to the same address as per your GST certificate. Click{' '}
                <strong>Don’t Update</strong> if you do not want to update your business address to
                the address on your GST certificate. In such cases, we will not be able to register
                your invoice on the GST portal, resulting in you losing the tax benefits of GST
                input credit.{' '}
                <a
                  href="https://razorpay.com/docs/announcements/gst-changes/"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Read more
                </a>
              </span>
            </div>
          )}

          {this.showGSTINChangeBlock() && this.showGSTOptOutFlow() === true && (
            <div class="gst-opt-out-cta">
              {business_suggested_address && (
                <div class="address-section">
                  <span>Business Address</span>
                  <span>{business_suggested_address}</span>
                </div>
              )}
              {business_suggested_pin && (
                <div class="pincode-section">
                  <span>Business Pincode</span>
                  <span>{business_suggested_pin}</span>
                </div>
              )}
              <div class="action">
                <span>
                  <p onClick={this.confirmDontUpdate}>Don't update</p>
                </span>
              </div>
              <span class="dot-loader">.</span>
            </div>
          )}
        </div>
      </div>
    );
  }
}
