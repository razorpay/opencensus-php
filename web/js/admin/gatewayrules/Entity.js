import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, {
  SelectField,
  SelectMode,
  SelectMethod,
  TextAreaField,
} from 'ui/Field';
import { openModal, notifyError } from 'common/modal';
import { observer } from 'mobx-react';
import user from 'admin/user';
import BaseModal from 'ui/BaseModal';
import {
  methods,
  gateways,
  cardTypes,
  networks,
  categories,
  gatewayAcquirers,
} from 'util/data';

import { merchantId } from 'ui/Item';
import Duplex from 'ui/Duplex';
import GatewayRule from './model';

class EntityProps extends Component {
  render() {
    const { model } = this.props;
    const isEditable = user.permissions.indexOf('edit_gateway_rule') > -1;

    return (
      <BaseModal
        header={`${isEditable ? 'Edit' : 'View'} Rule`}
        noPadding={!isEditable}
      >
        {isEditable ? (
          <GatewayRuleForm model={model} />
        ) : (
          <Duplex fields={fields} model={model} />
        )}
      </BaseModal>
    );
  }
}

const fields = [
  item => ['Type', item.type],
  item => item.type === 'filter' && ['Filter Type', item.filter_type],
  item => item.type === 'sorter' && ['Load', item.load],
  item => item.group && ['Group', item.group],
  item => ['Merchant Id', merchantId(item)],
  item => ['Method', methods[item.method]],
  item => ['Gateway', gateways[item.method][item.gateway]],
  item => item.method_type && ['Card Type', cardTypes[item.method_type]],
  item => item.network && ['Network', networks[item.network]],
  item => ['Currency', item.currency],
  item => item.international && ['International', item.international],
  item => (item.iins.length && ['IINs', item.iins]) || null,
  item => (item.min_amount && ['Min Amount', item.min_amount]) || null,
  item => (item.max_amount && ['Max Amount', item.max_amount]) || null,
  item => item.issuer && ['Issuer', item.issuer],
  item => item.category2 && ['Category2', categories[item.category2]],
  item =>
    item.gateway_acquirer && [
      'Gateway Acquirer',
      gatewayAcquirers[item.gateway_acquirer],
    ],
  item => item.shared_terminal && ['Shared Terminal', item.shared_terminal],
];

@observer
class GatewayRuleForm extends Component {
  handleSubmit = body => {
    const { model } = this.props;

    const mode = body.mode;
    delete body.mode;

    if (model.id) {
      const data = {}; // Only below 5 fields are allowed while Edit

      if (body['load'] && body['load'] !== model['load']) {
        data['load'] = body['load'];
      }

      if (body['group'] && body['group'] !== model['group']) {
        data['group'] = body['group'];
      }

      if (body['iins'] && body['iins'] !== model['iins']) {
        data['iins'] = body['iins'];
      }

      if (body['filter_type'] && body['filter_type'] !== model['filter_type']) {
        data['filter_type'] = body['filter_type'];
      }

      if (body['comment'] && body['comment'] !== model['comment']) {
        data['comment'] = body['comment'];
      }

      if (!Object.keys(data).length) {
        notifyError('Make changes before saving');
        return;
      }
      return model.update(this.props.model.id, data, mode);
    } else {
      return model.save(body, mode);
    }
  };

  render() {
    let { model } = this.props;
    return (
      <Form onSubmit={this.handleSubmit}>
        {model.id && (
          <div class="field">
            <label>Rule Id</label>
            <strong>{model.id}</strong>
          </div>
        )}
        <Field
          label="Merchant Id"
          name="merchant_id"
          defaultValue={model.merchant_id}
          required
          disabled={!!model.id}
        />
        <SelectMode defaultValue={'live'} required disabled={!!model.id} />
        <SelectField
          label="Type"
          name="type"
          required
          onChange={model.onPropChange}
          value={model.type}
          disabled={!!model.id}
        >
          <option value="" />
          <option value="sorter">Sorter</option>
          <option value="filter">Filter</option>
        </SelectField>
        {model.type === 'sorter' && (
          <Field
            label="Load"
            name="load"
            type="number"
            min="0"
            max="100"
            placeholder="Load value, 0-100"
            defaultValue={model.load}
            required
          />
        )}
        {model.type === 'filter' && (
          <SelectField
            label="Filter Type"
            name="filter_type"
            defaultValue={model.filter_type}
            required
          >
            <option value="" />
            <option value="select">Select</option>
            <option value="reject">Reject</option>
          </SelectField>
        )}

        <Field label="Group" name="group" defaultValue={model.group} />
        <SelectMethod
          required
          value={model.method}
          onChange={model.onPropChange}
          disabled={!!model.id}
        />
        <SelectField
          name="gateway"
          label="Gateway"
          defaultValue={model.gateway}
          disabled={!!model.id}
        >
          <option value="">All</option>
          {model.method &&
            Object.keys(gateways[model.method]).map((m, index) => (
              <option key={index} value={m}>
                {gateways[model.method][m]}
              </option>
            ))}
        </SelectField>
        {(model.method === 'card' || model.method === 'emi') && (
          <div>
            <SelectField
              name="method_type"
              label="Card Type"
              defaultValue={model.method_type}
              disabled={!!model.id}
            >
              <option value="">All</option>
              {Object.keys(cardTypes).map((m, index) => (
                <option key={index} value={m}>
                  {cardTypes[m]}
                </option>
              ))}
            </SelectField>
            <SelectField
              defaultValue={model.network}
              name="network"
              label="Card Network"
              disabled={!!model.id}
            >
              <option value="">All</option>
              {Object.keys(networks).map((m, index) => (
                <option key={index} value={m}>
                  {networks[m]}
                </option>
              ))}
            </SelectField>
            <SelectField
              name="currency"
              label="Currency"
              defaultValue={model.currency}
              disabled={!!model.id}
            >
              <option value="INR">INR</option>
              <option value="USD">USD</option>
            </SelectField>
            <SelectField
              name="international"
              label="International"
              defaultValue={model.international}
              disabled={!!model.id}
            >
              <option value="" />
              <option value="0">No</option>
              <option value="1">Yes</option>
            </SelectField>
            <Field
              style={{ width: 300 }}
              label="IINs"
              name="iins"
              defaultValue={model.iins}
              placeholder="6 digit IINs, comma separated"
              pattern="^(\d{6},)*\d{6}$"
            />
          </div>
        )}

        <Field
          type="number"
          min="0"
          label="Min Amount"
          name="min_amount"
          placeholder="In Rupees"
          disabled={!!model.id}
        />
        <Field
          type="number"
          min="0"
          label="Max Amount"
          name="max_amount"
          placeholder="In Rupees"
          disabled={!!model.id}
        />
        <Field
          label="Issuer"
          name="issuer"
          defaultValue={model.issuer}
          disabled={!!model.id}
        />
        <SelectField
          defaultValue={model.category2}
          name="category2"
          label="Category2"
          disabled={!!model.id}
        >
          <option value="" />
          {Object.keys(categories).map((m, index) => (
            <option key={index} value={m}>
              {categories[m]}
            </option>
          ))}
        </SelectField>

        <SelectField
          defaultValue={model.gateway_acquirer}
          name="gateway_acquirer"
          label="Gateway Acquirer"
          disabled={!!model.id}
        >
          <option value="" />
          {Object.keys(gatewayAcquirers).map((m, index) => (
            <option key={index} value={m}>
              {gatewayAcquirers[m]}
            </option>
          ))}
        </SelectField>

        <SelectField
          name="shared_terminal"
          label="Shared Terminal"
          defaultValue={model.shared_terminal}
          disabled={!!model.id}
        >
          <option value="" />
          <option value="0">No</option>
          <option value="1">Yes</option>
        </SelectField>
        <TextAreaField
          label="Add Comment:"
          name="comment"
          style={{ width: '275px' }}
        />
        <button>Save</button>
      </Form>
    );
  }
}

export function showEntity(collection) {
  openModal(
    this ? (
      <EntityProps model={this} />
    ) : (
      <BaseModal header="Create new Gateway Rule">
        <GatewayRuleForm model={new GatewayRule(collection)} />
      </BaseModal>
    )
  );
}
