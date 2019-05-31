import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchSubventions as fetchAll } from 'merchant/modules/collection';

import DataTable from 'rzp/ui/Table/DataTable';
import Amount from 'rzp/ui/Amount';

import { commissionId, createdAtShort } from 'rzp/ui/item/pair';

const merchantName = {
  title: 'Affiliated Name',
  value: item => (item.merchant || {}).name,
};

const subventionFee = {
  title: 'Subvention Fee',
  value: item => <Amount currency={item.currency} value={item.fee} />,
};

@connect(state => ({ ...state.commisions }), { fetchAll })
export default class SubventionList extends ListContainer {
  render() {
    return (
      <div className="content-wrapper">
        <DataTable
          title="Subventions"
          columns={[commissionId, subventionFee, merchantName, createdAtShort]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
