import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import InlineField from 'rzp/ui/Forms/InlineField'
import InputField from 'rzp/ui/Forms/InputField'
import TypeAhead from 'rzp/ui/Select/TypeAhead'
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea'
import ItemCreation from 'merchant/containers/Items/New'
import * as ModalActions from 'merchant/modules/modals'
import { findBy } from 'rzp/utils/rzp-utils'

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

  quickCreateItem({ searchTerm = '' }) {
    this.props.openModal({
      size: 'small',
      component: <ItemCreation
        saveLabel='Create and add this item'
        onSave={this.selectItemAndCloseModal}
        item={{
          name: searchTerm
        }}
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
    this.props.change(`${fieldName}.amount`, item.amount || 0)
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
    let isEmptyRow = !((selectedOption.item_id && selectedOption.item_id !== 'NULL') || selectedOption.name)

    return (
      <tr class={`${isEmptyRow ? 'lineItem--empty' : ''} ${disabled ? 'lineItem--disabled' : ''}`}>
        <td>
          <span class='remove-row-action' onClick={() => onRemove(index)}>
            <i class='fa fa-times-circle text-danger'></i>
          </span>

          <div class='item-ac-container'>
            <InlineField
              formName='newInvoice'
              name={`${fieldName}.item_id`}
              component={TypeAhead}
              options={items}
              selected={selectedOption}
              optionLabelPath='name'
              placeholder='Select an item'
              onOptionChange={this.updateLineItemRow}
              onQuickAdd={this.quickCreateItem}
              disabled={disabled}
              normalizeValue={(value) => {
                let selected = findBy(items || [], 'id', value) || selectedOption
                if (selected) {
                  return selected.name
                }
                return value
              }}
            />
            <InlineField
              formName='newInvoice'
              name={`${fieldName}.description`}
              component={AutoResizeTextarea}
              rows={2}
              class='form-control input-xs'
              placeholder='Enter item description'
              disabled={disabled}
            />
          </div>
        </td>

        <td>
          <InlineField
            formName='newInvoice'
            name={`${fieldName}.amountInINR`}
            component='input'
            class='form-control text-right input-xs'
            type='number'
            rightAlign={true}
            disabled={true}
          />
        </td>

        <td>
          <InlineField
            formName='newInvoice'
            name={`${fieldName}.quantity`}
            component={InputField}
            class='form-control text-right input-xs'
            type='number'
            min={1}
            rightAlign={true}
            disabled={disabled || isEmptyRow}
            showInlineErrorText={false}
          />
        </td>

        <td class='text-right'>
          {this.calculateLineItemTotal()}
        </td>
      </tr>
    )
  }
}
