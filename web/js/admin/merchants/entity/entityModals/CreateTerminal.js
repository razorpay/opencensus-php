import React, { Component } from 'react';
import { ModalContent } from 'component/Modal';
import { getDetailsViewMap } from '../entity-resources';

import { isOrgHDFC } from 'admin/user';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SelectField, FileField, CheckField } from 'ui/Field';
import { adminFetch, adminPost, adminFormUpload2 } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import MultiSelectField from 'ui/MultiSelectField';

// TODO: import currency data in a better way
import CurrencyData from './currency.json';

export default class TerminalForm extends Component {
  getMcc = () => {
    const detailsMap = getDetailsViewMap(this.props.props);
    console.log(detailsMap);
    let mcc = detailsMap.find(o => o.label === 'MCC');
    // let methods = detailsMap.find(o=>o.label==="Methods");
    // console.log(methods.children());
    console.log(mcc);
    return mcc.value;
  };
  // Creates terminal
  handleCreate = body => {
    // var data, gateway_input,terminal;
    console.log(body);
    const {
      pg_merchant_id,
      gateway,
      mid,
      tid,
      mcc,
      currency_code,
      trans_mode,
      mode,
    } = body;
    var data = {
      gateway: gateway,
      gateway_input: {
        mid: mid,
        tid: tid,
        mcc: this.getMcc(),
        currency_code: currency_code,
        trans_mode: trans_mode,
      },
      terminal: {
        pg_merchant_id: pg_merchant_id,
      },
    };
    return adminPost({
      url: `${mode}/merchants/${pg_merchant_id}/terminals/onboard`,
      data: data,
    })
      .then(response => {
        console.log('response', response);
        if (response.data.success) {
          notifySuccess('Terminal created successfully.');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    console.log('here');
    console.log(this.props);
    console.log(this.props.props);
    console.log(this.props.props.Model);
    console.log(this.props.props.model);
    // const detailsMap = getDetailsViewMap(this.props.props);
    // console.log(detailsMap);
    // let mcc = detailsMap.find(o=>o.label==="MCC");
    // console.log(mcc);
    const { merchantId } = this.props;
    return (
      <ModalContent header={'Create Terminal'}>
        <Form class="entity-container" style={{ width: '600px' }}>
          <Field
            label="Merchant id"
            name="pg_merchant_id"
            defaultValue={merchantId}
            disabled
          />
          <SelectField name="mode" label="Mode" defaultValue="live">
            <option value="test">Test</option>
            <option value="live">Live</option>
          </SelectField>
          <SelectField
            name="trans_mode"
            label="Transaction mode"
            defaultValue="CARDS"
          >
            <option value="CARDS">CARDS</option>
            <option value="UPI">UPI</option>
            <option value="BharatQR">BharatQR</option>
          </SelectField>
          <SelectField name="gateway" label="Gateway" defaultValue="hitachi">
            <option value="hitachi">Hitachi</option>
          </SelectField>
          <Field
            label="Terminal Category"
            name="category"
            defaultValue={this.getMcc()}
            disabled
          />
          <Field label="Gateway Merchant Id" name="mid" />
          <Field label="Gateway Terminal Id" name="tid" />
          <SelectField name="currency_code" label="Currency" defaultValue="INR">
            <option value="" />
            {CurrencyData.data.map(({ code }) => (
              <option value={code}>{code}</option>
            ))}
          </SelectField>
          <div class="m-t m-b" />
          <AsyncButton
            text="Cancel"
            class="btn btn-default"
            pendingClass="small spinner"
            onSubmit={closeModal}
          />
          <AsyncButton
            onSubmit={this.handleCreate}
            class="btn"
            pendingClass="small spinner"
            confirm={'Are you sure you want to create this terminal?'}
          >
            Ok
          </AsyncButton>
        </Form>
      </ModalContent>
    );
  }
}
