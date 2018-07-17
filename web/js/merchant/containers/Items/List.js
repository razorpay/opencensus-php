import React, { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import ItemsList from 'merchant/components/Items/ItemsList';
import ItemCreation from 'merchant/containers/Items/New';
import ListContainer from 'merchant/containers/ListContainer';
import * as ModalActions from 'rzp/modules/modals';
import * as ItemActions from 'merchant/modules/items';
import { luminateRow } from 'merchant/modules/app';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';
import { stringifyQueryParams } from '../../../rzp/utils/rzp-utils';

@connect(
  state => ({
    ...state.items,
    session: state.session,
  }),
  { ...ItemActions, ...ModalActions, luminateRow }
)
@reduxForm({
  form: 'newItem',
})
export default class ItemsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Invoices',
      eventAction: 'Go To - Items',
    });
  }

  fetchEntityList(params) {
    return this.props.fetchItems({
      ...params,
      'expand[]': 'tax',
      type: 'invoice',
    });
  }

  itemFormOnMount = item => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Invoices',
      eventAction: `Open Form - ${item ? 'Edit' : 'New'} Item`,
    });
  };

  itemFormOnUnmount = item => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Invoices',
      eventAction: `Close Form - ${item ? 'Edit' : 'New'} Item`,
    });
  };

  showItemModal = (item = null) => {
    /**
     * Taxes are to be shown when the merchant has GSTIN entered.
     * Size of modal changes if taxes are to be shown.
     */
    let user = this.props.session.user;
    let gstin = user.gstin || user.p_gstin;
    let showTaxes = Boolean(gstin);

    this.props.openModal({
      size: showTaxes ? 'regular' : 'small',
      component: (
        <ItemCreation
          item={item}
          onSave={this.highlightRowAndClose}
          closeModal={this.props.closeModal}
          onMount={this.itemFormOnMount}
          onUnmount={this.itemFormOnUnmount}
          showTaxes={showTaxes}
        />
      ),
    });
  };

  highlightRowAndClose = (item, prevItem) => {
    this.props.luminateRow(item.id);
    this.props.closeModal();
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Invoices',
      eventAction: `Submit Form - ${prevItem ? 'Edit' : 'New'} Item`,
      eventLabel: getKeysSeparatedByPipe(item),
    });
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
        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar">
              <button
                class="pull-right btn btn-primary"
                onClick={() => this.showItemModal()}
              >
                <i class="i i-plus" />
                <span>New Item</span>
              </button>
            </div>
          </ShowWhen>
        </HeaderAction>

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
