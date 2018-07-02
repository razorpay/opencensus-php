import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import { openModal, closeModal } from 'rzp/modules/modals';

import DataTable from 'rzp/ui/Table/DataTable';
import StatsCard from 'rzp/ui/StatsCard';
import HeaderAction from 'rzp/ui/HeaderAction';

import { getTime } from 'rzp/ui/item';
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
  value: item => item.activation_status,
};

const switchMerchant = {
  title: 'Switch Merchant',
  value: () => 'Partnership Removed',
};

@connect(null, { fetchAll, openModal, closeModal })
export default class SubMerchantsList extends Component {
  state = {};

  handleAddMerchant = () => {
    this.props.openModal({
      size: 'small',
      component: <AddMerchant closeModal={this.props.closeModal} />,
    });
  };

  search = () => {};
  render() {
    return (
      <div class="sub-merchants-list">
        <div class="content-wrapper sub-merchants-list--stats">
          <HeaderAction>
            <button
              class="btn btn-primary pull-right"
              onClick={this.handleAddMerchant}
            >
              <i class="i i-plus" />
              Add New Merchant
            </button>
          </HeaderAction>
          <StatsCard
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
          />
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
            items={sampleResponse}
            columns={[
              name,
              id,
              email,
              addedOn,
              activationStatus,
              switchMerchant,
            ]}
          />
        </div>
      </div>
    );
  }
}

function fetchAll() {}

var sampleResponse = [
  {
    id: 'acc_abc1234561',
    name: 'Chai Time',
    email: 'submerchant@razorpay.com',
    created_at: 1528373839,
    activation_status: 'Not Submitted',
  },
  {
    id: 'acc_abc1234562',
    name: 'Swiggy',
    email: 'submerchant1@razorpay.com',
    created_at: 1528373839,
    activation_status: 'Rejected',
  },
  {
    id: 'acc_abc1234563',
    name: 'Fassos',
    email: 'submerchant2@razorpay.com',
    created_at: 1528373839,
  },
  {
    id: 'acc_abc1234564',
    name: 'Big Basket',
    email: 'submerchant3@razorpay.com',
    created_at: 1528373839,
    activation_status: 'Submitted',
  },
];
