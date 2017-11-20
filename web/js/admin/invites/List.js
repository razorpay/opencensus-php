import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminFetch } from 'util/fetch';
import { PageTable } from 'ui/Table';
import { showEntity, showDetails } from './Entity';

const fields = [
  ['Invitation ID', item => item.id],
  ['Merchant Email', item => item.email],
  ['Signed Up', item => (item.signed_up_at ? 'Yes' : 'No')],
  ['Created At', item => new Date(item.created_at * 1000).toTimeString()],
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
            <div class="btn pull-right" onClick={this.showEntity}>
              Invite a Merchant
            </div>
          </header>
        </div>
        <PageTable
          model={this.collection}
          fields={fields}
          onClick={showDetails}
        />
      </div>
    );
  }
}
