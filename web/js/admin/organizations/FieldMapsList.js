import React, { Component } from 'react';

import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';

const fields = [
  ['Organization ID', item => item.id],
  ['Business Name', item => item.business_name],
  ['Display Name', item => item.display_name],
  ['Email', item => item.email],
  ['Email Domain', item => item.email_domains.join(',')],
  ['Actions', item => <Actions id={item.id} />],
];

class FieldMapsList extends Component {
  collection = new Collection({
    data: {
      route_name: 'org_fieldmap_get_multiple',
      url_params: {
        orgId: this.props.orgId,
      },
    },
    fetchFn: adminFetch,
  });
  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Organizations</header>
          <Form>
            <Field label="Search" />
            <button>Add an Organization</button>
          </Form>
        </div>
        <Table model={this.collection} fields={fields} />
      </div>
    );
  }
}

export default FieldMapsList;
