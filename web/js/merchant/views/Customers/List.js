import React, { Suspense } from 'react';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import Alert from 'common/ui/Forms/Alert';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import Loader from 'common/ui/Loader';
import Pager from 'common/ui/Pager';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ListContainer from 'merchant/containers/ListContainer';
import { luminateRow } from 'merchant/reducers/app';
import * as CustomerActions from 'merchant/reducers/customers';
import lazy from 'merchant/routes/LazyLoader';
import CustomersList from 'merchant/views/Customers/components/CustomersList';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';

const CustomerCreation = lazy(() =>
  import(/* webpackChunkName: "CustomersNew" */ 'merchant/views/Customers/New'),
);

@connect((state) => ({ ...state.customers, mode: state.session.mode, user: state.session.user }), {
  ...CustomerActions,
  ...ModalActions,
  ...NotificationActions,
  luminateRow,
})
class CustomersListContainer extends ListContainer {
  fetchEntityList(params) {
    selfServeTrackInitiate({
      selfServeAction: 'Customer Details Fetched',
      page: 'Customers',
      screen: 'Customers',
    });
    const fetchCustomerPromise = this.props.fetchCustomers(params);
    fetchCustomerPromise.then(() => {
      selfServeTrackSuccess({
        selfServeAction: 'Customer Details Fetched',
        page: 'Customers',
        screen: 'Customers',
      });
    });
    return fetchCustomerPromise;
  }

  showCustomerModal = (customer = null) => {
    selfServeTrackInitiate({
      selfServeAction: 'New Customer Created',
      page: 'Customers',
      screen: 'Customers',
    });
    this.props.openModal({
      size: 'small',
      component: (
        <Suspense fallback={<Loader />}>
          <CustomerCreation
            customer={customer}
            onSave={this.highlightRowAndClose}
            closeModal={this.props.closeModal}
            askAddress={false}
          />
        </Suspense>
      ),
    });
  };

  highlightRowAndClose = (customer) => {
    this.props.luminateRow(customer.id);
    this.props.closeModal();
    selfServeTrackSuccess({
      selfServeAction: 'New Customer Created',
      page: 'Customers',
      screen: 'Customers',
    });
  };

  deleteCustomer = (customer) => {
    this.context.confirm({
      message: 'Are you sure to delete the customer?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        this.props
          .deleteCustomer(customer)
          .then(() => {
            this.setState({
              status: {
                type: 'success',
                message: 'Customer deleted successfully',
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
    const { loading, items, mode, user } = this.props;
    const { status } = this.state;

    return (
      <div class="content-wrapper">
        <TestModeBanner />

        <HeaderAction>
          <ShowWhen
            additionalCondition={(user) =>
              (mode !== 'live' || !user.isRejected) && user.isAllowedEdit('customers')
            }
          >
            <div class="btn-toolbar">
              <button class="pull-right btn btn-primary" onClick={() => this.showCustomerModal()}>
                <i class="i i-plus" />
                <span>New Customer</span>
              </button>
            </div>
          </ShowWhen>
        </HeaderAction>

        <Alert type={status.type} message={status.message} />

        <CustomersList
          customers={items}
          isLoading={loading}
          userActionAllowed={user.isAllowedEdit('customers')}
          onEdit={this.showCustomerModal}
          onDelete={this.deleteCustomer}
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

export default withRouter(CustomersListContainer);
