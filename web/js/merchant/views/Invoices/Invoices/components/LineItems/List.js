import { Component, Fragment } from 'react';
import { reduxForm } from 'redux-form';
import LineItem from './Item';
import Amount from 'common/ui/Amount';
import AmountInWords from 'common/ui/AmountInWords';
import { track } from 'merchant/views/Invoices/ga';
import { compose } from 'redux';

class InvoiceLineItemTable extends Component {
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

  onRemove = (index) => {
    const { fields } = this.props;
    fields.remove(index);
    if (fields.length === 1) {
      this.addInvoiceItem();
    }
  };

  render() {
    const {
      fields,
      gstSlabs,
      items,
      disabled,
      invoice,
      invoiceTotal,
      applyTaxes,
      invoiceCurrency,
      trackLineItem,
    } = this.props;

    return (
      <div>
        <div className="items-header">
          <div className="row">
            <div className="col-md-12">
              <div className="invoice-lineitem table-responsive">
                <table className="table inv__itemtable">
                  <thead>
                    <tr>
                      <th className="lineItem__item">DESCRIPTION</th>
                      <th className="text-right lineItem__amount">RATE/ITEM</th>
                      <th className="text-right lineItem__qty">QTY</th>
                      <th className="text-right lineItem__total">TOTAL</th>
                    </tr>
                  </thead>
                  <tbody className="invoice-lineitem">
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
                        invoiceCurrency={invoiceCurrency}
                        trackLineItem={trackLineItem}
                      />
                    ))}
                    <tr className="addline">
                      <td className="no-border">
                        {!disabled && (
                          <span
                            className="text-primary cursor-pointer Btn--Link Button--transparent Button"
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
          <div className="row inv__padded">
            <div className="col-md-12">
              <div className="invoice-lineitem">
                <table className="table inv__itemtable">
                  <tbody>
                    {applyTaxes && (
                      <Fragment>
                        <tr className="total sub-total">
                          <td />
                          <td className="text-right">Sub Total</td>
                          <td className="text-right">
                            {' '}
                            <Amount
                              value={invoiceTotal.subtotal * 100}
                              currency={invoice.currency}
                            />
                          </td>
                        </tr>
                        {invoiceCurrency === 'INR' && (
                          <tr className="total sub-total">
                            <td />
                            <td className="text-right">Total Tax</td>
                            <td className="text-right" width="30%">
                              <Amount value={invoiceTotal.tax * 100} currency={invoice.currency} />
                            </td>
                          </tr>
                        )}
                      </Fragment>
                    )}

                    {invoice.offer_amount && (
                      <tr className="total">
                        <td />
                        <td className="text-right">
                          <b>Offer Discount</b>
                        </td>
                        <td className="text-right" width="30%">
                          <b>
                            - <Amount value={invoice.offer_amount} currency={invoiceCurrency} />
                          </b>
                        </td>
                      </tr>
                    )}

                    <tr className="total">
                      <td />
                      <td className="text-right">
                        <b>Total Amount</b>
                      </td>
                      <td className="text-right" width="30%">
                        <b>
                          <Amount value={invoiceTotal.total * 100} currency={invoiceCurrency} />
                        </b>
                      </td>
                    </tr>
                    {!invoice.subscription_id && invoiceCurrency === 'INR' && (
                      <tr className="total amount-words">
                        <td colSpan="3" className="text-right">
                          <AmountInWords
                            amount={invoiceTotal.total}
                            prefix="(In Words)"
                            suffix="/-"
                            currency={invoiceCurrency}
                          />
                        </td>
                      </tr>
                    )}
                    {invoice.amount_paid ? (
                      <tr className="text-success amount-paid">
                        <td />
                        <td className="text-right">
                          <b>Amount Paid</b>
                        </td>
                        <td className="text-right" width="30%">
                          <b>
                            <Amount value={invoice.amount_paid} currency={invoice.currency} />
                          </b>
                        </td>
                      </tr>
                    ) : null}

                    {invoice.amount_paid ? (
                      <tr className="total">
                        <td />
                        <td className="text-right">
                          <b>Amount Due</b>
                        </td>
                        <td className="text-right" width="30%">
                          <b>
                            <Amount value={invoice.amount_due} currency={invoice.currency} />
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

export default compose(
  reduxForm({
    form: 'newInvoice',
    destroyOnUnmount: false,
  }),
)(InvoiceLineItemTable);
