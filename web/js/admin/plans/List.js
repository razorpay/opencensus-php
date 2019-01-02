import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { SelectField } from 'ui/Field';

import { adminFetch } from 'common/fetch';
import { openPricingEntity, copyPricingEntity } from './Entity';
import { isOrgRazorpay } from 'admin/user';

function fetchFn() {
  return adminFetch(...arguments).then(
    data =>
      data &&
      data.map(p => ({
        id: p.plan_id,
        name: p.plan_name,
        rules_count: p.rules_count,
      }))
  );
}

export default class PlanList extends Component {
  state = {
    orgs: [], //default as we fetch all orgs
  };

  collection = new Collection({
    data: {
      url: 'live/pricing/merchants',
      copyItem: rules => this.copyPricing(rules),
    },
    fetchFn,
  });

  newPricingEntity = e =>
    openPricingEntity.call({
      collection: this.collection,
    });

  copyPricing = rules => {
    copyPricingEntity.call(
      {
        collection: this.collection,
      },
      rules
    );
  };

  componentWillMount() {
    if (isOrgRazorpay()) {
      adminFetch('live/orgs').then(response => {
        if (response) {
          this.setState({
            orgs: response.items,
          });
        }
      });
    }
  }

  handleOrgChange = e => {
    console.log(e.target.value);
  };

  render() {
    const { orgs } = this.state;
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Pricing Plans
            <div class="btn pull-right" onClick={this.newPricingEntity}>
              Add New
            </div>
          </header>
          {isOrgRazorpay() &&
            orgs.length > -1 && (
              <SelectField
                label="Organisation"
                name="org_id"
                onChange={this.handleOrgChange}
              >
                <option value="">All</option>
                {orgs.map(org => (
                  <option key={org.id} value={org.id.replace('org_', '')}>
                    {org.display_name}
                  </option>
                ))}
              </SelectField>
            )}
        </div>
        <PageTable
          model={this.collection}
          fields={pricingFields}
          onClick={openPricingEntity}
        />
      </div>
    );
  }
}

const pricingFields = [
  ['Plan ID', item => item.id],
  ['Plan Name', item => item.name],
  ['Number of Rules', item => item.rules_count || item.count],
];
