import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { observer } from 'mobx-react';

import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';

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
    <div class="link">
      <Link to={`/orgsEdit/${item.id}`}>Edit</Link>
    </div>
    <div class="link">
      <Link to={`/fieldmaps/${item.id}`}>FieldMaps</Link>
    </div>
    <div class="link danger">Delete</div>
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

  onSubmit = filters => this.collection.setFilters(filters);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Organizations
            <button class="btn">
              <Link to={'/orgsEdit'}>Add an Organization</Link>
            </button>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field label="Search" />
          </Form>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}

export default OrganizationsList;
