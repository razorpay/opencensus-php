import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, TextAreaField, CheckField } from 'ui/Field';
import { splitAndFilter } from 'common/util';
import { adminPut, adminPatch } from 'common/fetch';
import { ModalContent } from 'component/Modal';
import {
  openModal,
  closeModal,
  notifyError,
  notifySuccess,
} from 'common/modal';

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

const statusValues = {
  1: 'Enable',
  0: 'Disable',
};

BulkEditIIN.title = 'Bulk Edit IIN';
export default function BulkEditIIN() {
  function handleSubmit(data) {
    const request = { iins: null, payload: {} };
    const { iins, ...rest } = data;

    request.iins = splitAndFilter(iins, ',');
    request.payload = rest;

    adminPatch({
      url: `live/iins/bulk`,
      data: request,
    }).then(response => {
      if (response) {
        if (response.success == 0) {
          notifyError(`Failed to update`);
        } else {
          notifySuccess('Update Successful');
          closeModal();
        }
        openModal(
          <ModalContent header="API Response">
            <div class="code" style={{ width: '650px' }}>
              {JSON.stringify(response, null, 4)}}
            </div>
          </ModalContent>
        );
      }
    });
  }

  return (
    <Form class="full-span" onSubmit={handleSubmit}>
      <TextAreaField
        label="IINs(6 Digit)"
        type="text"
        name="iins"
        required
        placeholder="Enter comma separated IINs"
      />
      <SelectField label="Network" name="network">
        <option value={''} />
        {Object.keys(network).map((item, idx) => {
          return (
            <option value={item} key={idx}>
              {network[item]}
            </option>
          );
        })}
      </SelectField>
      <SelectField label="Type" name="type">
        <option value={''} />
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

      <SelectField label="EMI Available" name="emi">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="Enabled" name="enabled">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="Locked" name="locked">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="3Ds" name="flows[3ds]">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="Pin" name="flows[pin]">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="OTP" name="flows[otp]">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="Iframe" name="flows[iframe]">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="Magic" name="flows[magic]">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="Headless OTP" name="flows[headless_otp]">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <SelectField label="Recurring" name="recurring">
        <option value="" />
        {Object.keys(statusValues).map((val, idx) => {
          return (
            <option value={val} key={idx}>
              {statusValues[val]}
            </option>
          );
        })}
      </SelectField>

      <button class="btn" type="submit">
        Submit
      </button>
    </Form>
  );
}
