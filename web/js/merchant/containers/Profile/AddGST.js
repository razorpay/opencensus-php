import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import Banner from 'rzp/ui/Banner';
import InputField from 'rzp/ui/Forms/InputField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { saveGST } from 'merchant/modules/profile';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import { required, validateGSTIN } from 'rzp/utils/validators';

const selector = formValueSelector('newGST');
@connect(
  state => {
    return {
      merchant_gst: state.profile.merchant_gst,
      rzp_gst: state.profile.rzp_gst,
    };
  },
  {
    saveGST,
    ...ModalActions,
    ...NotificationsActions,
  }
)
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

    return this.props
      .saveGST(fieldProps)
      .then(item => {
        if (this.props.openedFromTopbar) {
          this.setState({
            saved: true,
          });
        } else {
          this.props.showNotification({
            type: 'success',
            message: `Your GST details have been updated. Note: ${
              this.gst_success_msg
            }`,
          });
          this.props.closeModal();
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
      openedFromTopbar,
    } = this.props;
    let isNew = !merchant_gst.p_gstin && !merchant_gst.gstin;

    return (
      <div>
        {!openedFromTopbar ? (
          <ModalHeader
            title={isNew ? 'Add your GST Details' : 'Edit GST Details'}
            onCloseClick={this.props.closeModal}
          />
        ) : null}

        <div class="modal-body rzp-gst-content">
          {openedFromTopbar ? (
            <div class="rzp-gst">
              <button
                type="button"
                class="close"
                onClick={this.props.closeModal}
              >
                <i class="i i-close" />
              </button>

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

              <hr />

              <label>Add your GST number</label>
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
            <form onSubmit={handleSubmit(this.save)}>
              <div class="help-block">
                Entered GSTIN will appear on invoices that we send to you
              </div>

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
                  />
                </div>
                <div className="gst-update-note">
                  <Banner>
                    <b>Note:</b> {this.gst_success_msg}
                  </Banner>
                </div>
              </div>

              <div class="help-block">
                <span>
                  Final GSTIN once submitted cannot be updated via dashboard. To
                  update it, write to us at{' '}
                  <a href="mailto:support@razorpay.com">support@razorpay.com</a>
                </span>
              </div>

              <div class="Modal__actions">
                <AsyncButton
                  type="submit"
                  class="btn btn-primary btn-block"
                  text="Save my GST Details"
                  pendingText="Saving..."
                  onClick={handleSubmit(this.save)}
                />
              </div>
            </form>
          )}
        </div>
      </div>
    );
  }
}
