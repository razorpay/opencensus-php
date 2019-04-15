import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchAggregate } from 'merchant/modules/commission';

import Amount from 'rzp/ui/Amount';
import DataTable from 'rzp/ui/Table/DataTable';

import { without } from 'rzp/utils/rzp-utils';

import ListFilter from './ListFilter';

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

@connect(state => ({ ...state.commissionsAggregate }), { fetchAggregate })
export default class CommissionsDailyList extends ListContainer {
  onDatesChange = (from, to) => {
    this.search({ from, to });
  };

  fetchEntityList(params) {
    if (!params.from && !params.to) {
      const currDate = moment();
      (params.to = Number(currDate.format('X'))),
        (params.from = Number(
          currDate
            .startOf('day')
            .subtract(7, 'days')
            .format('X')
        ));
    }

    return this.props.fetchAggregate(without(params, ['skip', 'count']));
  }

  render() {
    return (
      <div className="content-wrapper CommissionList--Daily">
        <ListFilter onDatesChange={this.onDatesChange} />
        <DataTable
          columns={[date, earnings, volume, activeMerchants, transactions]}
          {...this.props}
          title="Data"
        />
      </div>
    );
  }
}
