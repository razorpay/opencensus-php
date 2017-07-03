import React, { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import ItemsList from 'merchant/components/Items/ItemsList';
import ItemCreation from 'merchant/containers/Items/New';
import ListContainer from 'merchant/containers/ListContainer';
import * as ModalActions from 'rzp/modules/modals';
import * as ItemActions from 'merchant/modules/items';
import { luminateRow } from 'merchant/modules/app';

@connect(state => state.items, { ...ItemActions, ...ModalActions, luminateRow })
@reduxForm({
  form: 'newItem',
})
export default class ItemsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchItems(params);
  }

  showItemModal = (item = null) => {
    this.props.openModal({
      size: 'small',
      component: (
        <ItemCreation
          item={item}
          onSave={this.highlightRowAndClose}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  highlightRowAndClose = item => {
    this.props.luminateRow(item.id);
    this.props.closeModal();
  };

  deleteItem = item => {
    this.context.confirm({
      message: 'Are you sure to delete the item?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        this.props
          .deleteItem(item)
          .then(response => {
            this.setState({
              status: {
                type: 'success',
                message: 'Item deleted successfully',
              },
            });
          })
          .catch(err => {
            this.setState({
              status: {
                type: 'error',
                message: err.errors,
              },
            });
          }),
    });
  };

  render() {
    let { loading, items } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#invoicing-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 0"
        >
          <div />{/* required by react-tether */}

          <ShowWhen notMyRole="support">
            <div class="btn-toolbar">
              <button
                class="pull-right btn btn-primary"
                onClick={() => this.showItemModal()}
              >
                <i class="icon icon-plus" />
                <span>New Item</span>
              </button>
            </div>
          </ShowWhen>
        </TetherComponent>

        <Alert type={status.type} message={status.message} />

        <ItemsList
          items={items}
          isLoading={loading}
          onEdit={this.showItemModal}
          onDelete={this.deleteItem}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={items.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
