import React, { Component } from 'react';

import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, FromField, ToField, CheckField } from 'ui/Field';

import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { statusPill, publicFeature } from 'common/data';
import { snakeToTitleCase, prevent, formatDate } from 'common/util';

const fields = [
  ['id', item => item.id],
  ['Begin', item => formatDate(item.begin)],
  ['End', item => formatDate(item.end)],
  ['Method', item => item.method],
  ['Gateway', item => item.gateway],
  ['Issuer', item => item.issuer],
  ['Partial', item => item.partial],
  ['Scheduled', item => item.scheduled],
  ['Card Type', item => item.card_type],
  ['Network', item => item.network],
  ['Terminal', item => item.terminal_id],
  ['Reason Code', item => item.reason_code],
  ['Source', item => item.source],
  ['Comment', item => item.comment],
];

export default class PublicFeaturesList extends Component {
  collection = new Collection({
    data: {
      url: 'live/gateway/downtimes',
    },
    fetchFn: adminFetch,
  });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Gateway Downtimes</header>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}
