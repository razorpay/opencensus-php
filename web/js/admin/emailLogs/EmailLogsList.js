import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, CheckField } from 'ui/Field';
import Collection from 'model/collection';
import fetch from 'util/fetch';

const emailFetch = ({ query_params }) => {
  if (!query_params.event) {
    query_params.event = 'NOT accepted';
  }
  if (!query_params.recipient) {
    query_params.recipient =
      'NOT https://api.razorpay.com/v1/mailgun/callback/failure';
  }
  query_params.ascending = 'no';
  return fetch({
    url: '/admin/emaillogs',
    params: query_params,
  });
};

export default class EmailLogsList extends Component {
  collection = new Collection({
    fetchFn: emailFetch,
    filters: null,
  });

  onSubmit = filters => this.collection.applyFilters(filters);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Emails</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field
              name="recipient"
              type="email"
              label="Recipient"
              placeholder="abc@xyz.com"
            />
            <Field
              name="tag"
              label="Tag"
              placeholder="payment_successful AND pay_7baYnDji90l"
            />
            <SelectField name="event" label="Event" defaultValue={''}>
              <option value="">All</option>
              <option value="delivered">Delivered</option>
              <option value="opened">Opened</option>
              <option value="clicked">Clicked</option>
              <option value="failed">Failed</option>
              <option value="rejected">Rejected</option>
            </SelectField>
            <button>Search</button>
          </Form>
        </div>
        <PageTable info={false} model={this.collection} fields={fields} />
      </div>
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
