import React from 'react';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import { CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'util/fetch';

export default ({ props }) => {
  let defaultMethods;

  /* Send only changed methods */
  function filterChangedMethods(methods) {
    for (var method in methods) {
      if (methods[method] === defaultMethods[method]) {
        delete methods[method];
      }
    }

    return methods;
  }

  /* UI fields for methods */
  function getFormFields() {
    const fields = [];
    const methods = {};

    _getPossiblyMissingFields().map(method => {
      // Assign a default of false and override if we have it
      methods[method] = 0;

      if (methods.hasOwnProperty(method)) {
        methods[method] = props.details.methods[method] ? 1 : 0;
      }
    });

    defaultMethods = { ...methods };

    for (let method in methods) {
      fields.push(
        <CheckField
          label={method}
          name={method}
          key={method}
          defaultChecked={props.details.methods[method]}
        />
      );
    }

    return fields;
  }

  /* Submit button action */
  function onSubmit(body) {
    body = filterChangedMethods(body);

    for (let method in body) {
      body[method] = body[method] ? 1 : 0;
    }

    // TODO: adminPut, in this case, won't work if parseParams(params) are not sent as "data" like in adminPost
    return adminPut({
      route_name: 'merchant_put_payment_methods',
      url_params: {
        mid: props.details.id,
      },
      body,
    })
      .then(response => {
        if (response.data.success) {
          notifySuccess('Methods updated successfully.');
          closeModal();
        } else {
          response.data.errors.map(error => notifyError(error));
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Activate/Deactivate Merchant Payment Methods">
      <Form>
        {getFormFields()}

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </BaseModal>
  );
};

function _getPossiblyMissingFields() {
  // List all the methods here
  // This lets us display methods that are not returned by the API as false
  return [
    'aeps',
    'bank_transfer',
    'mobikwik',
    'payzapp',
    'payumoney',
    'olamoney',
    'mpesa',
    'upi',
    'airtelmoney',
    'freecharge',
    'emi',
    'amex',
    'netbanking',
    'debit_card',
    'credit_card',
    'jiomoney',
    'openwallet',
    'sbibuddy',
  ];
}
