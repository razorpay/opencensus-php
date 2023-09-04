import React from 'react';
import { Link } from 'react-router-dom';

import ShowWhen from 'merchant/components/ShowWhen';
import Input from 'common/new-ui/Input';

import { showDynamicFields, showPayerNamePL } from 'merchant/views/PaymentLinks/utils';
import {
  CUSTOM_FIELDS,
  TYPE_MAP,
} from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/constants';
import { DYNAMIC_FIELDS_PL } from 'merchant/views/Settings/Configuration/deeplink-constants';

import PayerName from './PayerName';

function DynamicFields({ payerName = '', dynamicFields = [], disabled }) {
  const getDynamicFields = () => {
    return dynamicFields.map(({ configuration }, index) => {
      const { label, is_mandatory, type } = configuration || {};

      return (
        <Input
          id={`${CUSTOM_FIELDS}_${index}`}
          key={label}
          name={label}
          label={label}
          className="Input--vTop"
          placeholder={label}
          labelClass="Input-label pb-8"
          required={is_mandatory}
          type={TYPE_MAP[type] || type}
          disabled={disabled}
        />
      );
    });
  };

  if (showDynamicFields()) {
    return dynamicFields.length > 0 ? getDynamicFields() : <Placeholder />;
  } else if (showPayerNamePL()) {
    return <PayerName defaultValue={payerName} required={true} disabled={disabled} />;
  }

  return null;
}

function Placeholder() {
  return (
    <div className="Input Input--vTop">
      <div className="Input-label">Dynamic Field</div>
      <div className="Input-content">
        Dynamic Field is not set to this payment link.
        <ShowWhen additionalCondition={(user) => !user.isAccountAndSettingsRevampEnabled}>
          <br />
          Set it up{' '}
          <Link target="_blank" to={`/config#${DYNAMIC_FIELDS_PL}`} rel="noreferrer noopener">
            here
          </Link>
          .
        </ShowWhen>
      </div>
    </div>
  );
}

export default DynamicFields;
