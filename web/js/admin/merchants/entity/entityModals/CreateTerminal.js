import React, { Component } from 'react';
import { ModalContent } from 'component/Modal';
import { getDetailsViewMap } from '../entity-resources';

import { closeModal, confirm, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SelectField, SelectMode } from 'ui/Field';
import { adminFetch, adminPost, adminFormUpload2 } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';

export default class TerminalForm extends Component {
  getMcc = () => {
    const detailsMap = getDetailsViewMap(this.props.props);
    let mcc = this.search('MCC', detailsMap);
    return mcc.value;
  };

  search = (nameKey, array) => {
    for (var i = 0; i < array.length; i++) {
      if (array[i].label === nameKey) {
        return array[i];
      }
    }
  };
  // Creates terminal
  handleCreate = body => {
    confirm('Are you sure you want to create this terminal?').then(_ => {
      const { pg_merchant_id, gateway, currency_code, mode, category } = body;
      var data = {
        gateway: gateway,
        gateway_input: {
          mcc: category || this.getMcc(),
          currency_code: currency_code,
          trans_mode: gateway === 'hitachi' ? 'CARDS' : '', //todo else condition
        },
      };
      return adminPost({
        url: `${mode}/merchants/${pg_merchant_id}/terminals/onboard`,
        data: data,
      })
        .then(response => {
          if (response) {
            notifySuccess('Terminal created successfully.');
            closeModal();
          }
        })
        .catch(err => {
          console.log(err);
          notifyError(JSON.stringify(err.response));
        });
    });
  };

  render() {
    const { merchantId } = this.props;
    return (
      <ModalContent header={'Create Terminal'}>
        <Form class="entity-container" onSubmit={this.handleCreate}>
          <Field
            label="Merchant id"
            name="pg_merchant_id"
            defaultValue={merchantId}
            disabled
          />
          <SelectMode />
          <SelectField name="gateway" label="Gateway" defaultValue="hitachi">
            <option value="hitachi">Hitachi</option>
          </SelectField>
          <Field
            label="Terminal Category"
            name="category"
            defaultValue={this.getMcc()}
          />
          <SelectField name="currency_code" label="Currency" defaultValue="INR">
            <option value="" />
            {Object.keys(window.currencyLib.displayCurrencies).map(k => (
              <option key={k} value={k}>
                {k}
              </option>
            ))}
          </SelectField>
          <div class="m-t m-b" />

          <button
            type="button"
            className="btn btn-default"
            onClick={closeModal}
          >
            Cancel
          </button>
          <button type="submit" className="btn">
            ok
          </button>
        </Form>
      </ModalContent>
    );
  }
}
