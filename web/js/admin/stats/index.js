import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, FromField, ToField } from 'ui/Field';
import { adminPost } from 'util/fetch';
import StatsModel from './model';
import { observer } from 'mobx-react';

@observer
export default class MerchantStats extends Component {
  model = new StatsModel();

  onSubmit = _ => this.model.submit();

  updateData = e => (this.model.getData()[e.target.name] = e.target.value);

  render() {
    let model = this.model;
    let formData = model.getData();

    return (
      <div class="dual-container">
        <aside>
          <Form onSubmit={this.onSubmit}>
            <Field
              name="merchant_id"
              label="Merchant Id"
              value={formData.merchant_id}
              onChange={this.updateData}
            />
            <SelectField
              name="type"
              label="Analysis Type"
              value={formData.type}
              onChange={this.updateData}
            >
              <option value="sum">Payment Volume</option>
              <option value="success_rate">Success Rate</option>
              <option value="summary">Summary</option>
            </SelectField>
            <FromField onChange={this.updateData} />
            <ToField onChange={this.updateData} />
            <SelectField
              name="interval"
              label="Interval"
              value={formData.interval}
              onChange={this.updateData}
            >
              <option value="">None</option>
              <option value="histogram_hourly">Hourly</option>
              <option value="histogram_daily">Daily</option>
              <option value="histogram_weekly">Weekly</option>
            </SelectField>
            <SelectField
              name="group"
              label="Group By"
              value={formData.group}
              onChange={this.updateData}
            >
              <option value="">None</option>
              <option value="method">Method</option>
              <option value="platform">Platform</option>
              <option value="type">Card Type</option>
              <option value="network">Network</option>
              <option value="bank">Bank</option>
              <option value="wallet">Wallet</option>
            </SelectField>
            <SelectField
              name="filter"
              label="Payment Method"
              value={formData.filter}
              onChange={this.updateData}
            >
              <option value="">All</option>
              <option value="card">Card</option>
              <option value="netbanking">Netbanking</option>
              <option value="wallet">Wallet</option>
              <option value="upi">UPI</option>
            </SelectField>
            <button>Go</button>
          </Form>
        </aside>
        <main onClick={model.setSelected}>
          {model.items.map(
            (i, index) =>
              (index && (
                <div
                  onClick={model.setSelected}
                  data-key={index}
                  key={index}
                  class={
                    (model.selected.get() === index && 'box selected') || 'box'
                  }
                >
                  {i.component.get()}
                </div>
              )) ||
              null
          )}
        </main>
      </div>
    );
  }
}
