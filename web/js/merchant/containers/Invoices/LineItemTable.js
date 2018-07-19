import { Component, Fragment } from 'react';
import { Field, reduxForm } from 'redux-form';
import LineItem from './LineItem';
import Amount from 'rzp/ui/Amount';
import { track } from './ga';

@reduxForm({
  form: 'newInvoice',
  destroyOnUnmount: false,
})
export default class InvoiceLineItemTable extends Component {
  addInvoiceItem = () => {
    track({
      eventAction: 'Click - Add Line',
    });

    this.props.fields.push({
      item_id: '',
      quantity: 1,
      amountInINR: '0.00',
    });
  };

  /**
   * Removes an item from the Invoice.
   * @param {Number} index
   */
  onRemove = index => {
    const { fields } = this.props;
    fields.remove(index);
    if (fields.length === 1) {
      this.addInvoiceItem();
    }
  };

  render() {
    let {
      fields,
      gstSlabs,
      items,
      disabled,
      invoice,
      invoiceTotal,
      applyTaxes,
    } = this.props;

    return (
      <div>
        <div class="items-header">
          <div class="row">
            <div class="col-md-12">
              <div class="invoice-lineitem table-responsive">
                <table class="table inv__itemtable">
                  <thead>
                    <tr>
                      <th class="lineItem__item">DESCRIPTION</th>
                      <th class="text-right lineItem__amount">RATE</th>
                      <th class="text-right lineItem__qty">QTY</th>
                      <th class="text-right lineItem__total">TOTAL</th>
                    </tr>
                  </thead>
                  <tbody class="invoice-lineitem">
                    {fields.map((fieldName, idx) => (
                      <LineItem
                        key={`line_item_${idx}`}
                        index={idx}
                        disabled={disabled}
                        fieldName={fieldName}
                        items={items}
                        gstSlabs={gstSlabs}
                        onRemove={this.onRemove}
                        applyTaxes={applyTaxes}
                      />
                    ))}
                    <tr class="addline">
                      <td class="no-border">
                        {!disabled && (
                          <span
                            class="text-primary cursor-pointer btn-link"
                            onClick={this.addInvoiceItem}
                          >
                            + Add Line Item
                          </span>
                        )}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="row inv__padded">
            <div class="col-md-12">
              <div class="invoice-lineitem">
                <table class="table inv__itemtable">
                  <tbody>
                    {applyTaxes && (
                      <Fragment>
                        <tr class="total sub-total">
                          <td />
                          <td class="text-right">Sub Total</td>
                          <td class="text-right">₹{invoiceTotal.subtotal}</td>
                        </tr>
                        <tr class="total sub-total">
                          <td />
                          <td class="text-right">Total Tax</td>
                          <td class="text-right" width="30%">
                            ₹{invoiceTotal.tax}
                          </td>
                        </tr>
                      </Fragment>
                    )}
                    <tr class="total">
                      <td />
                      <td class="text-right">
                        <b>Total Amount</b>
                      </td>
                      <td class="text-right" width="30%">
                        <b>₹{invoiceTotal.total}</b>
                      </td>
                    </tr>

                    {invoice.amount_paid ? (
                      <tr class="text-success amount-paid">
                        <td />
                        <td class="text-right">
                          <b>Amount Paid</b>
                        </td>
                        <td class="text-right" width="30%">
                          <b>
                            <Amount
                              value={invoice.amount_paid}
                              currency={invoice.currency}
                            />
                          </b>
                        </td>
                      </tr>
                    ) : null}

                    {invoice.amount_paid ? (
                      <tr class="total">
                        <td />
                        <td class="text-right">
                          <b>Amount Due</b>
                        </td>
                        <td class="text-right" width="30%">
                          <b>
                            <Amount
                              value={invoice.amount_due}
                              currency={invoice.currency}
                            />
                          </b>
                        </td>
                      </tr>
                    ) : null}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
