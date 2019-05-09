import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, TextAreaField, CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { adminPut, adminPatch } from 'common/fetch';
import { ModalContent } from 'component/Modal';
import { openModal } from 'common/modal';

const network = {
  Unknown: 'Unknown',
  'American Express': 'American Express',
  'Diners Club': 'Diners Club',
  Discover: 'Discover',
  JCB: 'JCB',
  Maestro: 'Maestro',
  MasterCard: 'MasterCard',
  RuPay: 'RuPay',
  Visa: 'Visa',
  'Union Pay': 'Union Pay',
};

const type = {
  debit: 'Debit',
  credit: 'Credit',
  unkown: 'Unknown',
};

BulkEditIIN.title = 'Bulk Edit IIN';
export default function BulkEditIIN() {
  function handleSubmit(data) {
    const request = { iins: null, payload: {} };
    const { iins, ...rest } = data;

    request.iins = iins
      .split(',')
      .map(item => item.trim())
      .filter(item => item.length === 6);

    Object.keys(rest).map(key => {
      if (rest[key] == 0) delete rest[key];
    });

    const { flows } = rest;

    Object.keys(flows).map(key => {
      if (flows[key] == 0) delete flows[key];
    });

    Object.keys(flows).length > 0 ? (rest.flows = flows) : delete rest.flows;

    request.payload = rest;
    openModal(
      <ModalContent header="Your Request">
        <div class="code" style={{ width: '650px' }}>
          {JSON.stringify(request, null, 4)}
        </div>
      </ModalContent>
    );
    return adminPatch({
      url: `live/iins/bulk`,
      data: request,
    });
  }

  return (
    <Form class="full-span">
      <TextAreaField
        label="IINs(6 Digit)"
        type="text"
        name="iins"
        required
        placeholder="Enter comma separated IINs"
      />
      <SelectField label="Network" name="network">
        <option value={null}>{null}</option>
        {Object.keys(network).map((item, idx) => {
          return (
            <option value={item} key={idx}>
              {network[item]}
            </option>
          );
        })}
      </SelectField>
      <SelectField label="Type" name="type">
        <option value={null}>{null}</option>
        {Object.keys(type).map((item, idx) => {
          return (
            <option value={item} key={idx}>
              {type[item]}
            </option>
          );
        })}
      </SelectField>
      <Field label="Country (2 characters)" name="country" length="2" />
      <Field label="Category" name="category" />
      <Field label="Issuer" name="issuer" />
      <Field label="Issuer Name" name="issuerName" />
      <Field label="Trivia" name="trivia" />
      <CheckField label="EMI Available" name="emi" defaultChecked={0} />
      <CheckField label="Enabled" name="enabled" defaultChecked={0} />

      <CheckField label="Locked" name="locked" defaultChecked={0} />

      <CheckField label="3Ds" name="flows[3ds]" defaultChecked={0} />

      <CheckField label="Pin" name="flows[pin]" defaultChecked={0} />

      <CheckField label="OTP" name="flows[otp]" defaultChecked={0} />

      <CheckField label="Iframe" name="flows[iframe]" defaultChecked={0} />

      <CheckField label="Magic" name="flows[magic]" defaultChecked={0} />

      <CheckField
        label="Headless OTP"
        name="flows[headless_otp]"
        defaultChecked={0}
      />

      <CheckField label="Recurring" name="recurring" defaultChecked={0} />
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={handleSubmit}
      />
    </Form>
  );
}
