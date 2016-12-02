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
    this.props.change(`${fieldName}.id`, item.id)
    this.props.change(`${fieldName}.amountInINR`, (item.amount/100).toFixed(2))
    this.closeModal()
  }

  calculateLineItemTotal() {
    let fieldItem = this.props.invoice_line_items[this.props.index]
    return (Number(fieldItem.amountInINR) * Number(fieldItem.quantity)).toFixed(2)
  }

  render() {
    let { fieldName, fieldItem, index, items, onRemove } = this.props
    let selectedItemId = fieldItem.item ? fieldItem.item.id :
                          fieldItem.item_id ? fieldItem.item_id : null

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

          <Field
            name={`${fieldName}.item_id`}
            component={TypeAhead}
            options={items}
            selected={selectedItemId}
            optionLabelPath='name'
            placeholder='Select an item'
            onChange={(selectedItem) => {
              this.props.change(`${fieldName}.amountInINR`, selectedItem.amountInINR || '0.00')
              setTimeout(() => {
                this.calculateLineItemTotal()
              }, 0)
            }}
            onQuickAdd={this.quickCreateItem}
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
            name={`${fieldName}.amountInINR`}
            component='input'
            class='form-control text-right'
            type='number'
            disabled={true}
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
