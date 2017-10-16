import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, SelectMethod } from 'ui/Field';
import { openModal } from 'common/modal';
import { observer } from 'mobx-react';
import {
  gateways,
  cardTypes,
  networks,
  categories,
  gatewayAcquirers,
} from 'util/data';

@observer
export default class GatewayRuleEntity extends Component {
  render() {
    let { model } = this.props;
    return (
      <div>
        <header>Create new Gateway Rule</header>
        <Form onSubmit={model.save}>
          <SelectField
            label="Type"
            name="type"
            required
            onChange={model.onPropChange}
            value={model.type}
          >
            <option value="" />
            <option value="sorter">Sorter</option>
            <option value="filter">Filter</option>
          </SelectField>
          {model.type === 'sorter' && (
            <Field
              label="Load"
              type="number"
              min="0"
              max="100"
              placeholder="Load value, 0-100"
              defaultValue={model.load}
              required
            />
          )}
          {model.type === 'filter' && (
            <SelectField label="Filter Type" name="filter_type" required>
              <option value="" />
              <option value="select">Select</option>
              <option value="reject">Reject</option>
            </SelectField>
          )}

          <Field label="Group" name="group" defaultValue={model.group} />
          <Field
            label="Merchant Id"
            name="merchant_id"
            defaultValue={model.merchant_id}
            required
          />
          <SelectMethod
            required
            value={model.method}
            onChange={model.onPropChange}
          />
          <SelectField
            name="gateway"
            label="Gateway"
            defaultValue={model.gateway}
          >
            <option value="">All</option>
            {model.method &&
              Object.keys(gateways[model.method]).map((m, index) => (
                <option key={index} value={m}>
                  {gateways[model.method][m]}
                </option>
              ))}
          </SelectField>
          <br />
          {(model.method === 'card' || model.method === 'emi') && (
            <div>
              <SelectField
                name="method_type"
                label="Card Type"
                defaultValue={model.method_type}
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
              >
                <option value="INR">INR</option>
                <option value="USD">USD</option>
              </SelectField>
              <SelectField
                name="international"
                label="International"
                defaultValue={model.international}
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
                pattern="^(\\d{6},)*\\d{6}$"
              />
            </div>
          )}

          <Field
            type="number"
            min="0"
            label="Min Amount"
            name="min_amount"
            placeholder="In Rupees"
          />
          <Field
            type="number"
            min="0"
            label="Max Amount"
            name="max_amount"
            placeholder="In Rupees"
          />
          <Field label="Issuer" name="issuer" />
          <SelectField
            defaultValue={model.category2}
            name="category2"
            label="Category2"
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
          >
            <option value="" />
            <option value="0">No</option>
            <option value="1">Yes</option>
          </SelectField>
          <br />
          <button style={{ float: 'right' }}>Save</button>
        </Form>
      </div>
    );
  }
}

export function showEntity() {
  return openModal(<GatewayRuleEntity model={this} />);
}
