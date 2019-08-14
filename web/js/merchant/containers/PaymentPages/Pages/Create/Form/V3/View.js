import { connect } from 'react-redux';
import AmountDisplayField from './Amount/AmountDisplayField';
import UDFDisplayField from './UDF/UDFDisplayField';
import AddUDFButton from './UDF/AddUDFButton';
import AddAmountButton from './Amount/AddAmountButton';

import {
  updateData,
  deleteInSchema,
  updateInSchema,
  addInSchema,
} from 'merchant/modules/wysiwyg';
import { constructFieldSchema } from '../Fields/V3';

@connect(state => ({ ...state.wysiwyg }), {
  updateData,
  deleteInSchema,
  updateInSchema,
  addInSchema,
})
export default class View extends React.PureComponent {
  componentWillReceiveProps(nextProps) {
    if (this.props.payment_page_id !== nextProps.payment_page_id) {
      // this.onCreatorClose(); // TODO: Important controller point to close all the modals
    }
  }

  onSubmitAmountField = (formData, udfFieldIndex) => {
    /*
    const { currency, amount, quantity, allow_multiple_units } = formData;

    this.props.updateData({
      currency,
      amount: amount || null,
      quantity: quantity || null,
      settings: {
        allow_multiple_units: !!allow_multiple_units,
      },
    });
*/
  };

  onDeleteAmountField = amountFieldIndex => {};

  onSubmitUDFField = (formData, udfFieldIndex) => {
    // console.log('FORM DATA.....', formData);
    const fieldSchema = constructFieldSchema(formData);
    // console.log('FIELD SCHEMA...', fieldSchema);

    if (
      !fieldSchema ||
      (fieldSchema.enum && (!formData.enum || !formData.enum.length))
    ) {
      throw 'Invalid field data';
    }

    if (fieldSchema.enum) {
      fieldSchema.enum = formData.enum.concat();
    }

    this.props.updateInSchema({
      field: fieldSchema,
      index: udfFieldIndex,
    });
  };

  onDeleteUDFField = udfFieldIndex => {
    this.props.deleteInSchema(udfFieldIndex);
  };

  validateSameTitleExists = (title, fieldSelfIndex) => {
    const allFieldsTitles = this.props.FORM_SCHEMA.map(f => f.title), // TODO: Currently checking for UDF Field. To add for Amount field labels
      sameTitleIndex = allFieldsTitles.indexOf(title);

    if (sameTitleIndex > -1 && sameTitleIndex !== fieldSelfIndex) {
      return true;
    }
  };

  render() {
    const { paymentPageEntity, FORM_SCHEMA } = this.props;

    if (!paymentPageEntity) {
      return null;
    }

    if (
      paymentPageEntity.id &&
      typeof paymentPageEntity.title === 'undefined'
    ) {
      return (
        <div class="spinner-container">
          <div class="spin-btn large visible" />
        </div>
      );
    }

    return (
      <React.Fragment>
        <div class="UI-form">
          {/*
          <AmountDisplayField
            key={field.name}
            index={field.idx}
            field={field}
            onDeleteAmountField={this.onDeleteAmountField}
            onSubmitAmountField={this.onSubmitAmountField}
            validateSameTitleExists={this.validateSameTitleExists}
          />
        */}
          {FORM_SCHEMA.map((field, idx) => {
            return (
              <UDFDisplayField
                key={field.name}
                index={idx}
                field={field}
                onDeleteUDFField={this.onDeleteUDFField}
                onSubmitUDFField={this.onSubmitUDFField}
                validateSameTitleExists={this.validateSameTitleExists}
              />
            );
          })}

          <div class="Field">
            <div class="Field-label" style={{ opacity: 0.6 }}>
              Add new
            </div>

            <div class="Field-content">
              <AddUDFButton
                onDeleteUDFField={this.onDeleteUDFField}
                onSubmitUDFField={this.onSubmitUDFField}
                validateSameTitleExists={this.validateSameTitleExists}
              />
              <AddAmountButton onSelectField={_ => _} />
            </div>
          </div>

          <FormFooter amountToPay={paymentPageEntity.amount} />
        </div>
      </React.Fragment>
    );
  }
}

const FormFooter = ({ amountToPay }) => (
  <div id="form-footer">
    <img
      id="fin-logo"
      alt="pay-methods"
      src="https://cdn.razorpay.com/static/assets/upi_visa_mc_ae_pc.png"
    />
  </div>
);
