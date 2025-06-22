import React from 'react';
import { Box, Link, SettingsIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPayments, fetchMarketplacePayments } from 'merchant/reducers/collection';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
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
import { getPaymentStatusVariantMap } from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable/constants';
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
      user: { isCustomTransactionTabView, isOptimizerEnabled },
      fetchProviders,
    } = this.props;
    if (isOptimizerEnabled) {
      fetchProviders();
    }

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
          ].filter((column) => column !== null),
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
        isJnKOmniEnabled,
        isPosOrderIDEnabled,
      },
      orgName,
      terminalProviders,
      isMarketplacePayments,
    } = this.props;

    const shouldDisplayOptimizerColumn = this.props.user.isOptimizerEnabled;
    const isOmniView = isOmniEnabledMerchant || (!!pos_activation_status && isOmniChannelMerchant);

    return (
      <>
        <PaymentsListFilter
          onSubmit={onSearch(history)}
          loading={loading}
          terminalProviders={terminalProviders}
        />
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
          isDisabled={({ status }) => !getPaymentStatusVariantMap(orgName)[status]}
          selectedColumnsList={selectedColumnsList}
          shouldShowCustomTransactionTabView={isCustomTransactionTabView}
          shouldDisplayOptimizerColumn={shouldDisplayOptimizerColumn}
          isOmniView={isOmniView}
          isJnKOmniEnabled={isJnKOmniEnabled}
          isPosOrderIDEnabled={isPosOrderIDEnabled}
          isMarketplacePayments={isMarketplacePayments}
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

function mapDispatchToProps(dispatch, props) {
  return bindActionCreators(
    {
      fetchAll: props?.isMarketplacePayments ? fetchMarketplacePayments : fetchPayments,
      showNotification,
      fetchProviders: fetchTerminalProviders,
    },
    dispatch,
  );
}

export default withSplitzService(
  withRouter(
    connect((state, props) => {
      return {
        ...(props?.isMarketplacePayments ? state.mpPayments : state.payments),
        user: state.session.user,
        orgName: state.session.org?.business_name,
        terminalProviders: state.navigator.terminalProviders,
      };
    }, mapDispatchToProps)(PaymentsList),
  ),
);
