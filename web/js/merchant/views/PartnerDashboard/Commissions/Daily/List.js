import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import moment from 'moment';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchAggregate } from 'merchant/reducers/commission';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import Amount from 'common/ui/Amount';
import DataTable from 'common/ui/Table/DataTable';

import { without } from 'common/utils/rzp-utils';

import AddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant';
import ListFilter from 'merchant/views/PartnerDashboard/Commissions/Daily/ListFilter';
import EmptyDailyList from 'merchant/views/PartnerDashboard/Commissions/components/EmptyDailyList';
import InviteMerchantModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal';
import withPartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hocs/withPartnerDashboardExperiments';

const activeMerchants = {
  title: 'No. of Active Accounts',
  value: (item) => item.activeMerchants,
};

const transactions = {
  title: 'No. of Transactions',
  value: (item) => item.transactions,
};

const getVolmeListItem = (currency) => {
  return {
    title: 'Transaction Amount',
    value: (item) => (
      <Amount
        value={item.transactionVolume}
        currency={currency}
        testId={`amount-${item.timestamp}`}
      />
    ),
  };
};

@connect(
  (state) => ({
    ...state.commissionsAggregate,
    user: state?.session?.user,
    org: state?.session?.org,
  }),
  {
    fetchAggregate,
    openModal,
    closeModal,
  },
)
class CommissionsDailyList extends ListContainer {
  state = {
    isInviteMerchantModalOpen: false,
  };
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
    const { experiments } = this.props;
    if (experiments.isEasierAccessToSubmerchantKycEnabled) {
      this.setState({ isInviteMerchantModalOpen: true });
    } else {
      this.props.openModal({
        size: 'med-large',
        component: (
          <AddMerchant
            closeModal={this.props.closeModal}
            source="daily-earning"
            org={this.props.org}
          />
        ),
      });
    }
  };

  renderLessThanRequiredMerchants = () => {
    const { items, user } = this.props;
    const isAddMerchantView = user?.isPartner('reseller', 'aggregator') || false;
    return (
      <EmptyDailyList
        handleAddMerchant={this.handleAddMerchant}
        items={items}
        isPartnershipFUX={user?.isPartnershipFUX}
        isAddMerchantView={isAddMerchantView}
      />
    );
  };

  render() {
    const { user, experiments } = this.props;
    const currency = user.merchant.currency;
    const volume = getVolmeListItem(currency);

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
          EmptyComponent={this.renderLessThanRequiredMerchants}
          {...this.props}
        />
        {experiments.isEasierAccessToSubmerchantKycEnabled ? (
          <InviteMerchantModal
            isOpen={this.state.isInviteMerchantModalOpen}
            onDismiss={() => this.setState({ isInviteMerchantModalOpen: false })}
          />
        ) : null}
      </div>
    );
  }
}
export default withPartnerDashboardExperiments(withRouter(CommissionsDailyList));
