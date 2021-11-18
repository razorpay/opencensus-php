import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import Banner from 'common/ui/Banner';
import InputField from 'common/ui/Forms/InputField';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import { saveGST } from 'merchant/reducers/profile';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { required, validateGSTIN } from 'common/utils/validators';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import Popover, { PopoverBody } from 'common/ui/Popover';
import FileUpload from 'merchant/components/File/Upload';
import { merchantFetch } from 'merchant/utils/ajax';
import { bindActionCreators, compose } from 'redux';

function ShowStatusMsg({ closeModal }) {
  return (
    <div>
      <ModalHeader title="Verify GST details" />
      <div class="Modal__actions">
        <div class="modal-body rzp-gst-content">
          <div class="gst-help-block">
            <span>
              We are verifying your GST details with GST portal database. We will reach out to you
              if we need any more clarification
            </span>
          </div>
          <button class="btn btn-primary btn-block" onClick={closeModal}>
            Okay, got it
          </button>
        </div>
      </div>
    </div>
  );
}

class AddGST extends Component {
  state = {
    isGSTINSelfServeOn: false,
    gstinCertificate: null,
  };

  gst_success_msg = 'Entered GSTIN will be applicable only from current month onwards.';

  componentWillMount() {
    const { merchant_gst } = this.props;

    const initialValues = {
      gst_type: 'gstin',
    };

    this.props.initialize({
      ...initialValues,
      ...merchant_gst,
    });
  }

  handleEditClick = () =>
    this.setState((prevState) => {
      return {
        isGSTINSelfServeOn: !prevState.isGSTINSelfServeOn,
      };
    });

  updateGSTINAddress = async (event) => {
    event.preventDefault();
    const formData = new FormData();
    formData.append('gstin_self_serve_certificate', this.state.gstinCertificate);
    formData.append('gstin', this.props.merchant_gst.gstin);

    try {
      const response = await merchantFetch({
        url: `merchant/gstin_self_serve`,
        data: formData,
        method: `POST`,
      });

      if (response) {
        this.props.fetchStatus();
        this.props.openModal({
          size: 'small',
          component: <ShowStatusMsg closeModal={this.props.closeModal} />,
        });
      }
    } catch (error) {
      const msg = error.errors.join(' ');
      this.props.showNotification({
        type: 'error',
        message: `${msg}`,
      });
    }
  };

  onGstCertificateFileChange = (file) => {
    this.setState({ gstinCertificate: file || null });
  };

  save = ({ gst_type, ...otherProps }) => {
    const fieldProps = {};
    fieldProps[gst_type] = otherProps[gst_type];

    return this.props
      .saveGST(fieldProps)
      .then((item) => {
        const {
          updateSession: updateSessionFn,
          tracking,
          showNotification,
          closeModal,
        } = this.props;

        const user = new User({
          ...this.props.session.user,
          ...item.data,
        });

        updateSessionFn({
          user,
        });

        this.setState({
          saved: true,
        });

        tracking.trackEvent(
          window.rzpQ.onbr().initiated('dash.my_account_actions', {
            action: 'Add_GSTIN_Successful',
          }),
        );

        showNotification({
          type: 'success',
          message: `Your GST details are added. ${this.gst_success_msg}`,
          closeTimeout: 7000,
        });

        if (this.props.reloadAfterSave) {
          setTimeout(window.location.reload, 2500);
        } else {
          closeModal();
        }
      })
      .catch((err) => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  shouldGSTINBeDisabled = (isEditable) => {
    if (this.props.session.user.isFeatureEnabled(`gstin_self_serve`)) return false;

    if (isEditable) return false;

    return true;
  };

  render() {
    const { handleSubmit, merchant_gst, session, activationData } = this.props;
    const isNew = !merchant_gst.p_gstin && !merchant_gst.gstin;
    const isEditable = isNew && session.user.isAllowedEdit('profile_gst');
    const title = merchant_gst.gstin ? `Update GST details` : `Add GST details`;

    return (
      <div>
        {isNew ? <ModalHeader title={title} onCloseClick={this.props.closeModal} /> : null}

        <div class="modal-body rzp-gst-content">
          {!isNew ? (
            <div class="rzp-gst">
              <button type="button" class="close" onClick={this.props.closeModal}>
                <i class="i i-close" />
              </button>
            </div>
          ) : null}

          <Alert type="error" message={this.state.errors} />

          {this.state.saved ? (
            <div>
              <div>
                Your GST details have been updated. You can access it anytime from the{' '}
                <Link to="/profile" onClick={this.props.closeModal}>
                  Profile Section
                </Link>
              </div>

              <div class="gst-update-note">
                <Banner>
                  <b>Note:</b> {this.gst_success_msg}
                </Banner>
              </div>

              <div class="Modal__actions">
                <Link
                  class="btn btn-primary btn-block"
                  to="/profile"
                  onClick={this.props.closeModal}
                >
                  View my GST details
                </Link>
              </div>
            </div>
          ) : (
            <form onSubmit={isEditable ? handleSubmit(this.save) : undefined}>
              {!isNew && (
                <div class="help-block">
                  Entered GSTIN will appear on invoices that we send to you.
                </div>
              )}

              <div class="form-group">
                <label class="label-required">GSTIN</label>
                <div>
                  <Field
                    name="gstin"
                    component={InputField}
                    class="form-control"
                    autoFocus={true}
                    placeholder="19AAAAA1234Y1YY"
                    validate={[required(), validateGSTIN]}
                    disabled={this.shouldGSTINBeDisabled(isEditable)}
                  />
                </div>

                {isNew && (
                  <div class="gst-update-note">
                    <Banner>
                      <b>Note:</b> {this.gst_success_msg}
                    </Banner>
                  </div>
                )}
              </div>

              {this.props.selfServeStatus === 'not_started' && isNew && (
                <>
                  <label>GSTIN Address</label>
                  <div class="label-info">
                    <span>
                      Should be as per your GST Certificate. Your business address will also be
                      updated to this <i className="i i-info-circle" />
                      <Popover align="top" theme="dark">
                        <PopoverBody>
                          <div>
                            We use your business address to bill the invoices. It should be same as
                            the address on you GST certificate if you want to generate E-invoices.
                          </div>
                        </PopoverBody>
                      </Popover>
                    </span>
                  </div>
                </>
              )}

              {this.state.isGSTINSelfServeOn === false &&
                this.props.selfServeStatus === 'not_started' &&
                isNew && (
                  <div class="suggested-address-row">
                    <span>
                      {activationData.business_registered_address},{' '}
                      {activationData.business_registered_pin}{' '}
                      <p onClick={this.handleEditClick}>Edit</p>
                    </span>
                  </div>
                )}

              {this.state.isGSTINSelfServeOn === true && (
                <div class="form-group">
                  <div>
                    <Field
                      name="address"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                    />
                  </div>
                  <label class="label-required">Pincode</label>
                  <div>
                    <Field
                      name="pincode"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                      placeholder="Pincode"
                    />
                  </div>

                  <label class="label-required">City</label>
                  <div>
                    <Field
                      name="city"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                      placeholder="City"
                    />
                  </div>

                  <label class="label-required">State</label>
                  <div>
                    <Field
                      name="state"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                      placeholder="State"
                    />
                  </div>
                </div>
              )}

              {(isEditable || this.state.isGSTINSelfServeOn) && (
                <div class="Modal__actions">
                  <AsyncButton
                    type="submit"
                    class="btn btn-primary btn-block"
                    text="Save my GST Details"
                    pendingText="Saving..."
                    onClick={handleSubmit(this.save)}
                  />
                </div>
              )}
              <div>
                {!isNew && (
                  <>
                    <label class="label-required">GSTIN Certificate</label>
                    <Field
                      name="certificate"
                      component={FileUpload}
                      accept={['jpg', 'png', 'pdf']}
                      maxSize={2102000}
                      onBiggerFileSize={() => {
                        this.props.showNotification({
                          type: 'error',
                          message: `Document too large. Max limit 2MB`,
                        });
                      }}
                      onFileChange={this.onGstCertificateFileChange}
                      validate={required('Please upload GSTIN certificate')}
                      onCloseClick={this.onGstCertificateFileChange}
                    />

                    <div class="Modal__actions">
                      <button
                        type="submit"
                        class="btn btn-primary btn-block"
                        onClick={this.updateGSTINAddress}
                        disabled={this.state.gstinCertificate === null}
                      >
                        Submit
                      </button>
                    </div>
                  </>
                )}
              </div>
            </form>
          )}
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => ({
  merchant_gst: state.profile.merchant_gst,
  rzp_gst: state.profile.rzp_gst,
  session: state.session,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    { saveGST, updateSession, ...ModalActions, ...NotificationsActions },
    dispatch,
  );

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  reduxForm({
    form: 'newGST',
  }),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('AddGST')),
)(AddGST);
