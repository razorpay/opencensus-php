import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import * as ModalActions from 'rzp/modules/modals';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import InputField from 'rzp/ui/Forms/InputField';
import PropTypes from 'prop-types';
import * as ConfigActions from 'merchant/modules/config';
import * as NotificationsActions from 'rzp/modules/notifications';

const selector = formValueSelector('invoiceOnboarding');

@connect(
  state => {
    return {
      invoicesIssuedUnder: selector(state, 'invoice_label_field'),
      invoicesGstSelected: selector(state, 'invoice_gst_select'),
    };
  },
  {
    ...NotificationsActions,
    ...ConfigActions,
    ...ModalActions,
  }
)
@reduxForm({
  form: 'invoiceOnboarding',
})
export default class InvoicesOnboarding extends Component {
  static propTypes = {
    /**
     * Merchant.
     */
    merchant: PropTypes.object.isRequired,

    /**
     * Callback for once the details are saved.
     */
    onStart: PropTypes.func,

    /**
     * Callback for modal close.
     */
    onCloseClick: PropTypes.func,
  };

  static defaultProps = {
    onStart: () => {},
  };

  constructor() {
    super(...arguments);
    this.state = {
      //- step 0 => GST label selection form, step 1 => GST details form
      currentFormStep: 0,
    };
  }

  /**
   * Saves the label.
   * @param {Object} props
   */
  save = data => {
    let newProps = { ...data };

    // TODO: remove deletion;
    delete newProps.invoice_gst_select;

    return this.props
      .updateConfig(newProps)
      .then(res => {
        // this.props.showNotification({
        //   type: 'success',
        //   message: 'Invoices Enabled',
        //   hidePrevious: true,
        // });

        // this.props.onStart(newProps);
        this.switchStep(null, 1);
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  componentWillMount() {
    const { invoiceLabelField } = this.props;

    // if invoice label is selected, show gst details step
    if (invoiceLabelField) {
      this.setState({
        currentFormStep: 1,
      });
    }
  }

  componentDidMount() {
    if (this.props.merchant) {
      const { invoiceLabelField } = this.props;

      // Set value to appropriate radio button is automatically selected.
      this.props.change(
        'invoice_label_field',
        invoiceLabelField || 'business_name'
      );
      this.props.change('invoice_gst_select', 'with_gst');
    }
  }

  /**
   * Change handlers.
   * @param {Event} e
   */
  billingLabelChange = e => {
    this.props.change('invoice_label_field', e.target.value);
  };

  gstDetailsChange = e => {
    this.props.change('invoice_gst_select', e.target.value);
  };

  switchStep = (e, step) => {
    e && e.preventDefault();
    this.setState({ currentFormStep: step });
  };

  render() {
    const { merchant, handleSubmit, onCloseClick } = this.props;
    const { currentFormStep } = this.state;

    const { business_name, business_dba } = merchant;

    const showBillingLabelSection =
      business_dba && business_name !== business_dba;

    return (
      <div class="InvoicesOnboardingModal">
        <ModalHeader title="Configure Invoices" onCloseClick={onCloseClick} />
        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />
          <div class="row">
            <div class="col-md-12">
              <form autoComplete="off">
                <div class="row">
                  <div class="col-md-12">
                    <p>
                      Confirm the following details first to start creating GST
                      invoices:
                    </p>
                  </div>
                </div>
                <hr />
                {currentFormStep === 0 && (
                  <Fragment>
                    <div class="row">
                      <div class="col-md-12">
                        <small class="help-block">STEP 1/2</small>
                        <div class="section-title">Invoice Label:</div>
                        <p>Invoices will be issued under this name.</p>
                        {showBillingLabelSection ? (
                          <Fragment>
                            <Field
                              name="invoice_label_field"
                              component={RadioButton}
                              htmlValue="business_name"
                              onChange={this.billingLabelChange}
                              label={() => (
                                <span>
                                  <span class="title">{`${
                                    merchant.business_name
                                  } | `}</span>
                                  <span class="description">
                                    Registered Name
                                  </span>
                                </span>
                              )}
                            />
                            <Field
                              name="invoice_label_field"
                              htmlValue="business_dba"
                              component={RadioButton}
                              onChange={this.billingLabelChange}
                              label={() => (
                                <span>
                                  <span class="title">{`${
                                    merchant.business_dba
                                  } | `}</span>
                                  <span class="description">Billing Label</span>
                                </span>
                              )}
                            />
                          </Fragment>
                        ) : (
                          <Fragment>
                            <span class="title">{`${merchant.name} | `}</span>
                            <span class="description">Registered Name</span>
                          </Fragment>
                        )}
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-12">
                        <div class="Modal__actions">
                          <AsyncButton
                            type="submit"
                            class="btn btn-primary btn-block"
                            text="Next Step: GST Details"
                            pendingText="Saving..."
                            onClick={handleSubmit(this.save)}
                          />
                        </div>
                      </div>
                    </div>
                  </Fragment>
                )}
                {currentFormStep === 1 && (
                  <Fragment>
                    <div class="row">
                      <div class="col-md-12">
                        <small class="help-block">STEP 2/2</small>
                        <span
                          class="section-title"
                          style={{ display: 'inline-block' }}
                        >
                          GST Details:
                        </span>
                        {merchant.gstin ? (
                          <Fragment>
                            <div class="m-t">
                              Your Business GSTIN:{' '}
                              <span class="section-title">
                                {merchant.gstin}
                              </span>
                            </div>
                            <p class="help-block p-t">
                              To update your GST details, reach out to us at{' '}
                              <a href="mailto:support@razorpay.com">
                                support@razorpay.com
                              </a>
                            </p>
                          </Fragment>
                        ) : (
                          <Fragment>
                            <Field
                              name="invoice_gst_select"
                              component={RadioButton}
                              htmlValue="with_gst"
                              onChange={this.gstDetailsChange}
                              label={() => (
                                <span>
                                  <span class="title">
                                    Business Registered for GST
                                  </span>
                                </span>
                              )}
                            />
                            {this.props.invoicesGstSelected === 'with_gst' && (
                              <div class="invoice-gst-form-label">
                                <div class="row no-margin">
                                  <label class="section-title col-md-2 no-padding">
                                    Enter GSTIN:
                                  </label>
                                  <div class="col-md-9 no-padding">
                                    <Field
                                      name="gstin"
                                      component={InputField}
                                      class="form-control"
                                      autoFocus={true}
                                    />
                                    <p class="m-t">
                                      GSTIN once submitted cannot be updated via
                                      dashboard. To update it, write to us at{' '}
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
                              onChange={this.gstDetailsChange}
                              label={() => (
                                <span>
                                  <span class="title">GST Not Required</span>
                                </span>
                              )}
                            />
                            {this.props.invoicesGstSelected ===
                              'without_gst' && (
                              <div class="invoice-gst-form-label">
                                You will not be able to create GST invoices
                                without a GSTIN. You can add your GSTIN later
                                anytime from the dasboard.
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
                            onClick={e => this.switchStep(e, 0)}
                          >
                            Previos Step
                          </button>
                          <AsyncButton
                            type="submit"
                            class="btn btn-primary"
                            text={`Start Creating ${
                              this.props.invoicesGstSelected === 'without_gst'
                                ? 'Non-'
                                : ''
                            }GST Invoices`}
                            pendingText="Saving..."
                            onClick={handleSubmit(this.save)}
                          />
                        </div>
                      </div>
                    </div>
                  </Fragment>
                )}
              </form>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
