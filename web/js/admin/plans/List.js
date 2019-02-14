import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { SelectField } from 'ui/Field';

import { adminFetch } from 'common/fetch';
import { openPricingEntity, copyPricingEntity } from './Entity';
import { isBlank } from 'common/util';
import { isOrgRazorpay } from 'admin/user';

function fetchFn() {
  return adminFetch(...arguments).then(
    data =>
      data &&
      data.map(p => ({
        id: p.plan_id,
        name: p.plan_name,
        rules_count: p.rules_count,
        org_id: `org_${p.org_id}`,
      }))
  );
}

export default class PlanList extends Component {
  state = {
    selectedOrg: undefined,
    orgs: [], //default as we fetch all orgs
  };

  collection = new Collection({
    data: {
      url: 'live/pricing/merchants',
      copyItem: rules => this.copyPricing(rules),
      handleOrgChange: e => this.handleOrgChange(e),
    },
    extraFields: {
      selectedOrg: null,
    },
    fetchFn,
  });

  newPricingEntity = () =>
    openPricingEntity.call(
      {
        collection: this.collection,
      },
      this.state.orgs
    );

  copyPricing = rules => {
    copyPricingEntity.call(
      {
        collection: this.collection,
      },
      rules,
      this.state.orgs
    );
  };

  componentWillMount() {
    if (isOrgRazorpay()) {
      adminFetch('live/orgs').then(response => {
        if (response) {
          const orgMap = {};
          response.items.forEach(item => (orgMap[item.id] = item.display_name));
          this.setState({
            orgs: orgMap,
          });
        }
      });
    }
  }

  handleOrgChange = e => {
    const orgId = e.target.value;
    fetchFn({
      data: {
        count: 20,
        skip: 0,
      },
      url: 'live/pricing/merchants',
      ...(orgId && {
        headers: {
          'x-cross-org-id': orgId,
        },
      }),
    }).then(response => {
      this.setState({
        selectedOrg: orgId,
      });

      this.collection.extraFields.selectedOrg = orgId;
      this.collection.items.replace(
        response.map(item => new CollectionItem(this.collection, item))
      );
    });
  };

  getFields = () => {
    const { orgs } = this.state;

    let fields = [
      ['Plan ID', item => item.id],
      ['Plan Name', item => item.name],
      ['Number of Rules', item => item.rules_count || item.count],
    ];

    if (isOrgRazorpay()) {
      fields.push(['Org Name', item => orgs[item.org_id]]);
    }

    return fields;
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
          {isOrgRazorpay() && !isBlank(orgs) && (
            <div class="filters">
              <SelectField
                label="Organisation"
                name="org_id"
                value={this.state.selectedOrg}
                onChange={this.handleOrgChange}
              >
                <option value="">All</option>
                {Object.keys(orgs).map(orgId => (
                  <option key={orgId} value={orgId}>
                    {orgs[orgId]}
                  </option>
                ))}
              </SelectField>
            </div>
          )}
        </div>
        <PageTable
          model={this.collection}
          fields={this.getFields()}
          onClick={openPricingEntity}
          animateRow={false}
        />
      </div>
    );
  }
}
