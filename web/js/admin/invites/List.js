import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminFetch } from 'util/fetch';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';

import { showEntity } from './Entity';

const fields = [
  ['Invitation ID', item => item.id],
  ['Merchant Email', item => item.email],
  ['Signed Up', item => (item.signed_up_at ? 'Yes' : 'No')],
  ['Created At', item => item.created_at],
  ['', item => <div class="btn">Details</div>],
];

@observer
export default class InvitesList extends Component {
  collection = new Collection({
    data: {
      route_name: 'admin_lead_get_multiple',
    },
    fetchFn: adminFetch,
    model: CollectionItem,
  });

  showEntity = showEntity.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Invitations
            <div class="btn" onClick={this.showEntity}>
              Invite a Merchant
            </div>
          </header>
          <Form class="filters">
            <Field label="Search" />
          </Form>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}
