import { toTitleCase } from 'common/utils';
import React from 'react';

import { Field } from 'redux-form';

interface BaseFieldProps {
  name: string;
  label?: JSX.Element;
}

interface FieldProps {
  name?: string;
  label?: JSX.Element;
}

export const ReferenceIdField = ({ name, label }: FieldProps): JSX.Element => (
  <BaseField label={label} name={name || 'reference_id'} />
);

export const ContactField = ({ name, label }: FieldProps): JSX.Element => (
  <BaseField label={label} name={name || 'contact'} />
);

const BaseField = ({ name, label }: BaseFieldProps) => (
  <div className="form-group list-filter-item">
    {label || <label>{toTitleCase(name, '_')}</label>}
    <Field name={name} component="input" class="form-control input-sm" data-testid={name} />
  </div>
);
