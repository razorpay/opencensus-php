import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import * as ModalActions from 'rzp/modules/modals';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import PropTypes from 'prop-types';
import * as ConfigActions from 'merchant/modules/config';
import * as NotificationsActions from 'rzp/modules/notifications';
import AddGST from 'merchant/containers/Profile/AddGST';

const selector = formValueSelector('invoiceOnboarding');

@connect(
  state => {
    return {
      invoicesIssuedUnder: selector(state, 'invoice_label_field'),
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
    this.state = {};
  }

  /**
   * Saves the label.
   * @param {Object} props
   */
  save = props => {
    return this.props
      .updateConfig(props)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: 'Invoices Enabled',
          hidePrevious: true,
        });
        this.props.onStart(props);
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  componentDidMount() {
    if (this.props.merchant) {
      // Set value to appropriate radio button is automatically selected.
      this.props.change('invoice_label_field', 'business_name');
    }
  }

  /**
   * Change handler.
   * @param {Event} e
   */
  billingLabelChange = e => {
    this.props.change('invoice_label_field', e.target.value);
  };

  /**
   * Shows the GST modals.
   */
  showGSTModal = e => {
    e && e.preventDefault();
    this.props.openModal({
      size: 'small',
      component: <AddGST reloadAfterSave={true} />,
    });
  };

  render() {
    const { merchant, handleSubmit, onCloseClick } = this.props;

    const { business_name, business_dba } = merchant;

    const showBillingLabelSection =
      business_dba && business_name !== business_dba;

    return (
      <div class="InvoicesOnboardingModal">
        <ModalHeader
          title="Getting started with Invoices!"
          onCloseClick={onCloseClick}
        />
        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />
          <div class="row">
            <div class="col-md-7 col-sm-12">
              <form autoComplete="off">
                <div class="row">
                  <div class="col-md-12">
                    <p>
                      Now create GST-ready invoices instantly. Confirm the
                      following details and start creating invoices.
                    </p>
                  </div>
                </div>
                {merchant.gstin || merchant.p_gstin ? (
                  <div class="row">
                    <div class="col-md-12">
                      <span
                        class="section-title"
                        style={{ display: 'inline-block' }}
                      >
                        GSTIN:{' '}
                      </span>
                      <span class="title">
                        {' '}
                        {merchant.gstin || merchant.p_gstin}
                      </span>
                      <p>
                        To update your GST details, reach out to us at{' '}
                        <a href="mailto:support@razorpay.com">
                          support@razorpay.com
                        </a>
                      </p>
                    </div>
                  </div>
                ) : (
                  <div class="row">
                    <div class="col-md-12">
                      <span class="section-title">GSTIN: </span>No GSTIN Added
                      <p>
                        <a href="#" onClick={this.showGSTModal}>
                          Add GST Details
                        </a>
                      </p>
                    </div>
                  </div>
                )}
                <div class="row">
                  <div class="col-md-12">
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
                              <div class="title">{merchant.business_name}</div>
                              <div class="description">Registered Name</div>
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
                              <div class="title">{merchant.business_dba}</div>
                              <div class="description">Billing Label</div>
                            </span>
                          )}
                        />
                      </Fragment>
                    ) : (
                      <Fragment>
                        <div class="title">{merchant.name}</div>
                        <div class="description">Registered Name</div>
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
                        text="Start Creating Invoices"
                        pendingText="Saving..."
                        onClick={handleSubmit(this.save)}
                      />
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
