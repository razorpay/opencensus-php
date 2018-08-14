import { Fragment } from 'react';
import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';

import { openModal, closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { fetchSubmerchants as fetchAll } from 'merchant/modules/collection';
import { switchMerchant } from 'merchant/modules/session';

import DataTable from 'rzp/ui/Table/DataTable';
import StatsCard from 'rzp/ui/StatsCard';
import HeaderAction from 'rzp/ui/HeaderAction';

import ShowWhen from 'merchant/components/ShowWhen';
import { getTime } from 'rzp/ui/item';
import { ActivationStatusLabel } from 'merchant/components/StatusLabel';
import {
  submerchant as name,
  submerchantId as id,
  email as emailColumn,
} from 'rzp/ui/item/pair';
import { humanReadableIndianCurrency } from 'rzp/utils/numerals';

import AddMerchant from './AddMerchant';
import ListFilter from './ListFilter';

const email = {
  title: 'Registered Email',
  value: emailColumn.value,
};

const addedOn = {
  title: 'Added On',
  value: getTime('created_at', 'll'),
};

const activationStatus = {
  title: (
    <Fragment>
      Activation Status <i class="i-info-circle" />
    </Fragment>
  ),
  value: submerchant =>
    submerchant.details && submerchant.details.activation_status ? (
      <ActivationStatusLabel status={submerchant.details.activation_status} />
    ) : (
      <span class="status-label label label-warning">Not Submitted</span>
    ),
};

const switchMerchantActionBtn = handleSwitchMerchant => ({
  title: 'Switch Merchant',
  value: item =>
    item.dashboard_access ? (
      <button
        class="btn btn-default btn-xs"
        onClick={handleSwitchMerchant(item.id.replace('acc_', ''))}
      >
        Switch
      </button>
    ) : (
      'No Access'
    ),
});

const switchMerchantAccessMap = {
  fully_managed: true,
  aggregator: true,
  reseller: false,
  bank: false,
  pure_platform: false,
};

@connect(
  state => ({
    userPartnerType: state.session.user.partner_type,
    ...state.submerchants,
  }),
  {
    fetchAll,
    openModal,
    closeModal,
    switchMerchant,
    showNotification,
  }
)
export default class SubMerchantsList extends ListContainer {
  state = {};

  handleAddMerchant = () => {
    this.props.openModal({
      size: 'small',
      component: <AddMerchant closeModal={this.props.closeModal} />,
    });
  };

  handleSwitchMerchant = merchantId => () => {
    this.props
      .switchMerchant(merchantId)
      .then(() => {
        window.location.reload();
      })
      .catch(errors => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  search = () => {};
  render() {
    const { userPartnerType } = this.props;
    return (
      <div class="sub-merchants-list">
        <div>
          <ShowWhen myRole="owner manager admin">
            <HeaderAction>
              <button
                class="btn btn-primary pull-right"
                onClick={this.handleAddMerchant}
              >
                <i class="i i-plus" />
                Add New Merchant
              </button>
            </HeaderAction>
          </ShowWhen>
          {/* <StatsCard
            title="Total transaction volume"
            value={humanReadableIndianCurrency(603000000)}
          />
          <StatsCard
            title="Number of Payments"
            value={humanReadableIndianCurrency(20630)}
          />
          <StatsCard
            title="My Earnings"
            value={humanReadableIndianCurrency(560000)}
          /> */}
        </div>
        <div class="content-wrapper">
          <ListFilter
            form="SubmerchantListFilter"
            type="link"
            count={this.state.count}
            onSubmit={this.search}
          />
          <DataTable
            title="Sub Merchants"
            count={this.state.count}
            skip={this.state.skip}
            paginate={this.paginate}
            columns={[
              name,
              id,
              email,
              addedOn,
              activationStatus,
              ...(switchMerchantAccessMap[userPartnerType]
                ? [switchMerchantActionBtn(this.handleSwitchMerchant)]
                : []),
            ]}
            {...this.props}
          />
        </div>
      </div>
    );
  }
}
