import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import { fetchSubventions as fetchAll } from 'merchant/reducers/collection';

import DataTable from 'common/ui/Table/DataTable';
import Amount from 'common/ui/Amount';

import { subventionId, createdAtShort } from 'common/ui/item/pair';

import ListFilter from 'merchant/views/PartnerDashboard/Commissions/Transactional/ListFilter';

const merchantName = {
  title: 'Affiliated Name',
  value: (item) => (item.merchant || {}).name,
};

const subventionFee = {
  title: 'Subvention Fee',
  value: (item) => <Amount currency={item.currency} value={item.fee} />,
};

@connect((state) => ({ ...state.commisions }), { fetchAll })
class SubventionList extends ListContainer {
  render() {
    return (
      <div className="content-wrapper">
        <ListFilter
          form="CommissionsListFtiler"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Subventions"
          columns={[subventionId, subventionFee, merchantName, createdAtShort]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}

export default withRouter(SubventionList);
