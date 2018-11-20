import { Fragment } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';

import { openModal, closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { fetchSubmerchants as fetchAll } from 'merchant/modules/collection';
import { switchMerchant } from 'merchant/modules/session';

import DataTable from 'rzp/ui/Table/DataTable';
import HeaderAction from 'rzp/ui/HeaderAction';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

import ShowWhen from 'merchant/components/ShowWhen';
import { getTime } from 'rzp/ui/item';
import { ActivationStatusLabel } from 'merchant/components/StatusLabel';
import {
  submerchant as submerchantColumn,
  submerchantId as id,
  email as emailColumn,
} from 'rzp/ui/item/pair';

import AddMerchant from './AddMerchant';
import ListFilter from './ListFilter';
import {
  trackListEvents,
  trackSearchAnalytics,
  trackClearAnalytics,
} from '../ga';

const name = isPurePlatform => ({
  ...submerchantColumn,
  ...(isPurePlatform && {
    value: item => (
      <Link to={`/submerchants/${item.id}/${item.application.id}`}>
        {item.name}
      </Link>
    ),
  }),
});

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
      Activation Status&nbsp;
      <span>
        <i class="i i-info-circle" />&nbsp;
        <Popover align="top" theme="dark">
          <PopoverBody>
            Current status of merchant's activation request
          </PopoverBody>
        </Popover>
      </span>
    </Fragment>
  ),
  value: submerchant =>
    submerchant.details && submerchant.details.activation_status ? (
      <>
        {submerchant.details.activation_status === 'instantly_activated' && (
          <>
            <ActivationStatusLabel
              status={submerchant.details.activation_status}
            />&nbsp;
            <i class="i i-info-circle" />
            <Popover align="right" theme="dark">
              <PopoverBody>
                The merchant can accept live payments but settlements will be on
                hold until KYC completion.
              </PopoverBody>
            </Popover>
          </>
        )}
      </>
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

const appId = {
  title: 'App Id',
  value: item => (
    <Link to={`/submerchants/applications/${item.application.id}`}>
      {item.application.id}
    </Link>
  ),
};

@connect(
  state => ({
    user: state.session.user,
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

  componentDidMount() {
    trackListEvents('Go To');
  }

  render() {
    const { user } = this.props;
    let appIdColumn = [],
      switchMerchantColumn = [];

    if (user.isPartner('pure_platform')) {
      appIdColumn = [appId];
    } else if (user.isPartner('aggregator', 'fully_managed')) {
      switchMerchantColumn = [
        switchMerchantActionBtn(this.handleSwitchMerchant),
      ];
    }

    return (
      <div class="sub-merchants-list">
        <div>
          <ShowWhen
            myRole="owner manager admin"
            additionalCondition={user =>
              user.isPartner() && !user.isPartner('pure_platform')
            }
          >
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
            onSearchAnalytics={trackSearchAnalytics}
            onClearAnalytics={trackClearAnalytics}
          />
          <DataTable
            title="Sub Merchants"
            count={this.state.count}
            skip={this.state.skip}
            paginate={this.paginate}
            columns={[
              name(user.isPartner('pure_platform')),
              id,
              email,
              ...appIdColumn,
              addedOn,
              activationStatus,
              ...switchMerchantColumn,
            ]}
            {...this.props}
          />
        </div>
      </div>
    );
  }
}
