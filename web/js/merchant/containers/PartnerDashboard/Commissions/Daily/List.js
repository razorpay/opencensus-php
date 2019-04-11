import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchAggregate as fetchAll } from 'merchant/modules/commission';

import Amount from 'rzp/ui/Amount';

import DataTable from 'rzp/ui/Table/DataTable';

const date = {
  title: 'Date',
  value: item => (
    <Link to={`/partners/earnings/daily/${item.timestamp}`}>
      {moment(item.timestamp, 'X').format('ll')}
    </Link>
  ),
};

const earnings = {
  title: 'Total Earnings',
  value: item => <Amount value={item.earnings} currency={'INR'} />,
};

const volume = {
  title: 'Transaction Volume',
  value: item => <Amount value={item.transactionVolume} currency={'INR'} />,
};

const activeMerchants = {
  title: 'No. of Active Merchants',
  value: item => item.activeMerchants,
};

const transactions = {
  title: 'No. of Transactions',
  value: item => item.transactions,
};

@connect(state => ({ ...state.commissionsAggregate }), { fetchAll })
export default class CommissionsDailyList extends ListContainer {
  render() {
    return (
      <div className="content-wrapper">
        <DataTable
          columns={[date, earnings, volume, activeMerchants, transactions]}
          {...this.props}
        />
      </div>
    );
  }
}
