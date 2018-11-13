import React, { Component } from 'react';
import Table, { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { adminFetch, adminPost } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import ToggleEntityRow from 'ui/ToggleEntityRow';
import { Link } from 'react-router-dom';
import Duplex from 'ui/Duplex';
import { formatDate, getFormattedAmount } from 'common/util';
import { openModal } from 'common/modal';
import { ModalContent } from 'component/Modal';
import Field, { SelectField } from 'ui/Field';
import Form from 'ui/Form';

export default class RefundsList extends Component {
  state = {
    loading: true,
  };

  componentWillMount() {
    adminFetch('live/scrooge/refunds/' + this.props.match.params.id).then(d => {
      this.data = d;
      this.setState({
        loading: false,
      });
    });
  }

  render() {
    return (
      <div class="entity-page">
        <main class="limited box">
          <header>
            Refund Details
            {/* <AsyncButton class="btn">Process</AsyncButton> */}
            <Link
              to={`/scrooge/refunds${location.search}`}
              class="link"
              style={{ float: 'right', fontSize: 14, marginTop: 10 }}
            >
              Return to Refunds List
            </Link>
          </header>
          <Duplex
            pending={this.state.loading}
            model={this.data}
            fields={fields}
          />
          {this.data && (
            <ToggleEntityRow label="Logs">
              <Table items={this.data.state_machine_logs} fields={logFields} />
            </ToggleEntityRow>
          )}
        </main>
        <aside class="container">
          {this.data &&
            !this.state.loading && (
              <div>
                <div class="header">
                  <b>ACTIONS</b>
                </div>
                <AsyncButton
                  confirm
                  class="btn btn-default"
                  onClick={this.retry}
                >
                  Retry Refund
                </AsyncButton>
                <button class="btn btn-default" onClick={this.statusModal}>
                  Update Status
                </button>
              </div>
            )}
        </aside>
      </div>
    );
  }

  retry = () => {
    return adminPost(`live/refunds/${this.data.id}/retry`).then(data => {
      if (data) {
        notifySuccess('Refund retry request is successful');
      }
    });
  };

  statusModal = () => {
    openModal(
      <ModalContent header="Update Status">
        <Form onSubmit={this.updateStatus}>
          <SelectField name="event" label="Event">
            {this.data.available_status_update_events.map((e, i) => (
              <option key={i} value={e}>
                {e}
              </option>
            ))}
          </SelectField>
          <Field name="arn" label="ARN" />
          <button>Update</button>
        </Form>
      </ModalContent>
    );
  };

  updateStatus = data => {
    return adminPost({
      url: 'live/scrooge/refunds/bulk-status-update',
      data: [
        {
          event: data.event,
          refund_id: this.data.id,
          gateway_keys: {
            arn: data.arn,
          },
        },
      ],
    }).then(data => {
      if (data) {
        notifySuccess('Update status request is successful');
      }
    });
  };
}

const fields = [
  item => ['Refund ID', <b>{item.id}</b>],
  item => ['Gateway', item.gateway],
  item => ['Method', item.method],
  item => ['Amount', item.currency + ' ' + getFormattedAmount(item.amount)],
  item => ['Status', item.status],
  item => ['Refund Created At', formatDate(item.created_at)],
  item => ['Refund Updated At', formatDate(item.updated_at)],
  item => ['Last Attempted', formatDate(item.last_attempted_at)],
  item => ['Payment ID', <b>{item.payment_id}</b>],
  item => ['Payment Created At', formatDate(item.payment_created_at)],
  item => [
    'Payment Amount',
    item.currency + ' ' + getFormattedAmount(item.payment_amount),
  ],
  item => ['Payment Gateway Captured', item.payment_gateway_captured],
  item => ['ARN', item.arn],
  item => ['Attempts', item.attempts],
  item => ['Bank', item.bank],
  item => ['Is Reconciled', item.is_reconciled],
  item => ['On Hold Reason', item.on_hold_reason],
];

const logFields = [
  ['Date', item => formatDate(item.created_at)],
  ['From', item => item.from],
  ['To', item => item.to],
];
