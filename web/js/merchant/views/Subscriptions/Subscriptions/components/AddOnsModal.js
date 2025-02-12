import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'common/ui/Forms/InputField';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import { required } from 'common/utils/validators';
import { saveAddOn } from 'merchant/reducers/addons';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { AmountTooltip } from 'common/ui/Amount';
import { compose } from 'redux';

class CreateAddOnModal extends Component {
  state = {};

  UNSAFE_componentWillMount() {
    const { addon, subscriptionId, currency = 'INR' } = this.props;

    const initProps = {
      addon,
      subscription_id: subscriptionId,
      item: { currency },
      quantity: 1,
    };

    this.props.initialize(initProps);
  }

  handleSubmit = (props) => {
    return this.props
      .saveAddOn(props)
      .then((response) => {
        this.props.showNotification({
          type: 'success',
          message: 'Add-on included successfully',
        });
        this.props.onSave(response.data.id); // For highlighting the row
      })
      .catch((err) => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, invalid, addon, currency } = this.props;

    return (
      <div className="addon-create">
        <ModalHeader
          title={addon && addon.id ? 'Edit Add-on' : 'Include Add-on'}
          onCloseClick={this.props.closeModal}
        />

        <div className="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.handleSubmit)}>
            <div className="form-group">
              <label className="colcontrol-label label-required">Name</label>
              <Field
                name="item[name]"
                component={InputField}
                className="form-control"
                validate={required()}
              />
            </div>

            <div className="form-group">
              <label className="control-label">Description</label>
              <Field
                name="item[description]"
                placeholder="Enter optional  description here"
                component="textarea"
                className="form-control"
              />
            </div>

            <div className="form-group">
              <div style={{ display: 'inline-block', width: '62%' }}>
                <label className="control-label label-required price-per-unit">
                  Price per unit (in{' '}
                  <AmountTooltip currency={currency} parentQuerySelector=".Modal">
                    {window.currencyList[currency].symbol}
                  </AmountTooltip>
                  )
                </label>
                <Field
                  name="item[amount]"
                  placeholder="0.00"
                  component={InputField}
                  className="form-control"
                  validate={required()}
                />
              </div>
              <div
                style={{
                  display: 'inline-block',
                  width: '28%',
                  float: 'right',
                }}
              >
                <label className="control-label label-required">Quantity</label>
                <Field
                  name="quantity"
                  component={InputField}
                  className="form-control"
                  validate={required()}
                />
              </div>
            </div>

            <div className="Modal__actions clearfix" style={{ paddingTop: '16px' }}>
              <AsyncButton
                type="submit"
                className="btn btn-primary col-lg-12"
                text="Include"
                disabled={invalid}
                pendingText="Creating..."
                onClick={handleSubmit(this.handleSubmit)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect(null, {
    saveAddOn,
    showNotification,
    ...ModalActions,
  }),
  reduxForm({
    form: 'newAddOn',
  }),
)(CreateAddOnModal);
