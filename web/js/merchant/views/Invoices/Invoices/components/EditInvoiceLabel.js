import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import * as ModalActions from 'merchant_common/reducers/modals';
import RadioButton from 'common/ui/Forms/RadioButton';
import PropTypes from 'prop-types';
import * as ConfigActions from 'merchant/reducers/config';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { compose } from 'redux';

const selector = formValueSelector('editInvoiceLabel');

class EditInvoiceLabelModal extends Component {
  static propTypes = {
    /**
     * Merchant.
     */
    merchant: PropTypes.object.isRequired,

    /**
     * Current Invoices label.
     */
    current: PropTypes.string.isRequired,

    /**
     * Header for the modal.
     */
    header: PropTypes.string,

    /**
     * Callback for when the label is saved.
     */
    onSave: PropTypes.func,
  };

  static defaultProps = {
    header: 'Issue Invoices Under',
    onSave: () => {},
  };

  constructor() {
    super(...arguments);
    this.state = {};
  }

  /**
   * Saves the label.
   * @param {Object} props
   */
  save = (props) => {
    return this.props
      .updateConfig(props)
      .then((res) => {
        this.props.showNotification({
          type: 'success',
          message: 'Invoice Label updated',
          hidePrevious: true,
        });
        this.props.onSave(props);
      })
      .catch((err) => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  componentDidMount() {
    let { merchant, current } = this.props;
    if (merchant && current) {
      // Set value to appropriate radio button is automatically selected.
      this.props.change('invoice_label_field', current);
    }
  }

  /**
   * Change handler.
   * @param {Event} e
   */
  billingLabelChange = (e) => {
    this.props.change('invoice_label_field', e.target.value);
  };

  render() {
    const { header, invoicesIssuedUnder, merchant, handleSubmit } = this.props;

    return (
      <div>
        <ModalHeader title={header} onCloseClick={this.props.closeModal} />
        <div className="modal-body EditInvoiceLabelModal">
          <Alert type="error" message={this.state.errors} />
          <form autoComplete="off">
            <div className="row">
              <div className="col-md-12">
                <p>The Invoices created from this date onwards will be issued under this name.</p>
              </div>
            </div>
            <div className="row">
              <div className="col-md-12">
                <Field
                  label={() => (
                    <span>
                      <div className="title">{merchant.business_name}</div>
                      <div className="description">Registered Name</div>
                    </span>
                  )}
                  name="invoice_label_field"
                  component={RadioButton}
                  htmlValue="business_name"
                  onChange={this.billingLabelChange}
                />
                <Field
                  label={() => (
                    <span>
                      <div className="title">{merchant.business_dba}</div>
                      <div className="description">Billing Label</div>
                    </span>
                  )}
                  component={RadioButton}
                  name="invoice_label_field"
                  htmlValue="business_dba"
                  onChange={this.billingLabelChange}
                />
              </div>
            </div>
            <div className="row">
              <div className="col-md-12">
                <div className="Modal__actions">
                  <AsyncButton
                    type="submit"
                    className="btn btn-primary btn-block"
                    text="Save"
                    pendingText="Saving..."
                    onClick={handleSubmit(this.save)}
                  />
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return { invoicesIssuedUnder: selector(state, 'invoice_label_field') };
    },
    {
      ...NotificationsActions,
      ...ConfigActions,
      ...ModalActions,
    },
  ),
  reduxForm({
    form: 'editInvoiceLabel',
  }),
)(EditInvoiceLabelModal);
