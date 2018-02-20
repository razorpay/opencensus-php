import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

const options = {
  duration: [3, 6, 9, 12, 15, 18, 21, 24],
  methods: ['', 'card'],
  subvention: ['', 'customer', 'merchant'],
  bank: {
    RATN: 'RBL',
    HDFC: 'HDFC',
    UTIB: 'Axis',
    KKBK: 'Kotak',
    ICIC: 'ICICI',
    FDRL: 'Federal',
    INDB: 'Indusind',
    SCBL: 'Standard Chartered',
  },
};

export default class AddEMIPlan extends Component {
  static permission = 'create_emi_plan';
  static title = 'Add EMI Plan';

  state = {
    selectedSource: 'bank',
  };

  handleSourceChange = e => {
    this.setState({
      selectedSource: e.target.value,
    });
  };

  render() {
    return (
      <Form class="full-span full-elements">
        <div class="field multi">
          <label>
            {this.state.selectedSource === 'bank' ? 'Banks' : 'Network'}
          </label>
          {this.state.selectedSource === 'bank' ? (
            <select name="bank">
              {Object.keys(options.bank).map(key => (
                <option value={key} key={key}>
                  {options.bank[key]}
                </option>
              ))}
            </select>
          ) : (
            <select name="network">
              <option value="AMEX">AMEX</option>
            </select>
          )}
          <select
            value={this.state.selectedSource}
            onChange={this.handleSourceChange}
          >
            <option value="bank">Banks</option>
            <option value="network">Network</option>
          </select>
        </div>
        <SelectField label="Duration (Months)" name="duration">
          {options.duration.map((opt, idx) => (
            <option key={idx} value={opt}>
              {opt}
            </option>
          ))}
        </SelectField>
        {this.state.selectedSource === 'bank' && (
          <SelectField label="Methods" name="methods">
            {options.methods.map((opt, idx) => (
              <option key={idx} value={opt}>
                {opt}
              </option>
            ))}
          </SelectField>
        )}
        <br />
        <Field label="Interest Rate" placeholder="1250" name="rate" />
        <SelectField label="Subvention" name="subvention">
          {options.subvention.map((opt, idx) => (
            <option key={idx} value={opt}>
              {opt}
            </option>
          ))}
        </SelectField>
        <Field
          label="Merchant Payback"
          placeholder="1250"
          type="number"
          name="merchant_payback"
        />
        <br />
        <Field label="Issuer Plan ID" name="issuer_plan_id" />
        <Field
          label="Min Amount"
          placeholder="1250"
          type="number"
          name="min_amount"
        />
        <br />
        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={body => {
            return adminPost({
              url: 'live/emi',
              data: body,
            }).then(response => {
              if (response) {
                notifySuccess('EMI Plan added successfully');
                closeModal();
              }
            });
          }}
        />
      </Form>
    );
  }
}
