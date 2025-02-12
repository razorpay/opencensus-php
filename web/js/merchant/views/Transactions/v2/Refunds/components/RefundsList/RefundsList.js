import React from 'react';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { isOmniChannelMerchant } from 'merchant/utils/omniUtils';
import RefundsListFilter from 'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter';
import RefundsTable from 'merchant/views/Transactions/v2/Refunds/components/RefundsTable';
import { handleDetailsClick } from 'merchant/views/Transactions/v2/common/components/Details/Details';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { onPaginate, onSearch } from 'merchant/views/Transactions/v2/common/utils';

// TODO: @Shivam KS @Joel refactor it correctly
const RefundsListWrapper = (props) => {
  const navigate = useNavigate();
  return <RefundsList navigate={navigate} {...props} />;
};

class RefundsList extends ListContainer {
  componentDidMount() {
    const { fetchProviders, user } = this.props;
    if (user.isOptimizerView()) {
      fetchProviders();
    }
  }

  render() {
    const {
      loading,
      history,
      navigate,
      location: { pathname },
      user,
      terminalProviders,
    } = this.props;
    const { count, skip } = this.state;
    const shouldDisplayOptimizerColumn = this.props.user.isOptimizerView();
    const isOmniView = isOmniChannelMerchant(user);

    return (
      <>
        <RefundsListFilter
          count={count}
          onSubmit={onSearch(history)}
          loading={loading}
          terminalProviders={terminalProviders}
        />
        <RefundsTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          isOmniView={isOmniView}
          shouldDisplayOptimizerColumn={shouldDisplayOptimizerColumn}
          onRowClick={({ id }) =>
            handleDetailsClick({
              navigate,
              itemId: id,
              baseUrl: TransactionsEntityRoute.REFUNDS,
              initiatePage: TransactionsPagesMap[pathname],
            })
          }
          {...this.props}
        />
      </>
    );
  }
}

function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      fetchAll,
      fetchProviders: fetchTerminalProviders,
    },
    dispatch,
  );
}

export default withSplitzService(
  withRouter(
    connect(
      (state) => ({
        ...state.refunds,
        user: state.session.user,
        terminalProviders: state.navigator.terminalProviders,
      }),
      mapDispatchToProps,
    )(RefundsListWrapper),
  ),
);
