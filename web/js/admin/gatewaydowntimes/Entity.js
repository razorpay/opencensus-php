import React, { Component } from 'react';
import { openModal, closeModal, notifyDone, notifyError } from 'common/modal';
import Form from 'ui/Form';
import Field, { CheckField, DateField, SelectField } from 'ui/Field';
import { ModalContent } from 'component/Modal';
import { adminPut, adminPost } from 'common/fetch';
import { snakeToTitleCase } from 'common/util';
import { gateways, cardTypes, networks } from 'common/data';
import AsyncButton from 'ui/AsyncButton';

const supportedMethods = {
  netbanking: 'Netbanking',
  card: 'Card',
  upi: 'UPI',
  wallet: 'Wallet',
};
const reasonCodes = [
  'LOW_SUCCESS_RATE',
  'HIGHER_DECLINES',
  'ISSUER_DOWN',
  'SCHEDULED_DOWNTIME',
  'OTHER',
];
const sources = ['STATUSCAKE', 'BILLDESK', 'BANK', 'OTHER'];

export default class Entity extends Component {
  constructor(props) {
    super(props);
    this.model = this.props.model || {};
    this.state = {
      paymentMethod: this.model.method || 'card',
      scheduled: this.model.scheduled || false,
    };
  }

  preparePayload = body => {
    // Remove restricted keys and format timestamps
    if (body.begin_at_date) {
      body.begin = moment(
        `${body.begin_at_date} ${body.begin_at_time}`,
        'DD/MM/YYYY HH:mm'
      ).unix();
    }
    if (body.end_at_date) {
      body.end = moment(
        `${body.end_at_date} ${body.end_at_time}`,
        'DD/MM/YYYY HH:mm'
      ).unix();
    }

    ['begin_at_date', 'begin_at_time', 'end_at_date', 'end_at_time'].forEach(
      el => delete body[el]
    );
    if (this.props.model) {
      ['gateway', 'method', 'source'].forEach(el => delete body[el]);
    }
    return body;
  };

  onSubmit = body => {
    let promise;
    let payload = {
      data: this.preparePayload(body),
      url: 'live/gateway/downtimes',
    };

    if (this.props.model) {
      payload.url = `live/gateway/downtimes/${this.props.model.id}`;
      promise = adminPut(payload).then(data => {
        if (data) {
          this.props.collection.update(data);
          return data;
        }
      });
    } else {
      promise = adminPost(payload).then(data => {
        if (data) {
          this.props.collection.items.push(data);
          return data;
        }
      });
    }

    return promise.then(data => {
      if (data) {
        closeModal();
        notifyDone();
      }
    });
  };

  render() {
    let {
      id,
      begin,
      end,
      card_type,
      comment,
      gateway,
      issuer,
      method,
      network,
      reason_code,
      source,
      terminal_id,
      partial,
      scheduled,
    } = this.model;
    let beginDate = begin ? moment.unix(begin) : moment();
    let endDate = end && moment.unix(end);
    let isNew = id ? false : true;
    let isEditable = !scheduled;

    return (
      <ModalContent
        header={!isNew ? `Edit Downtime – ${id}` : 'Add a new Downtime'}
      >
        <Form class="full-span downtime-form">
          <div>
            {isNew || scheduled ? (
              <CheckField
                label="Scheduled"
                name="scheduled"
                defaultChecked={scheduled}
                disabled={!isNew}
                onChange={e => {
                  this.setState({
                    scheduled: !this.state.scheduled,
                  });
                }}
              />
            ) : (
              ''
            )}

            {isEditable ? (
              <DateField
                required
                name="begin_at_date"
                label="Starts at"
                defaultValue={beginDate}
                component={
                  <input
                    type="time"
                    name="begin_at_time"
                    defaultValue={beginDate.format('HH:MM')}
                  />
                }
                allowAllDates={true}
              />
            ) : (
              ''
            )}

            {isEditable ? (
              <DateField
                required={this.state.scheduled}
                name="end_at_date"
                label="Ends at"
                defaultValue={endDate}
                component={
                  <input
                    type="time"
                    name="end_at_time"
                    defaultValue={endDate ? endDate.format('HH:MM') : '09:00'}
                  />
                }
                allowAllDates={true}
                disabled={!!endDate}
              />
            ) : (
              ''
            )}

            <SelectField
              required
              label="Method"
              name="method"
              defaultValue={method || this.state.paymentMethod}
              disabled={!isNew || !isEditable}
              onChange={e => {
                this.setState({
                  paymentMethod: e.currentTarget.value,
                });
              }}
            >
              {Object.keys(supportedMethods).map(key => (
                <option key={key} value={key}>
                  {supportedMethods[key]}
                </option>
              ))}
            </SelectField>

            <SelectField
              required
              label="Gateway"
              name="gateway"
              defaultValue={gateway}
              disabled={!isNew || !isEditable}
            >
              <option key="ALL" value="ALL">
                All
              </option>
              {Object.keys(gateways[this.state.paymentMethod]).map(key => (
                <option key={key} value={key}>
                  {gateways[this.state.paymentMethod][key]}
                </option>
              ))}
            </SelectField>

            {this.state.paymentMethod === 'card' ? (
              <SelectField
                required
                label="Card Type"
                name="card_type"
                defaultValue={card_type}
                disabled={!isEditable}
              >
                <option key="ALL" value="ALL">
                  All
                </option>
                {Object.keys(cardTypes).map(key => (
                  <option key={key} value={key}>
                    {cardTypes[key]}
                  </option>
                ))}
              </SelectField>
            ) : (
              ''
            )}

            <SelectField
              required
              label="Source"
              name="source"
              defaultValue={source}
              disabled={!isNew || !isEditable}
            >
              {sources.map(val => (
                <option key={val} value={val}>
                  {snakeToTitleCase(val)}
                </option>
              ))}
            </SelectField>

            <SelectField
              required
              label="Reason Code"
              name="reason_code"
              defaultValue={reason_code}
              disabled={!isEditable}
            >
              {reasonCodes.map(val => (
                <option key={val} value={val}>
                  {snakeToTitleCase(val)}
                </option>
              ))}
            </SelectField>

            <Field
              label="Issuer"
              name="issuer"
              defaultValue={issuer}
              disabled={!isEditable}
            />

            {this.state.paymentMethod === 'card' ? (
              <SelectField
                label="Network"
                name="network"
                defaultValue={network}
                disabled={!isEditable}
              >
                <option key="NA" value="NA">
                  NA
                </option>
                <option key="ALL" value="ALL">
                  All
                </option>
                {Object.keys(networks).map(key => (
                  <option key={key} value={key}>
                    {networks[key]}
                  </option>
                ))}
              </SelectField>
            ) : (
              ''
            )}

            <Field
              label="Comment"
              name="comment"
              defaultValue={comment}
              disabled={!isEditable}
            />

            <Field
              label="Terminal ID"
              name="terminal_id"
              defaultValue={terminal_id}
              disabled={!isEditable}
            />

            <CheckField
              label="Partial"
              name="partial"
              defaultChecked={partial}
              disabled={!isEditable}
            />

            <footer class="text-right">
              {isEditable ? (
                <AsyncButton
                  text="Save"
                  class="btn"
                  pendingClass="small spinner"
                  onSubmit={this.onSubmit}
                />
              ) : (
                <button class="btn btn-default" onClick={closeModal}>
                  Close
                </button>
              )}
            </footer>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

export function showEntity(collection) {
  openModal(<Entity collection={collection} model={this} />);
}

export function markEntityComplete(collection, item) {
  let { terminal_id, ...data } = item;

  if (!data.scheduled) {
    data.end = moment().unix();
    if (terminal_id) {
      data.terminal_id = terminal_id;
    }
    // Deleting restricted keys
    [
      'gateway',
      'method',
      'source',
      'admin',
      'id',
      'entity',
      'updated_at',
      'created_at',
    ].forEach(el => delete data[el]);
    let payload = {
      data: data,
      url: `live/gateway/downtimes/${item.id}`,
    };
    adminPut(payload).then(data => {
      if (data) {
        collection.remove(item);
        notifyDone();
      }
    });
  } else {
    notifyError('You cannot end a scheduled downtime.');
  }
}
