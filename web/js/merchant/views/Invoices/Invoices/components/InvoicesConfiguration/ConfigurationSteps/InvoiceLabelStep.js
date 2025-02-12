import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import RadioButton from 'common/ui/Forms/RadioButton';

import { updateConfig } from 'merchant/reducers/config';
import { compose } from 'redux';

const selector = formValueSelector('invoiceLabelStepOnboarding');

class InvoiceLabelStep extends Component {
  componentDidMount() {
    const { config, merchantName } = this.props;

    if (merchantName) {
      // Set value to appropriate radio button is automatically selected.
      this.props.change('invoice_label_field', config.invoice_label_field || 'business_name');
    }
  }

  /**
   * Saves the label.
   * @param {Object} props
   */
  save = (props) => {
    return this.props
      .updateConfig(props)
      .then((_) => {
        this.props.onSwitchStep(null, 1);
        this.props.onStart();
      })
      .catch((err) => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  /**
   * Change handlers.
   * @param {Event} e
   */
  billingLabelChange = (e) => {
    this.props.change('invoice_label_field', e.target.value);
  };

  render() {
    const { businessName, businessDba, merchantName, handleSubmit } = this.props;

    const showBillingLabelSection = businessDba && businessName !== businessDba;

    return (
      <form autoComplete="off">
        <div className="row">
          <div className="col-md-12">
            <small className="help-block">STEP 1/2</small>
            <div className="section-title">Invoice Label:</div>
            <p>Invoices will be issued under this name.</p>
            <Field
              name="invoice_label_field"
              component={RadioButton}
              htmlValue="business_name"
              onChange={this.billingLabelChange}
              label={() => (
                <span>
                  <span className="title">{businessName ? `${businessName} | ` : ''}</span>
                  <span className="description">Registered Name</span>
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
                    <span className="title">{`${businessDba} | `}</span>
                    <span className="description">Billing Label</span>
                  </span>
                )}
              />
            ) : null}
          </div>
        </div>
        <div className="row">
          <div className="col-md-12">
            <div className="Modal__actions">
              <AsyncButton
                type="submit"
                className="btn btn-primary btn-block"
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

export default compose(
  connect(
    (state) => {
      return {
        invoicesIssuedUnder: selector(state, 'invoice_label_field'),
        config: state.config.config,
      };
    },
    {
      updateConfig,
    },
  ),
  reduxForm({
    form: 'invoiceLabelStepOnboarding',
  }),
)(InvoiceLabelStep);
