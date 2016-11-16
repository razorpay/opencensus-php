import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import LineItem from './LineItem'

const selector = formValueSelector('newInvoice')
@connect(
  (state) => {
    return {
      invoice_line_items: selector(state, 'line_items') || []
    }
  }
)
@reduxForm({
  form: 'newInvoice',
  destroyOnUnmount: false
})

export default class InvoiceLineItemTable extends Component {
  calculateItemsSubTotal() {
    return this.props.invoice_line_items.reduce((total, line_item) => {
      return total + (Number(line_item.quantity) * Number(line_item.rate))
    }, 0).toFixed(2)
  }

  calculateInvoiceTotal() {
    return this.calculateItemsSubTotal()
  }

  render() {
    let { fields, items } = this.props

    return (
      <div class='invoice-lineitem'>
        <table class='table'>
          <thead>
            <tr>
              <th>Item Details</th>
              <th style={{width: '12%'}} class='text-right'>Quantity</th>
              <th style={{width: '15%'}} class='text-right'>Rate</th>
              <th class='text-right'>Amount</th>
            </tr>
          </thead>
          <tbody>
            {
              fields.map((fieldName, idx, item) =>
                <LineItem
                  key={`line_item_${idx}`}
                  index={idx}
                  fieldName={fieldName}
                  fieldItem={item}
                  items={items}
                  onRemove={(index) => {
                    fields.remove(index)
                    if (fields.length === 1) {
                      fields.push({
                        item: null,
                        quantity: 1,
                        rate: '0.00',
                      })
                    }
                  }}
                />
              )
            }
          </tbody>
        </table>

        <div class='form-group clearfix'>
          <div class='invoice-total pull-right'>
            <dl class='dl-horizontal'>
              <dt>SUB TOTAL:</dt>
              <dd class='text-right'>{this.calculateItemsSubTotal()}</dd>

              <dt>TOTAL:</dt>
              <dd class='text-right'>{this.calculateInvoiceTotal()}</dd>
            </dl>
          </div>

          <button
            class='btn btn-default add-line-item'
            style={{
              marginLeft: '40px'
            }}
            type='button'
            onClick={() => fields.push({
              item: null,
              quantity: 1,
              rate: '0.00'
            })}
          >
            ADD ITEM
          </button>
        </div>
      </div>
    )
  }
}
