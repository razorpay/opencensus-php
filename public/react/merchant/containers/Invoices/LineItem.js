import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import TypeAhead from 'rzp/ui/Select/TypeAhead'
import ItemCreation from 'merchant/containers/Items/New'
import * as ModalActions from 'merchant/modules/modals'

const selector = formValueSelector('newInvoice')
@connect(
  (state) => {
    return {
      invoice_line_items: selector(state, 'line_items')
    }
  },
  ModalActions
)
@reduxForm({
  form: 'newInvoice',
  destroyOnUnmount: false
})
export default class InvoiceLineItem extends Component {
  constructor() {
    super(...arguments)
    this.quickCreateItem = ::this.quickCreateItem
    this.updateLineItemRow = ::this.updateLineItemRow
    this.selectItemAndCloseModal = ::this.selectItemAndCloseModal
  }

  quickCreateItem() {
    this.props.openModal({
      component: <ItemCreation
        onSave={this.selectItemAndCloseModal}
        closeModal={this.props.closeModal}
      />
    })
  }

  selectItemAndCloseModal(item) {
    this.updateLineItemRow(item)
    this.props.closeModal()
  }

  updateLineItemRow(item) {
    let fieldName = this.props.fieldName
    this.props.change(`${fieldName}.item_id`, item.id || 'NULL') // since redux-form converts falsy values into empty strings
    this.props.change(`${fieldName}.quantity`, 1)
    this.props.change(`${fieldName}.description`, item.description || '')
    this.props.change(`${fieldName}.amountInINR`, item.amountInINR || '0.00')
  }

  calculateLineItemTotal() {
    let fieldItem = this.props.invoice_line_items[this.props.index]
    return (Number(fieldItem.amountInINR) * Number(fieldItem.quantity)).toFixed(2)
  }

  render() {
    let {
      fieldName,
      index,
      disabled,
      items,
      onRemove
    } = this.props
    let selectedOption = this.props.invoice_line_items[index]

    return (
      <tr>
        <td>
          <span class='remove-row-action' onClick={() => onRemove(index)}>
            <i class='fa fa-times-circle text-danger'></i>
          </span>

          <div class='item-ac-container'>
            <Field
              name={`${fieldName}.item_id`}
              component={TypeAhead}
              options={items}
              selected={selectedOption}
              optionLabelPath='name'
              placeholder='Select an item'
              onChange={this.updateLineItemRow}
              onQuickAdd={this.quickCreateItem}
              disabled={disabled}
            />
            <Field
              name={`${fieldName}.description`}
              component='textarea'
              class='form-control'
              placeholder='Enter item description'
            />
          </div>
        </td>

        <td>
          <Field
            name={`${fieldName}.quantity`}
            component='input'
            class='form-control text-right'
            type='number'
            min={1}
            disabled={disabled}
          />
        </td>

        <td>
          <Field
            name={`${fieldName}.amountInINR`}
            component='input'
            class='form-control text-right'
            type='number'
            disabled={true}
          />
        </td>

        <td class='text-right'>
          {this.calculateLineItemTotal()}
        </td>
      </tr>
    )
  }
}
