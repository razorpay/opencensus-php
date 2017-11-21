import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { observer } from 'mobx-react';

import { adminFetch } from 'util/fetch';
import { removeEntity } from './Entity';
import Collection from 'model/collection';

import { PageTable } from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';

const fields = [
  ['Organization ID', item => item.id],
  ['Business Name', item => item.business_name],
  ['Display Name', item => item.display_name],
  ['Email', item => item.email],
  ['Email Domain', item => item.email_domains.join(',')],
  ['Actions', item => <Actions item={item} />],
];

const Actions = ({ item }) => (
  <div>
    <div class="link m-t m-r ">
      <Link to={`/orgs/${item.id}`}>Edit</Link>
    </div>
    <div class="link m-t m-r">
      <Link to={`/fieldmaps/${item.id}`}>FieldMaps</Link>
    </div>
    {/* Delete button hidden for razorpay organisation */}
    {item.id !== 'org_100000razorpay' && (
      <AsyncButton
        class="link danger m-t m-r"
        pendingClass="link danger m-l btn-pending"
        confirm={`Are you sure you want to delete organisation id "${item.id}"`}
        onClick={item::removeEntity}
      >
        Delete
        <span class="spin-btn" />
      </AsyncButton>
    )}
  </div>
);

@observer
class OrganizationsList extends Component {
  collection = new Collection({
    data: {
      route_name: 'org_get_multiple',
    },
    fetchFn: adminFetch,
  });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Organizations
            <button class="btn pull-right">
              <Link to={'/orgs/new'}>Add an Organization</Link>
            </button>
          </header>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}

export default OrganizationsList;
