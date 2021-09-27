import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import moment from 'moment';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchAggregate } from 'merchant/reducers/commission';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import Amount from 'common/ui/Amount';
import DataTable from 'common/ui/Table/DataTable';

import { without } from 'common/utils/rzp-utils';

import AddMerchant from '../../SubMerchant/AddMerchant';
import ListFilter from './ListFilter';

const volume = {
  title: 'Transaction Amount',
  value: (item) => <Amount value={item.transactionVolume} currency="INR" />,
};

const activeMerchants = {
  title: 'No. of Active Accounts',
  value: (item) => item.activeMerchants,
};

const transactions = {
  title: 'No. of Transactions',
  value: (item) => item.transactions,
};

@connect((state) => ({ ...state.commissionsAggregate }), {
  fetchAggregate,
  openModal,
  closeModal,
})
export default class CommissionsDailyList extends ListContainer {
  constructor(props) {
    super(props);

    this.dateColumn = {
      title: 'Date',
      value: (item) => (
        <Link to={`/partners/${props.dailyEntityRoute}/daily/${item.timestamp}`}>
          {moment(item.timestamp, 'X').format('ll')}
        </Link>
      ),
    };
  }

  onDatesChange = (from, to) => {
    this.search({ from, to });
  };

  fetchEntityList(params) {
    if (!params.from && !params.to) {
      const currDate = moment();
      params.to = Number(currDate.format('X'));
      params.from = Number(currDate.startOf('day').subtract(30, 'days').format('X'));
    }
    params.queryType = this.props.queryType;
    return this.props.fetchAggregate(without(params, ['skip', 'count']));
  }

  handleAddMerchant = () => {
    this.props.openModal({
      size: 'med-large',
      component: <AddMerchant closeModal={this.props.closeModal} />,
    });
  };

  renderLessThanRequiredMerchants = () => (
    <div class="empty-table-message">
      <h3>Unlock your earnings view</h3>
      <p class="m-t">
        Add more accounts (>{this.props.items.limit}) to unlock the details view of processed
        earnings
      </p>
      <p>
        <button class="btn btn-link" onClick={this.handleAddMerchant}>
          + Add New Account
        </button>
      </p>
    </div>
  );

  render() {
    return (
      <div class="content-wrapper CommissionList--Daily">
        <ListFilter onDatesChange={this.onDatesChange} />
        <DataTable
          columns={[
            this.dateColumn,
            this.props.amountColumn,
            volume,
            activeMerchants,
            transactions,
          ]}
          title="Data"
          EmptyComponent={this.props.items.limit && this.renderLessThanRequiredMerchants}
          {...this.props}
        />
      </div>
    );
  }
}
