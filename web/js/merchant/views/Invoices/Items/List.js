/* eslint-disable */
import React from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';

import HeaderAction from 'common/ui/HeaderAction';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import ItemsList from 'merchant/views/Invoices/Items/components/ItemsList';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import ItemCreation from 'merchant/views/Invoices/Items/New';
import ListContainer from 'merchant/containers/ListContainer';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as ItemActions from 'merchant/reducers/items';
import { fetchInvoices } from 'merchant/reducers/invoices/list';
import { luminateRow } from 'merchant/reducers/app';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import { RZPFeatures } from 'merchant/helpers/data';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { withRouter } from 'common/deprecated/withRouter';

@connect(
  (state) => ({
    ...state.items,
    user: state.session.user,
    mode: state.session.mode,
    invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.INVOICE),
  }),
  {
    ...ItemActions,
    ...ModalActions,
    luminateRow,
    handleProductQuickGuide,
    fetchInvoices,
  },
)
@reduxForm({
  form: 'newItem',
})
class ItemsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Invoices',
      eventAction: 'Go To - Items',
    });

    if (this.props.isInvoiceView) {
      this.props.fetchInvoices({ count: 25 });
    }
  }

  componentWillUnmount() {
    const { invoicesProductOnBoarding, isInvoiceView } = this.props;

    if (isInvoiceView && invoicesProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...invoicesProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: this.state.isInvoiceView,
        isTour: this.state.isInvoiceView,
      });
    }
  }

  fetchEntityList(params) {
    return this.props.fetchItems({
      ...params,
      'expand[]': 'tax',
      type: 'invoice',
    });
  }

  itemFormOnMount = (item) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Invoices',
      eventAction: `Open Form - ${item ? 'Edit' : 'New'} Item`,
    });
  };

  itemFormOnUnmount = (item) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Invoices',
      eventAction: `Close Form - ${item ? 'Edit' : 'New'} Item`,
    });
  };

  showItemModal = (item = null) => {
    /**
     * Taxes are to be shown when the merchant has GSTIN entered.
     * Size of modal changes if taxes are to be shown.
     */
    const { user } = this.props;
    const { merchant } = user;
    const gstin = user.gstin || user.p_gstin;
    const showTaxes = Boolean(gstin);

    selfServeTrackInitiate({
      selfServeAction: 'New Item Created',
      page: 'Items',
      screen: 'Invoice',
    });

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
          currency={(item && item.currency) || merchant.currency}
        />
      ),
    });
  };

  highlightRowAndClose = (item, prevItem) => {
    this.props.luminateRow(item.id);
    this.props.closeModal();
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Invoices',
      eventAction: `Submit Form - ${prevItem ? 'Edit' : 'New'} Item`,
      eventLabel: getKeysSeparatedByPipe(item),
    });
    selfServeTrackSuccess({
      selfServeAction: 'New Item Created',
      page: 'Items',
      screen: 'Invoice',
    });
  };

  deleteItem = (item) => {
    this.context.confirm({
      message: 'Are you sure to delete the item?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        this.props
          .deleteItem(item)
          .then(() => {
            this.setState({
              status: {
                type: 'success',
                message: 'Item deleted successfully',
              },
            });
          })
          .catch((err) => {
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
    const { loading, items, user, mode, isInvoiceView } = this.props;
    const { status } = this.state;

    return (
      <div className="content-wrapper">
        <HeaderAction>
          <div className="btn-toolbar">
            {isInvoiceView && (
              <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
                <TakeATourButton feature={RZPFeatures.INVOICE} />
              </ShowWhen>
            )}

            <ShowWhen
              additionalCondition={(user) =>
                (mode !== 'live' || !user.isRejected) && user.isAllowedEdit('items')
              }
            >
              <button className="pull-right btn btn-primary" onClick={() => this.showItemModal()}>
                <i className="i i-plus" />
                <span>New Item</span>
              </button>
            </ShowWhen>
          </div>
        </HeaderAction>

        <Alert type={status.type} message={status.message} />

        <ItemsList
          items={items}
          isLoading={loading}
          onEdit={this.showItemModal}
          onDelete={this.deleteItem}
          userActionAllowed={user.isAllowedEdit('items')}
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

export default withRouter(ItemsListContainer);
