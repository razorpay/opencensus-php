import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchCommissions as fetchAll } from 'merchant/modules/collection';

import DataTable from 'rzp/ui/Table/DataTable';

import { commissionId } from 'rzp/ui/item/pair';

import ListFilter from './ListFilter';

@connect(state => ({ ...state.commisions }), { fetchAll })
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
          columns={[commissionId]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
