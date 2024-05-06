import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'shell/deprecated/withRouter';
import { withZustand } from 'shell/commonStore';
import { compose } from 'redux';

import { ListContainer } from '@dashboard/shared-ui/containers';
import { fetchPayments as fetchAll } from 'apps/self-serve/src/bootstrap/Store/reducers/paymentsReducer';
import PaymentsListFilter from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsListFilter';
import PaymentsTable from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable';
import { handleDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { onPaginate, onSearch } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { paymentStatusVariantMap } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable/constants';
import { Box, Link, SettingsIcon } from '@razorpay/blade/components';
import { fetchMerchantColumnPreferences, fetchPaymentNotesKeys } from './model';
import {
  ERROR_MESSAGES,
  FIXED_COLUMNS_TRANSACTIONS_V2,
  OPTIONAL_COLUMNS_TRANSACTIONS_V2,
} from './constants';
import { PaymentsEditColumnsModal } from './PaymentsEditColumnsModal';

class PaymentsList extends ListContainer {
  state = {
    columnsList: [],
    selectedColumnsList: [],
    isEditColumnsModalOpen: false,
  };

  componentDidMount() {
    const {
      store: {
        session: {
          user: { isCustomTransactionTabView },
        },
      },
    } = this.props;
    if (isCustomTransactionTabView) {
      this.fetchColumns();
      this.fetchMerchantColumns();
    }
  }

  fetchColumns = async () => {
    const {
      store: { showNotification },
    } = this.props;
    try {
      const { data: columnsData, status_code } = await fetchPaymentNotesKeys();
      if (status_code === 200) this.setState({ columnsList: columnsData });
      else throw new Error();
    } catch (error) {
      showNotification({ type: 'error', message: ERROR_MESSAGES.FETCH_COLUMNS });
    }
  };

  fetchMerchantColumns = async () => {
    const {
      store: { showNotification },
    } = this.props;
    try {
      const { data: { data: selectedColumnsData } = {}, status_code } =
        await fetchMerchantColumnPreferences();
      if (status_code === 200)
        this.setState({
          selectedColumnsList: [
            ...selectedColumnsData.payment_optional_keys_columns,
            ...selectedColumnsData.user_notes_key_columns,
          ],
        });
      else throw new Error();
    } catch (error) {
      showNotification({ type: 'error', message: ERROR_MESSAGES.FETCH_PREFERENCES });
    }
  };

  toggleEditColumnsModal = () => {
    this.setState((prevState) => ({
      isEditColumnsModalOpen: !prevState.isEditColumnsModalOpen,
    }));
  };

  updateColumnView = (selectedColumnsListData) => {
    this.setState({
      selectedColumnsList: [...selectedColumnsListData],
      isEditColumnsModalOpen: false,
    });
  };

  render() {
    const { count, skip, columnsList, selectedColumnsList, isEditColumnsModalOpen } = this.state;
    const {
      loading,
      history,
      location: { pathname },
      navigate,
      store,
    } = this.props;

    const {
      session: {
        user: {
          isOmniChannelMerchant,
          pos_activation_status,
          isOmniEnabledMerchant,
          isCustomTransactionTabView,
        },
      },
    } = store;

    const isOmniView = isOmniEnabledMerchant || (!!pos_activation_status && isOmniChannelMerchant);

    return (
      <>
        <PaymentsListFilter onSubmit={onSearch(history)} loading={loading} />
        {isCustomTransactionTabView ? (
          <Box display="grid" margin="0 10px 10px 0">
            <Link
              variant="button"
              size="medium"
              justifySelf={{ l: 'flex-end', s: 'flex-start' }}
              display={{ xs: 'none', l: 'block' }}
              icon={SettingsIcon}
              onClick={this.toggleEditColumnsModal}
            >
              Edit Columns
            </Link>
            <PaymentsEditColumnsModal
              isOpen={isEditColumnsModalOpen}
              onClose={this.toggleEditColumnsModal}
              onSubmit={this.updateColumnView}
              columnsList={columnsList}
              selectedColumnsList={selectedColumnsList}
              fixedColumns={FIXED_COLUMNS_TRANSACTIONS_V2}
              optionalColumns={OPTIONAL_COLUMNS_TRANSACTIONS_V2}
            />
          </Box>
        ) : null}
        <PaymentsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          isDisabled={({ status }) => !paymentStatusVariantMap[status]}
          selectedColumnsList={selectedColumnsList}
          shouldShowCustomTransactionTabView={isCustomTransactionTabView}
          isOmniView={isOmniView}
          onRowClick={(id) =>
            handleDetailsClick({
              navigate,
              itemId: id,
              baseUrl: TransactionsEntityRoute.PAYMENTS,
              initiatePage: TransactionsPagesMap[pathname],
              prevPath: pathname,
            })
          }
          {...this.props}
        />
      </>
    );
  }
}

export default compose(
  withRouter,
  connect(
    (state) => ({
      ...state.payments,
    }),
    { fetchAll },
  ),
  (component) => withZustand(component, ['session', 'showNotification']),
)(PaymentsList);
