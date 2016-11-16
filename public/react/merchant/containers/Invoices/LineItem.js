import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import ReduxTypeAhead from 'rzp/ui/Forms/ReduxTypeAhead'

const selector = formValueSelector('newInvoice')
@connect(
  (state) => {
    return {
      invoice_line_items: selector(state, 'line_items')
    }
  }
)
@reduxForm({
  form: 'newInvoice',
  destroyOnUnmount: false
})
export default class InvoiceLineItem extends Component {
  calculateLineItemTotal() {
    let fieldItem = this.props.invoice_line_items[this.props.index]
    return (Number(fieldItem.rate) * Number(fieldItem.quantity)).toFixed(2)
  }

  render() {
    let { fieldName, fieldItem, index, items, onRemove } = this.props

    return (
      <tr>
        <td>
          <Field
            name={`${fieldName}.item`}
            component={ReduxTypeAhead}
            options={items}
            selected={fieldItem.item}
            selectedLabel='name'
            optionComponent={(option) => <span>{option.name}</span>}
            searchIndices={['name']}
            placeholder='Select an item'
            onChange={(selectedItem) => {
              this.props.change(`${fieldName}.rate`, selectedItem.rate || '0.00')
              setTimeout(() => {
                this.calculateLineItemTotal()
              }, 0)
            }}
          />
        </td>

        <td>
          <Field
            name={`${fieldName}.quantity`}
            component='input'
            class='form-control text-right'
            type='number'
            min={1}
          />
        </td>

        <td>
          <Field
            name={`${fieldName}.rate`}
            component='input'
            class='form-control text-right'
            type='number'
          />
        </td>

        <td class='text-right'>
          {this.calculateLineItemTotal()}

          <span class='remove-row-action' onClick={() => onRemove(index)}>
            <i class='fa fa-times-circle text-danger'></i>
          </span>
        </td>
      </tr>
    )
  }
}
