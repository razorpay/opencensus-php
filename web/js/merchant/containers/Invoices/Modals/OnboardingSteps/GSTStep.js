import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import RadioButton from 'rzp/ui/Forms/RadioButton';
import InputField from 'rzp/ui/Forms/InputField';

import { saveGST } from 'merchant/modules/profile';
import { closeModal } from 'rzp/modules/modals';
import { validateGSTIN } from 'rzp/utils/validators';
import LocalStorageService from 'rzp/utils/localStorage';
import * as NotificationsActions from 'rzp/modules/notifications';

const selector = formValueSelector('gstStepOnboarding');

@connect(
  state => {
    return {
      invoicesGstSelected: selector(state, 'invoice_gst_select'),
    };
  },
  {
    saveGST,
    closeModal,
    ...NotificationsActions,
  }
)
@reduxForm({
  form: 'gstStepOnboarding',
})
export default class GSTStepOnboarding extends Component {
  componentDidMount() {
    const { merchantGstin } = this.props;

    this.props.change('invoice_gst_select', 'with_gst');

    if (merchantGstin) {
      this.props.change('gstin', merchantGstin);
    }
  }

  gstChange = (propKey, e) => {
    this.props.change(propKey, e.target.value);
  };

  save = props => {
    if (props.invoice_gst_select === 'without_gst') {
      return this.saveWithoutGst();
    } else if (props.invoice_gst_select === 'with_gst') {
      return this.saveWithGst({ gstin: props.gstin });
    }
  };

  saveWithGst = props => {
    return this.props
      .saveGST(props)
      .then(resp => {
        LocalStorageService.setItem('gst_invoice_enabled', true);
        this.successNotification();
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err[0],
        });
      });
  };

  saveWithoutGst = () => {
    LocalStorageService.setItem('gst_invoice_enabled', false);
    this.successNotification();
  };

  successNotification = () => {
    this.props.closeModal();
    this.props.showNotification({
      type: 'success',
      message:
        'Invoices configured successfully. Start creating your first Invoice.',
    });
    this.props.onStart();
  };

  render() {
    const { merchantGstin, onSwitchStep, handleSubmit } = this.props;

    return (
      <form autoComplete="off">
        <div class="row">
          <div class="col-md-12">
            <small class="help-block">STEP 2/2</small>
            <span class="section-title" style={{ display: 'inline-block' }}>
              GST Details:
            </span>
            {merchantGstin ? (
              <Fragment>
                <div class="m-t">
                  Your Business GSTIN:{' '}
                  <span class="section-title">{merchantGstin}</span>
                </div>
                <p class="help-block p-t">
                  To update your GST details, reach out to us at{' '}
                  <a href="mailto:support@razorpay.com">support@razorpay.com</a>
                </p>
              </Fragment>
            ) : (
              <Fragment>
                <Field
                  name="invoice_gst_select"
                  component={RadioButton}
                  htmlValue="with_gst"
                  onChange={e => this.gstChange('invoice_gst_select', e)}
                  label={() => (
                    <span>
                      <span class="title">Business Registered for GST</span>
                    </span>
                  )}
                />
                {this.props.invoicesGstSelected === 'with_gst' && (
                  <div class="invoice-gst-form-label">
                    <div class="row no-margin">
                      <label class="section-title col-md-2 no-padding">
                        Enter GSTIN:
                      </label>
                      <div class="col-md-10 no-padding">
                        <Field
                          name="gstin"
                          component={InputField}
                          class="form-control"
                          onChange={e => this.gstChange('gstin', e)}
                          autoFocus={true}
                          validate={[validateGSTIN]}
                        />
                        <p class="m-t">
                          GSTIN once submitted cannot be updated via dashboard.
                          To update it, write to us at{' '}
                          <a href="mailto:support@razorpay.com">
                            support@razorpay.com
                          </a>.
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                <Field
                  name="invoice_gst_select"
                  component={RadioButton}
                  htmlValue="without_gst"
                  onChange={e => this.gstChange('invoice_gst_select', e)}
                  label={() => (
                    <span>
                      <span class="title">GST Not Required</span>
                    </span>
                  )}
                />
                {this.props.invoicesGstSelected === 'without_gst' && (
                  <div class="invoice-gst-form-label">
                    You will not be able to create GST invoices without a GSTIN.
                    You can add your GSTIN later anytime from the dasboard.
                  </div>
                )}
              </Fragment>
            )}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="Modal__actions">
              <button
                class="btn btn-default m-r"
                onClick={e => onSwitchStep(e, 0)}
              >
                Previos Step
              </button>
              <AsyncButton
                type="submit"
                class="btn btn-primary"
                text={`Start Creating ${
                  this.props.invoicesGstSelected === 'without_gst' ? 'Non-' : ''
                }GST Invoices`}
                pendingText="Saving..."
                onClick={handleSubmit(
                  merchantGstin ? this.successNotification : this.save
                )}
              />
            </div>
          </div>
        </div>{' '}
      </form>
    );
  }
}
