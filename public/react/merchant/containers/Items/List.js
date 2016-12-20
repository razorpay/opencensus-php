import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { reduxForm } from 'redux-form'
import Modal from 'rzp/ui/Modal'
import Alert from 'rzp/ui/Forms/Alert'
import ItemsList from 'merchant/components/Items/ItemsList'
import ItemCreation from 'merchant/containers/Items/New'
import ModalContainer from 'merchant/containers/ModalContainer'
import * as ItemActions from 'merchant/modules/items'

@connect(
  (state) => state.items,
  ItemActions
)
@reduxForm({
  form: 'newItem',
})
export default class ItemsListContainer extends ModalContainer {
  static contextTypes = {
    confirm: PropTypes.func
  }

  constructor() {
    super(...arguments)
    this.state.status = {}

    this.showItemModal = ::this.showItemModal
    this.highlightRowAndClose = ::this.highlightRowAndClose
    this.deleteItem = ::this.deleteItem
  }

  componentWillMount() {
    this.props.fetchItems()
  }

  showItemModal(item = null) {
    this.setState({
      itemToEdit: item
    })
    this.openModal()
  }

  highlightRowAndClose(item) {
    this.props.highlightItemRow(item)
    this.closeModal()
  }

  deleteItem(item) {
    this.context.confirm('Are you sure to delete the item?').then(() => {
      this.props.deleteItem(item).then((response) => {
        this.setState({
          status: {
            type: 'success',
            message: 'Item deleted successfully'
          }
        })
      }).catch((err) => {
        this.setState({
          status: {
            type: 'error',
            message: err.errors
          }
        })
      })
    })
  }

  render() {
    let { loading, items, highlightRowId } = this.props
    let status = this.state.status

    return (
      <div class='react-root'>
        <div class='btn-toolbar'>
          <button
            class='pull-right btn btn-primary btn-rounded'
            onClick={() => this.showItemModal()}
          >
            <i class='fa fa-plus'></i>
            <span>New Item</span>
          </button>
        </div>

        <div class='content-wrapper'>
          <Alert
            type={status.type}
            message={status.message}
          />

          <div class='panel panel-default'>
            <ItemsList
              items={items}
              isLoading={loading}
              highlightRow={(item) => item.id === highlightRowId}
              onEdit={this.showItemModal}
              onDelete={this.deleteItem}
            />
          </div>
        </div>

        <Modal
          isOpen={this.state.isModalOpen}
          onRequestClose={this.closeModal}
          closeTimeoutMS={300}
        >
          <ItemCreation
            item={this.state.itemToEdit}
            onSave={this.highlightRowAndClose}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
