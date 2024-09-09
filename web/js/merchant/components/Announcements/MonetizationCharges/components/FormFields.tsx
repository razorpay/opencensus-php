import React from 'react';
import { FORM_FIELDS } from '../constants/fields';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  TextInput,
} from '@razorpay/blade/components';
import { COMMON_Z_INDEX } from 'common/constant';

const FormFields = ({ errors, handleChange, values }) => {
  const { isDesktop } = useBladeBreakpoints();

  return (
    <>
      {FORM_FIELDS.map((field, idx) => {
        if (field.type === 'select') {
          return (
            <Dropdown key={idx}>
              <SelectInput
                errorText={errors[field.name]}
                helpText={field.helperText}
                isRequired={field.isRequired}
                validationState={Boolean(errors[field.name]) ? 'error' : 'none'}
                value={values[field.name]}
                placeholder={field.placeholder}
                label={field.label}
                name={field.name}
                labelPosition={isDesktop ? 'left' : 'top'}
                onChange={(e) => handleChange(e, 'select')}
              />
              <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
                <ActionList>
                  {field.elements!.map((element, idx) => (
                    <ActionListItem key={idx} title={element.label} value={element.value} />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          );
        } else if (field.type === 'phone-IN') {
          return (
            <TextInput
              key={idx}
              isRequired={field.isRequired}
              validationState={Boolean(errors[field.name]) ? 'error' : 'none'}
              errorText={errors[field.name]}
              value={values[field.name]}
              label={field.label}
              name={field.name}
              labelPosition={isDesktop ? 'left' : 'top'}
              helpText={field.helperText}
              placeholder={field.placeholder}
              size="medium"
              onChange={handleChange}
            />
          );
        } else {
          return (
            <TextInput
              key={idx}
              isRequired={field.isRequired}
              validationState={Boolean(errors[field.name]) ? 'error' : 'none'}
              errorText={errors[field.name]}
              value={values[field.name]}
              label={field.label}
              name={field.name}
              labelPosition={isDesktop ? 'left' : 'top'}
              helpText={field.helperText}
              placeholder={field.placeholder}
              size="medium"
              onChange={handleChange}
            />
          );
        }
      })}
    </>
  );
};

export default FormFields;
