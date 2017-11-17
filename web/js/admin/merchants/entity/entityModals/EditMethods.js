import React from 'react';
import BaseModal from 'ui/BaseModal';
import { withRouter } from 'react-router-dom';
import Form from 'ui/Form';
import { SwitchField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'util/fetch';
import { isWorkflow } from 'util/index';

export default withRouter(({ props, history }) => {
  let defaultMethods;

  /* Send only changed methods */
  function filterChangedMethods(methods) {
    for (var method in methods) {
      if (methods[method] === defaultMethods[method].toString()) {
        delete methods[method];
      }
    }

    return methods;
  }

  /* UI fields for methods */
  function getFormFields() {
    const fields = [];
    const methods = {};

    _getForceFields().map(method => {
      // Assign a default of false and override if we have it
      methods[method] = '0';

      if (methods.hasOwnProperty(method)) {
        methods[method] = props.merchant.details.methods[method] ? '1' : '0';
      }
    });

    defaultMethods = { ...methods };

    for (let method in methods) {
      fields.push(
        <label key={method}>
          {method}
          <SwitchField name={method} defaultValue={methods[method]} />
        </label>
      );
    }

    return fields;
  }

  /* Submit button action */
  function onSubmit(body) {
    body = filterChangedMethods(body);

    for (let method in body) {
      if (body[method] === '1') {
        body[method] = 1;
      } else if (body[method] === '0') {
        body[method] = 0;
      }
    }

    return adminPut({
      route_name: 'merchant_put_payment_methods',
      url_params: {
        mid: props.merchant.details.id,
      },
      body,
    })
      .then(data => {
        if (data) {
          if (isWorkflow(data, history)) {
            return;
          }
          notifySuccess('Methods updated successfully.');
          props.updateDetails({ ...props.merchant.details, methods: data });
          closeModal();
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
});

function _getForceFields() {
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
