import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import Banner from 'rzp/ui/Banner';
import InputField from 'rzp/ui/Forms/InputField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { saveGST } from 'merchant/reducers/profile';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { required, validateGSTIN } from 'rzp/utils/validators';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import ShowWhen from 'merchant/components/ShowWhen';

const selector = formValueSelector('newGST');
@connect(
  state => {
    return {
      merchant_gst: state.profile.merchant_gst,
      rzp_gst: state.profile.rzp_gst,
      session: state.session,
    };
  },
  {
    saveGST,
    updateSession,
    ...ModalActions,
    ...NotificationsActions,
  }
)
@RTracking(() => window.rzpQ.component('AddGST'))
@reduxForm({
  form: 'newGST',
})
export default class AddGST extends Component {
  state = {};

  gst_success_msg = 'Entered GSTIN will be applicable only from current month onwards.';

  componentWillMount() {
    let { merchant_gst } = this.props;

    let initialValues = {
      gst_type: 'gstin',
    };

    this.props.initialize({
      ...initialValues,
      ...this.props.merchant_gst,
    });
  }

  save = ({ gst_type, ...otherProps }) => {
    let fieldProps = {};

    fieldProps[gst_type] = otherProps[gst_type];

    const isNew =
      !this.props.merchant_gst.p_gstin && !this.props.merchant_gst.gstin;

    return this.props
      .saveGST(fieldProps)
      .then(item => {
        const {
          updateSession,
          tracking,
          showNotification,
          closeModal,
        } = this.props;

        let user = new User({
          ...this.props.session.user,
          ...item.data,
        });

        updateSession({
          user,
        });

        this.setState({
          saved: true,
        });

        tracking.trackEvent(
          window.rzpQ.onbr().initiated('dash.my_account_actions', {
            action: 'Add_GSTIN_Successful',
          })
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
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const {
      handleSubmit,
      merchant_gst,
      rzp_gst,
      selectedGSTType,
      session,
    } = this.props;
    const isNew = !merchant_gst.p_gstin && !merchant_gst.gstin;
    const isEditable =
      isNew && this.props.session.user.isAllowedEdit('profile_gst');

    return (
      <div>
        {isNew ? (
          <ModalHeader
            title="Add your GST Details"
            onCloseClick={this.props.closeModal}
          />
        ) : null}

        <div class="modal-body rzp-gst-content">
          {!isNew ? (
            <div class="rzp-gst">
              <button
                type="button"
                class="close"
                onClick={this.props.closeModal}
              >
                <i class="i i-close" />
              </button>

              {session.user.isOrgRZP && (
                <React.Fragment>
                  <label>Razorpay's GST number</label>
                  <div>
                    <span>{rzp_gst.gstin}</span>
                    <CustomClipboard value={rzp_gst.gstin}>
                      <button
                        class="btn btn-default btn-xs"
                        style={{ marginLeft: '5px' }}
                      >
                        Copy GST
                      </button>
                    </CustomClipboard>
                  </div>
                </React.Fragment>
              )}

              <hr />
            </div>
          ) : null}

          <Alert type="error" message={this.state.errors} />

          {this.state.saved ? (
            <div>
              <div>
                Your GST details have been updated. You can access it anytime
                from the{' '}
                <Link to="/profile" onClick={this.props.closeModal}>
                  Profile Section
                </Link>
              </div>

              <div className="gst-update-note">
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
                    placeholder="19AAAAAA1234YYY"
                    validate={[required(), validateGSTIN]}
                    disabled={!isEditable}
                  />
                </div>
                {isNew && (
                  <div className="gst-update-note">
                    <Banner>
                      <b>Note:</b> {this.gst_success_msg}
                    </Banner>
                  </div>
                )}
              </div>

              <div class="help-block">
                <span>
                  GSTIN once submitted cannot be updated via dashboard. To
                  update it, please <Link to="#ticket">write to support</Link>
                </span>
              </div>

              {isEditable && (
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
            </form>
          )}
        </div>
      </div>
    );
  }
}
