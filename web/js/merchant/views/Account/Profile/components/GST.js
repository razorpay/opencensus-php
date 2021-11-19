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
import { bindActionCreators, compose } from 'redux';
import moment from 'moment';

const THRESHOLD_DATE_GSTIN = `01/01/2021`;

class GSTDetails extends Component {
  state = {
    business_suggested_address: null,
    business_suggested_pin: null,
    newAddressFetchFailed: false,
    optOutSuccess: null,
    activationResponse: null,
    selfServeStatus: null,
    rejectionReason: null,
  };

  GSTSection = React.createRef(null);

  componentWillMount() {
    this.props.fetchGST();
  }

  componentDidMount() {
    this.fetchNewAddress();
    if (this.props.user.isGstinSelfServeOn) {
      this.getSelfServeStatus();
    }

    // scroll directly to GST section
    if (location.hash.startsWith('#gst') && this.GSTSection.current)
      this.GSTSection.current.scrollIntoView();
  }

  openAddGSTModal = () => {
    const doesGSTINExist = this.props.merchant_gst.gstin;
    const {
      business_suggested_address,
      business_suggested_pin,
      activationResponse,
      selfServeStatus,
    } = this.state;

    const isSignedUpAfterThreshold =
      new Date(moment.unix(this.props.user.created_at).format('DD/MM/YYYY')) >=
      new Date(THRESHOLD_DATE_GSTIN);

    if (!doesGSTINExist) {
      return this.props.openModal({
        size: 'medium',
        component: (
          <AddGST
            suggestedAddress={business_suggested_address}
            suggestedPin={business_suggested_pin}
            showGSTINSelfServe={this.showGSTINSelfServe}
            showNotification={this.props.showNotification}
            activationData={activationResponse}
            selfServeStatus={selfServeStatus}
            fetchStatus={this.getSelfServeStatus}
          />
        ),
      });
    } else {
      // update gstin flow
      // update flow will only work if experiment is on & user is created after 1st Jan, 2021
      // eslint-disable-next-line no-lonely-if
      if (this.props.user.isGstinSelfServeOn && isSignedUpAfterThreshold) {
        return this.props.openModal({
          size: 'medium',
          component: (
            <AddGST
              suggestedAddress={business_suggested_address}
              suggestedPin={business_suggested_pin}
              showGSTINSelfServe={this.showGSTINSelfServe}
              showNotification={this.props.showNotification}
              activationData={activationResponse}
              selfServeStatus={selfServeStatus}
              fetchStatus={this.getSelfServeStatus}
            />
          ),
        });
      }
    }
    return '';
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
      if (response)
        this.setState({
          selfServeStatus: response.data?.status,
          rejectionReason: response.data?.rejection_reason,
        });
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

    return '';
  };

  render() {
    const { merchant_gst, rzp_gst, user } = this.props;
    const { business_suggested_address, business_suggested_pin, rejectionReason } = this.state;
    let title = merchant_gst.gstin
      ? user.isGstinSelfServeOn
        ? `Update GST details`
        : ``
      : `Add GST details`;
    if (rejectionReason) {
      title = 'Request rejected (retry)';
    }

    return (
      <div class="panel panel-default" ref={this.GSTSection}>
        <div class="panel-heading">
          GST Details
          <ShowWhen
            myRole="owner admin"
            additionalCondition={(usr) => usr.isAllowedEdit('profile')}
          >
            {this.state.selfServeStatus === 'not_started' &&
              this.state.activationResponse !== null && (
                <span class="pull-right">
                  <a onClick={this.openAddGSTModal}>{title}</a>
                  {rejectionReason && (
                    <Popover align="top" followPointer theme="dark">
                      <PopoverBody>{rejectionReason}</PopoverBody>
                    </Popover>
                  )}
                </span>
              )}
            {this.state.selfServeStatus === 'in_progress' && (
              <span class="pull-right" style={{ opacity: '0.5' }}>
                Request under review
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
              <span>Razorpay&#39;s GST Number</span>
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
                  <p onClick={this.confirmDontUpdate}>Don&#39;t update</p>
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
