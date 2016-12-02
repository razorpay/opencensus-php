import React, { Component } from 'react'
import { connect } from 'react-redux'
import { reduxForm } from 'redux-form'
import Modal from 'rzp/ui/Modal'
import Header from 'rzp/ui/Header'
import { fetchItems } from 'merchant/modules/items'
import ItemsList from 'merchant/components/Items/ItemsList'
import ItemCreation from 'merchant/containers/Items/New'
import ModalContainer from 'merchant/containers/ModalContainer'

@connect(
  (state) => state.items.toJS(),
  { fetchItems }
)
@reduxForm({
  form: 'newItem',
})
export default class ItemsListContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.state.itemToEdit = null
    this.showItemModal = ::this.showItemModal
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

  deleteItem() {

  }

  render() {
    let { loading, items } = this.props

    return (
      <div>
        <Header title='Items'>
          <button
            class='pull-right btn btn-primary btn-rounded'
            onClick={() => this.showItemModal()}
          >
            <i class='fa fa-plus'></i>
            <span>New Item</span>
          </button>
        </Header>

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <ItemsList
              items={items}
              isLoading={loading}
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
            onSave={this.closeModal}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
