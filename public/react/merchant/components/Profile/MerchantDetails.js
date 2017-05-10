import React, { PureComponent } from 'react';
import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';

export default class MerchantDetails extends PureComponent {
  render() {
    const { user } = this.props;
    return (
      <div class="row wrapper">
        <div class="list-group">
          <a class="list-group-item">
            <span class="pull-right">
              {titleCase(user.name)}
            </span>
            Merchant Name
          </a>
          <a href={`mailto:${user.email}`} class="list-group-item">
            <span class="pull-right">{user.email}</span>
            Merchant Email
          </a>
          <a class="list-group-item">
            <span class="pull-right">
              {/* tooltip="{{user.activated == 1 ? 'Activated' : 'Not Activated'}}" //TODO: pending */}

              <i
                class={`fa ${user.activated == 1 ? 'fa-check text-success' : 'fa-times text-danger'}`}
              />
            </span>
            Activation Status
          </a>
          <a class="list-group-item">
            <span class="pull-right">
              <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
            </span>
            Registration Date
          </a>
          <a class="list-group-item">
            <span class="pull-right">{user.activation_progress}%</span>
            Activation Form Progress
          </a>
        </div>
      </div>
    );
  }
}
