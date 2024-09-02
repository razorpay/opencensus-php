import React from 'react';
import { Box, Link, SettingsIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import {
  FIXED_COLUMNS_TRANSACTIONS_V2,
  OPTIONAL_COLUMNS_TRANSACTIONS_V2,
  ERROR_MESSAGES,
} from 'merchant/views/Transactions/constants';
import {
  fetchMerchantColumnPreferences,
  fetchPaymentNotesKeys,
} from 'merchant/views/Transactions/model';
import { PaymentsEditColumnsModal } from 'merchant/views/Transactions/v1/Payments/components/PaymentsEditColumnsModal';
import PaymentsListFilter from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter';
import PaymentsTable from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable';
import { paymentStatusVariantMap } from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable/constants';
import { handleDetailsClick } from 'merchant/views/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { onPaginate, onSearch } from 'merchant/views/Transactions/v2/common/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

class PaymentsList extends ListContainer {
  state = {
    columnsList: [],
    selectedColumnsList: [],
    isEditColumnsModalOpen: false,
  };

  componentDidMount() {
    const {
      user: { isCustomTransactionTabView },
    } = this.props;
    if (isCustomTransactionTabView) {
      this.fetchColumns();
      this.fetchMerchantColumns();
    }
  }

  fetchColumns = async () => {
    try {
      const { data: columnsData, status_code } = await fetchPaymentNotesKeys();
      if (status_code === 200) this.setState({ columnsList: columnsData });
      else throw new Error();
    } catch (error) {
      showNotification({ type: 'error', message: ERROR_MESSAGES.FETCH_COLUMNS });
    }
  };

  fetchMerchantColumns = async () => {
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
      showNotification({
        type: 'error',
        message: ERROR_MESSAGES.FETCH_PREFERENCES,
      });
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
    const { count, skip, isEditColumnsModalOpen, columnsList, selectedColumnsList } = this.state;
    const {
      loading,
      history,
      location: { pathname },
      navigate,
      user: {
        isOmniChannelMerchant,
        pos_activation_status,
        isOmniEnabledMerchant,
        isCustomTransactionTabView,
      },
    } = this.props;
    const isOmniView = isOmniEnabledMerchant || (!!pos_activation_status && isOmniChannelMerchant);

    return (
      <>
        <PaymentsListFilter onSubmit={onSearch(history)} loading={loading} />
        {isCustomTransactionTabView && (
          <Box display="grid" margin="0 10px 10px 0">
            <Link
              variant="button"
              size="medium"
              justifySelf={{ l: 'flex-end', s: 'flex-start' }}
              display={{ l: 'block', xs: 'none' }}
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
              showNotification={showNotification}
            />
          </Box>
        )}
        <PaymentsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          isDisabled={({ status }) => !paymentStatusVariantMap[status]}
          selectedColumnsList={selectedColumnsList}
          shouldShowCustomTransactionTabView={isCustomTransactionTabView}
          isOmniView={isOmniView}
          onRowClick={({ id, rowData }) =>
            handleDetailsClick({
              navigate,
              itemId: id,
              baseUrl: TransactionsEntityRoute.PAYMENTS,
              initiatePage: TransactionsPagesMap[pathname],
              rowData,
            })
          }
          {...this.props}
        />
      </>
    );
  }
}

export default withRouter(
  connect(
    (state) => ({
      ...state.payments,
      user: state.session.user,
    }),
    { fetchAll, showNotification },
  )(PaymentsList),
);
