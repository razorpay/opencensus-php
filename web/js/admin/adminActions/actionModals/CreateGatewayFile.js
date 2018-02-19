import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, {
  SelectField,
  DateField,
  TextAreaField,
  CheckField,
  SelectMode,
} from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';
import { snakeToTitleCase } from 'common/util';

import { adminPost } from 'common/fetch';

const typeToTargetMap = {
  '': ' ',
  refund: ['hdfc', 'icici'],
  emi: ['indusind', 'kotak', 'axis', 'rbl', 'scbl'],
  combined: ['kotak', 'axis', 'rbl', 'indusind', 'federal'],
  emandate_register: ['hdfc'],
  emandate_debit: ['hdfc', 'axis'],
  // TODO: uncomment this when api branch supporting this gets merge
  // claim: [],
  refund_failed: [
    'upi_icici',
    'airtel_money',
    'icic_first_data',
    'axis_migs',
    'axis_cybersource',
    'hdfc_cybersource',
    'hdfc_fss',
  ],
};

const targetToSubTypeMap = {
  kotak: ['tpv', 'non_tpv'],
  axis: ['corporate', 'non_corporate'],
};

export default class CreateGatewayFile extends Component {
  static title = 'Create Gateway File';
  static permission = 'create_netbanking_refund';

  state = { type: '', target: '', dateIsRange: false };

  handleChangeIn = (field, prop = 'value') => event => {
    this.setState({
      [field]: event.currentTarget[prop],
    });
  };

  handleSubmit = ({ mode, ...body }) => {
    return adminPost({
      url: `${mode}/gateway/files`,
      data: {
        type: body.type,
        begin: Number(
          moment(body.begin, 'DD/MM/YYYY')
            .startOf('day')
            .format('X')
        ),
        end: Number(
          moment(
            Boolean(Number(body.dateIsRange)) ? body.end : body.begin,
            'DD/MM/YYYY'
          )
            .endOf('day')
            .format('X')
        ),
        targets: [body.target],
        sub_type: body.subType,
        recipients: body.recipients ? body.recipients.split(',') : [],
      },
    }).then(response => {
      if (response) {
        notifySuccess('Gateway File created successfully');
        closeModal();
      }
    });
  };

  render() {
    const {
      type: currentType,
      target: currentTarget,
      dateIsRange,
    } = this.state;
    return (
      <Form class="full-span full-elements">
        <SelectMode defaultValue="live" />

        <SelectField
          label="Type"
          value={currentType}
          name="type"
          onChange={this.handleChangeIn('type')}
          required
        >
          {Object.keys(typeToTargetMap).map(type => (
            <option key={type} value={type}>
              {snakeToTitleCase(type)}
            </option>
          ))}
        </SelectField>

        <SelectField
          label="Target"
          name="target"
          required={typeToTargetMap[currentType].length > 0}
          onChange={this.handleChangeIn('target')}
        >
          <option value=""> </option>
          {Array.isArray(typeToTargetMap[currentType]) &&
            typeToTargetMap[currentType].map(name => (
              <option key={name} value={name}>
                {snakeToTitleCase(name)}
              </option>
            ))}
        </SelectField>

        {currentType === 'combined' &&
          ['kotak', 'axis'].includes(currentTarget) && (
            <SelectField name="subType" label="Sub Type" required>
              <option value=""> </option>
              {Array.isArray(targetToSubTypeMap[currentTarget]) &&
                targetToSubTypeMap[currentTarget].map(name => (
                  <option key={name} value={name}>
                    {snakeToTitleCase(name)}
                  </option>
                ))}
            </SelectField>
          )}

        <CheckField
          name="dateIsRange"
          label="Dates are in range"
          value={dateIsRange}
          onChange={this.handleChangeIn('dateIsRange', 'checked')}
        />

        <DateField
          name="begin"
          fieldClass="create-gateway-file"
          label={!dateIsRange ? 'Date' : 'From'}
          required
        />

        {dateIsRange && (
          <DateField
            name="end"
            fieldClass="create-gateway-file"
            label={'To'}
            required
          />
        )}

        <Field
          label="Sender Email"
          type="text"
          name="sender"
          placeholder="someone@example.com"
        />

        <TextAreaField
          label="Recipients"
          name="recipients"
          placeholder="Please enter comma(,) seperated recipient emails"
        />

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={this.handleSubmit}
        />
      </Form>
    );
  }
}
