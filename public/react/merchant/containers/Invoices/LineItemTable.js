import { Component } from 'react';
import { Field, reduxForm } from 'redux-form';
import LineItem from './LineItem';

@reduxForm({
  form: 'newInvoice',
  destroyOnUnmount: false,
})
export default class InvoiceLineItemTable extends Component {
  addInvoiceItem = () => {
    this.props.fields.push({
      item_id: '',
      quantity: 1,
      amountInINR: '0.00',
    });
  };

  render() {
    let { fields, items, disabled, invoiceTotal } = this.props;

    return (
      <div class="invoice-lineitem">
        <table class="table">
          <thead>
            <tr>
              <th style={{ width: '50%' }}>DESCRIPTION</th>
              <th class="text-right">RATE</th>
              <th style={{ width: '15%' }} class="text-right">QTY</th>
              <th class="text-right">TOTAL</th>
            </tr>
          </thead>
          <tbody>
            {fields.map((fieldName, idx) => (
              <LineItem
                key={`line_item_${idx}`}
                index={idx}
                disabled={disabled}
                fieldName={fieldName}
                items={items}
                onRemove={index => {
                  fields.remove(index);
                  if (fields.length === 1) {
                    this.addInvoiceItem();
                  }
                }}
              />
            ))}
            <tr class="total">
              <td class="no-border">
                {!disabled &&
                  <button
                    class="btn btn-default add-line-item"
                    type="button"
                    onClick={this.addInvoiceItem}
                  >
                    ADD ITEM
                  </button>}
              </td>
              <td class="text-right">Sub Total</td>
              <td colSpan="2" class="text-right">₹ {invoiceTotal}</td>
            </tr>
            <tr class="total">
              <td class="no-border" />
              <td class="text-right"><b>Total</b></td>
              <td colSpan="2" class="text-right"><b>₹ {invoiceTotal}</b></td>
            </tr>
          </tbody>
        </table>
      </div>
    );
  }
}
