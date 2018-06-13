import React, { Fragment } from 'react';
import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import { SwitchField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPut } from 'common/fetch';
import { isWorkflow } from 'common/util';

export default ({ props, merchantId }) => {
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

    Object.keys(props.merchant.details.methods)
      .sort()
      .forEach(method => {
        if (exclusionFields.indexOf(method) < 0) {
          methods[method] = props.merchant.details.methods[method] | 0;
        }
      });
    defaultMethods = { ...methods };

    for (let method in methods) {
      fields.push(
        <Fragment key={method}>
          <SwitchField
            name={method}
            disabledLabel={method}
            defaultValue={methods[method]}
            nocaption
          />
          <br />
        </Fragment>
      );
    }

    return fields;
  }

  /* Submit button action */
  function onSubmit(body) {
    body = filterChangedMethods(body);

    if (Object.keys(body).length === 0) {
      return notifyError('Please change a method or methods to proceed.');
    }

    for (let method in body) {
      if (body[method] === '1') {
        body[method] = 1;
      } else if (body[method] === '0') {
        body[method] = 0;
      }
    }

    return adminPut({
      url: `live/merchants/${merchantId}/methods`,
      data: body,
    })
      .then(data => {
        if (data) {
          closeModal();

          if (isWorkflow(data)) {
            notifySuccess('Workflow is created successfully.');
            return;
          }
          notifySuccess('Methods updated successfully.');

          props.updateDetails({ ...props.merchant.details, methods: data });
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <ModalContent header="Activate/Deactivate Merchant Payment Methods">
      <Form class="">
        {getFormFields()}

        <br />
        <div class="separate" />
        <AsyncButton
          text="OK"
          class="btn pull-right"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </ModalContent>
  );
};

//exclude fields from `methods` which are not required
const exclusionFields = [
  //extra fields
  'card',
  'disabled_banks',
  'entity',
  'merchant_id',
  //method fields
  'paytm',
];
