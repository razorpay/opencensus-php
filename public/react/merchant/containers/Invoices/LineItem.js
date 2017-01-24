import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import TypeAhead from 'rzp/ui/Select/TypeAhead'
import Modal from 'rzp/ui/Modal'
import ModalContainer from 'merchant/containers/ModalContainer'
import ItemCreation from 'merchant/containers/Items/New'

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
export default class InvoiceLineItem extends ModalContainer {
  constructor() {
    super(...arguments)
    this.quickCreateItem = ::this.quickCreateItem
    this.selectItemAndCloseModal = ::this.selectItemAndCloseModal
  }

  quickCreateItem() {
    this.openModal()
  }

  selectItemAndCloseModal(item) {
    let fieldName = this.props.fieldName
    this.props.change(`${fieldName}.item_id`, item.id)
    this.props.change(`${fieldName}.amountInINR`, (item.amount/100).toFixed(2))
    this.props.change(`${fieldName}.description`, item.description)
    this.closeModal()
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
          <Modal
            isOpen={this.state.isModalOpen}
            onRequestClose={this.closeModal}
            closeTimeoutMS={300}
          >
            <ItemCreation
              onSave={this.selectItemAndCloseModal}
              closeModal={this.closeModal}
            />
          </Modal>

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
              onChange={(selectedItem) => {
                this.props.change(`${fieldName}.amountInINR`, selectedItem.amountInINR || '0.00')
                this.props.change(`${fieldName}.item_id`, selectedItem.id || '')
                this.props.change(`${fieldName}.description`, selectedItem.description || '')

                setTimeout(() => {
                  this.calculateLineItemTotal()
                }, 0)
              }}
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
