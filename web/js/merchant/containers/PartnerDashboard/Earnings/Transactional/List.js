import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchEarnings as fetchAll } from 'merchant/modules/collection';
import { fetchCommissionBalances } from 'merchant/modules/commission';

import ShowWhen from 'merchant/components/ShowWhen';

import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import Amount from 'rzp/ui/Amount';

import { earningId, createdAtShort } from 'rzp/ui/item/pair';
import { capitalize, isPresent } from 'rzp/utils/rzp-utils';

import ListFilter from '../../Commissions/Transactional/ListFilter';

const sourceType = {
  title: 'Source',
  value: item => capitalize(item.source_type),
};

const totalCommission = {
  title: 'Total Earning',
  value: item => (
    <Amount
      currency={item.currency}
      value={item.source_type === 'payment' ? item.credit : item.debit}
    />
  ),
};

const merchantName = {
  title: 'Account Name',
  value: item => (item.merchant || {}).name,
};

@connect(state => ({ ...state.commisions }), { fetchAll })
export default class CommissionList extends ListContainer {
  state = {
    commissionBalance: null,
  };

  componentDidMount() {
    this.getCommissionBalance();
  }

  getCommissionBalance = () => {
    fetchCommissionBalances().then(res => {
      const data = res.data;
      if (data.items.length > 0) {
        const commissionItem = data.items.find(
          item => item.type === 'commission'
        );
        isPresent(commissionItem) &&
          this.setState({
            commissionBalance: commissionItem.balance,
          });
      }
    });
  };

  render() {
    const { commissionBalance } = this.state;
    return (
      <div class="content-wrapper">
        <ShowWhen
          additionalCondition={user =>
            !user.isPartner('reseller') &&
            user.isOrgAllowedFunctionality('current_balance') &&
            (commissionBalance == 0 || commissionBalance)
          }
        >
          <HeaderAction>
            <span class="balance-amount">
              Commission Balance:{' '}
              <Amount value={commissionBalance} currency="INR" />
            </span>
          </HeaderAction>
        </ShowWhen>

        <ListFilter
          form="CommissionsListFilter"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Commissions"
          columns={[
            earningId,
            totalCommission,
            merchantName,
            sourceType,
            createdAtShort,
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
