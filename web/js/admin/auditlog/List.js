import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

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
      <PageTable
        title="Audit Log"
        info={false}
        model={this.collection}
        fields={fields}
      />
    );
  }
}

const fields = [
  ['Event', item => item.event],
  ['Recipient', item => item.recipient],
  ['Subject', item => item.message.headers.subject],
  ['Failure Reason', item => item.reason],
  ['Tags', item => <pre>{item.tags.join('\n')}</pre>],
  ['Timestamp', item => new Date(1e3 * item.timestamp).toString()],
];
