import React from 'react';
import Field, { SelectField } from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

const options = {
  period: ['', 'Hourly', 'Daily', 'Weekly', 'Monthly-Date', 'Monthly-Week'],
};

CreateSchedule.title = 'Add Schedule';

export default function CreateSchedule() {
  return (
    <Form>
      <Field label="Name" placeholder="Weekly-2" name="name" />
      <SelectField label="Period" name="period">
        {options.period.map((opt, idx) => (
          <option
            value={opt.length ? opt[0].toLowerCase() + opt.substring(1) : ''}
            key={idx}
          >
            {opt}
          </option>
        ))}
      </SelectField>
      <br />
      <Field label="Interval" name="interval" />
      <Field label="Delay" name="delay" />
      <br />
      <Field label="Anchor" name="anchor" />
      <AsyncButton
        text="OK"
        class="btn"
        pendingClass="small spinner"
        onSubmit={body => {
          return adminPost({
            body,
            route_name: 'schedule_create',
          }).then(response => {
            if (response) {
              notifySuccess('Schedule added successfully');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
