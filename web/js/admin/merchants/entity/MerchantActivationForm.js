import React, { Component } from 'react';
import { observer } from 'mobx-react';

import { adminFetch } from 'util/fetch';
import { openModal, confirm } from 'common/modal';

import Model from './model';
import EntityRow from 'ui/EntityRow';
import Table from 'ui/Table';

@observer
export default class MerchantActivationForm extends Component {
  state = {};

  constructor(props) {
    super();
    this.merchantId = props.match.params.id;

    this.model = new Model({
      fetchFn: adminFetch,
      merchantId: this.merchantId,
    });
  }

  componentWillMount() {
    adminFetch({
      route_name: 'invitation_fetch',
      merchant_id: this.merchantId,
    }).then(data => {
      this.setState({
        pendingInvites: data,
      });
    });

    adminFetch({
      route_name: 'merchant_fetch_users',
      url_params: {
        id: this.merchantId,
      },
    }).then(data => {
      this.setState({
        users: data,
      });
    });
  }

  getOverview() {
    const { details } = this.model.merchant;

    return (
      <div class="box">
        <div class="heading">
          Merchant: <b>{this.merchantId}</b>
        </div>
        {!Object.keys(details).length ? (
          <div class="spinner" />
        ) : (
          _getOverviewFields(details).map(row => (
            <EntityRow
              key={row.label}
              label={row.label}
              value={row.value}
              className="separate"
            />
          ))
        )}
      </div>
    );
  }

  render() {
    return (
      <div class="entity-container">
        <header class="heading">Activation Form</header>
        {this.getOverview()}

        <div class="box">
          <div class="heading">Merchant Activation Form</div>
        </div>
      </div>
    );
  }
}

/* Resources */

function _getOverviewFields(details) {
  return [
    {
      label: 'Name',
      value: details.name,
    },
    {
      label: 'Email',
      value: details.email,
    },
    {
      label: 'Activation Form Submitted',
      value: () => <i class={`i i-${details.submitted == 1 ? 'yes' : 'no'}`} />,
    },
    {
      label: 'Activation Form Status',
      value: () => (
        <i class={`i i-${details.locked == 1 ? 'lock' : 'unlock'}`} />
      ),
    },
    {
      label: 'Activated',
      value: () => <i class={`i i-${details.activated == 1 ? 'yes' : 'no'}`} />,
    },
  ];
}
