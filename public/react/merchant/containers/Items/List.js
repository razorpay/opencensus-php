import React, { Component } from 'react'
import { connect } from 'react-redux'
import { reduxForm } from 'redux-form'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'
import ShowWhen from 'merchant/components/ShowWhen'
import ItemsList from 'merchant/components/Items/ItemsList'
import ItemCreation from 'merchant/containers/Items/New'
import ListContainer from 'merchant/containers/ListContainer'
import * as ItemActions from 'merchant/modules/items'
import * as ModalActions from 'rzp/modules/modals'

@connect(
  (state) => state.items,
  { ...ItemActions, ...ModalActions }
)
@reduxForm({
  form: 'newItem',
})
export default class ItemsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchItems(params)
  }

  showItemModal = (item = null) => {
    this.props.openModal({
      size: 'small',
      component: <ItemCreation
        item={item}
        onSave={this.highlightRowAndClose}
        closeModal={this.props.closeModal}
      />
    })
  }

  highlightRowAndClose = (item) => {
    this.props.highlightItemRow(item)
    this.props.closeModal()
  }

  deleteItem = (item) => {
    this.context.confirm({
      message: 'Are you sure to delete the item?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () => this.props.deleteItem(item).then((response) => {
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
        <ShowWhen notMyRole='support'>
          <div class='btn-toolbar'>
            <button
              class='pull-right btn btn-primary btn-rounded'
              onClick={() => this.showItemModal()}
            >
              <i class='fa fa-plus'></i>
              <span>New Item</span>
            </button>
          </div>
        </ShowWhen>

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

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={items.length}
              onClick={this.fetchAll}
            />
          </div>
        </div>
      </div>
    )
  }
}
