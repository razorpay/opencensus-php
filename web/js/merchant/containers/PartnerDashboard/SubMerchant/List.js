import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';

import DataTable from 'rzp/ui/Table/DataTable';
import { getTime } from 'rzp/ui/item';
import {
  submerchant as name,
  submerchantId as id,
  email as emailColumn,
} from 'rzp/ui/item/pair';

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

@connect(null, { fetchAll })
export default class SubMerchantsList extends Component {
  state = {};
  render() {
    return (
      <div class="content-wrapper">
        <DataTable
          title="Sub Merchants"
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          items={sampleResponse}
          columns={[name, id, email, addedOn, activationStatus, switchMerchant]}
        />
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
