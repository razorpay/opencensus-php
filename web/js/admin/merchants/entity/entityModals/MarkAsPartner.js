import React from 'react';

import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import { SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { snakeToTitleCase } from 'common/util';

export default () => {
  const handleSubmit = () => {
    // TODO: add code for submission in mark as partner
  };

  return (
    <ModalContent header="Mark as Partner">
      <Form class="full-span full-elements" style={{ width: '350px' }}>
        <SelectField label="Partner Type" name="type" defaultValue="">
          {partnerTypes.map(type => (
            <option key={type} value={type}>
              {snakeToTitleCase(type)}
            </option>
          ))}
        </SelectField>
        <AsyncButton
          text="Submit"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleSubmit}
        />
      </Form>
    </ModalContent>
  );
};

var partnerTypes = [
  'bank',
  'reseller',
  'aggregator',
  'fully_managed',
  'pure_platform',
];
