import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import RadioButton from 'common/ui/Forms/RadioButton';

import { updateConfig } from 'merchant/reducers/config';

const selector = formValueSelector('invoiceLabelStepOnboarding');

@connect(
  state => {
    return {
      invoicesIssuedUnder: selector(state, 'invoice_label_field'),
      config: state.config.config,
    };
  },
  {
    updateConfig,
  }
)
@reduxForm({
  form: 'invoiceLabelStepOnboarding',
})
export default class InvoiceLabelStep extends Component {
  componentDidMount() {
    const { config, merchantName } = this.props;

    if (merchantName) {
      // Set value to appropriate radio button is automatically selected.
      this.props.change(
        'invoice_label_field',
        config.invoice_label_field || 'business_name'
      );
    }
  }

  /**
   * Saves the label.
   * @param {Object} props
   */
  save = props => {
    return this.props
      .updateConfig(props)
      .then(_ => {
        this.props.onSwitchStep(null, 1);
        this.props.onStart();
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  /**
   * Change handlers.
   * @param {Event} e
   */
  billingLabelChange = e => {
    this.props.change('invoice_label_field', e.target.value);
  };

  render() {
    const {
      businessName,
      businessDba,
      merchantName,
      handleSubmit,
    } = this.props;

    const showBillingLabelSection = businessDba && businessName !== businessDba;

    return (
      <form autoComplete="off">
        <div class="row">
          <div class="col-md-12">
            <small class="help-block">STEP 1/2</small>
            <div class="section-title">Invoice Label:</div>
            <p>Invoices will be issued under this name.</p>
            <Field
              name="invoice_label_field"
              component={RadioButton}
              htmlValue="business_name"
              onChange={this.billingLabelChange}
              label={() => (
                <span>
                  <span className="title">
                    {businessName ? `${businessName} | ` : ''}
                  </span>
                  <span class="description">Registered Name</span>
                </span>
              )}
            />
            {showBillingLabelSection ? (
              <Field
                name="invoice_label_field"
                htmlValue="business_dba"
                component={RadioButton}
                onChange={this.billingLabelChange}
                label={() => (
                  <span>
                    <span class="title">{`${businessDba} | `}</span>
                    <span class="description">Billing Label</span>
                  </span>
                )}
              />
            ) : null}
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
      </form>
    );
  }
}
