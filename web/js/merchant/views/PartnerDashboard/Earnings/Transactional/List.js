import { connect } from 'react-redux';

import Amount from 'common/ui/Amount';
import DataTable from 'common/ui/Table/DataTable';
import { earningId, createdAtShort } from 'common/ui/item/pair';
import { capitalize } from 'common/utils/rzp-utils';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchEarnings as fetchAll } from 'merchant/reducers/collection';
import ListFilter from 'merchant/views/PartnerDashboard/Commissions/Transactional/ListFilter';

const sourceType = {
  title: 'Source',
  value: (item) => capitalize(item.source_type),
};

const totalCommission = {
  title: 'Total Earning',
  value: (item) => (
    <Amount
      currency={item.currency}
      value={item.source_type === 'payment' ? item.credit : item.debit}
      testId={`amount-${item.id}`}
    />
  ),
};

const merchantName = {
  title: 'Account Name',
  value: (item) => (item.merchant || {}).name,
};

@connect((state) => ({ ...state.commisions }), { fetchAll })
export default class CommissionList extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <ListFilter
          form="CommissionsListFilter"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Commissions"
          columns={[earningId, totalCommission, merchantName, sourceType, createdAtShort]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
