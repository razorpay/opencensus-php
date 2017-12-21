import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';

export default class AuditLogList extends Component {
  collection = new Collection({
    data: {
      route_name: 'auditlog_search',
    },
    filters: {
      count: 100,
    },
    fetchFn: adminFetch,
  });

  render() {
    return (
      <div class="entity-container">
        <header class="heading">Audit Log</header>
        <PageTable info={false} model={this.collection} fields={fields} />
      </div>
    );
  }
}

const fields = [
  ['Action', item => item.event.action],
  ['Category', item => item.event.category],
  ['Label', item => item.event.label],
  ['Failure Reason', item => item.reason],
  ['Timestamp', item => new Date(1e3 * item.event.created_at).toString()],
  ['Event Full Log', item => JSON.stringify(item.event)],
];
