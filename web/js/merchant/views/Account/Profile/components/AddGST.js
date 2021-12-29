import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { Field, reduxForm } from 'redux-form';
import Banner from 'common/ui/Banner';
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
import Input from 'common/new-ui/Input';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const GST_SUCCESS_MSG = 'Entered GSTIN will be applicable only from current month onwards.';

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
    gstinCertificate: null,
    gstin: null,
  };

  onInputFieldBlur = (e) => this.setState({ gstin: e.target.value });

  updateGSTIN = async (event) => {
    event.preventDefault();
    const formData = new FormData();
    formData.append('gstin_self_serve_certificate', this.state.gstinCertificate);
    formData.append('gstin', this.state.gstin);

    analyticsTrack({
      objectName: 'Merchant submits new gstin in edit gstin flow',
      actionName: 'Edit GSTIN submitted',
      screen: 'My account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    try {
      const response = await merchantFetch({
        url: `merchant/gstin_self_serve`,
        data: formData,
        mode: 'live',
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
    this.setState({ gstinCertificate: file || null, errors: null });
  };

  addGSTIN = (event) => {
    event.preventDefault();

    const formData = new FormData();
    formData.append('gstin_self_serve_certificate', this.state.gstinCertificate);
    formData.append('gstin', this.state.gstin);

    analyticsTrack({
      objectName: 'Merchant submits new gstin in add gstin flow',
      actionName: 'Add GSTIN submitted',
      screen: 'My account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    return merchantFetch({
      url: `merchant/gstin_self_serve`,
      data: formData,
      mode: 'live',
      method: `POST`,
    })
      .then((item) => {
        const { updateSession: updateSessionFn } = this.props;
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
        // cleanup
        this.props.fetchStatus();
        this.props.openModal({
          size: 'small',
          component: <ShowStatusMsg closeModal={this.props.closeModal} />,
        });
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

  onBiggerFileSize = () =>
    this.setState({
      errors: `Document too large. Max limit 2MB`,
    });

  render() {
    const { merchant_gst } = this.props;
    const isNew = !merchant_gst.p_gstin && !merchant_gst.gstin;
    const title = merchant_gst.gstin ? `Update GST details` : `Add GST details`;

    return (
      <div>
        {isNew ? <ModalHeader title={title} onCloseClick={this.props.closeModal} /> : null}

        <div className="modal-body rzp-gst-content">
          {!isNew ? (
            <div className="rzp-gst">
              <button type="button" className="close" onClick={this.props.closeModal}>
                <i className="i i-close" />
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

              <div className="gst-update-note">
                <Banner>
                  <b>Note:</b> {GST_SUCCESS_MSG}
                </Banner>
              </div>

              <div className="Modal__actions">
                <Link
                  className="btn btn-primary btn-block"
                  to="/profile"
                  onClick={this.props.closeModal}
                >
                  View my GST details
                </Link>
              </div>
            </div>
          ) : (
            <form onSubmit={isNew ? this.addGSTIN : this.updateGSTIN}>
              {!isNew && (
                <div className="help-block">
                  Entered GSTIN will appear on invoices that we send to you.
                </div>
              )}
              <Input
                label="GSTIN"
                autoFocus={true}
                placeholder="19AAAAA1234Y1YY"
                validator={validateGSTIN}
                onBlur={this.onInputFieldBlur}
                required
              />
              {isNew && (
                <div className="gst-update-note">
                  <Banner>
                    <b>Note:</b> {GST_SUCCESS_MSG}
                  </Banner>
                </div>
              )}
              <label className="label-required">GSTIN Certificate</label>
              <Field
                name="certificate"
                component={FileUpload}
                accept={['jpg', 'png', 'pdf']}
                maxSize={2102000}
                onBiggerFileSize={this.onBiggerFileSize}
                onFileChange={this.onGstCertificateFileChange}
                validate={required('Please upload GSTIN certificate')}
                onCloseClick={this.onGstCertificateFileChange}
                required
              />
              <br />
              <label>GSTIN Address</label>
              <div className="label-info">
                <span>
                  Should be as per your GST Certificate. Your business address will also be updated
                  to this <i className="i i-info-circle" />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div>
                        We use your business address to bill the invoices. It should be same as the
                        address on you GST certificate if you want to generate E-invoices.
                      </div>
                    </PopoverBody>
                  </Popover>
                </span>
              </div>
              <div class="Modal__actions">
                <button
                  type="submit"
                  className="btn btn-primary btn-block"
                  disabled={this.state.gstinCertificate === null}
                >
                  Submit
                </button>
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
