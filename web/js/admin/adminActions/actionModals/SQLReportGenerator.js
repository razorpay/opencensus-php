import React from 'react';
import Form from 'ui/Form';
import Field, { TextAreaField, DateField } from 'ui/Field';

import { adminPost } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import { closeModal, notifyError, notifySuccess } from 'common/modal';

export default function SQLReportGenerator() {
  function onSubmit(body) {
    if (body.start_at_date && body.end_at_date) {
      let startDate = moment(
        `${body.start_at_date} ${body.start_at_time}`,
        'DD/MM/YYYY HH:mm'
      );
      let endDate = moment(
        `${body.end_at_date} ${body.end_at_time}`,
        'DD/MM/YYYY HH:mm'
      );
      let diff = endDate.diff(startDate, 'days');

      if (diff > 10) {
        notifyError(
          'Difference b/w start and end date should not be more than 10 days.'
        );
        return;
      }

      if (diff < 0) {
        notifyError('End date should be after the start date.');
        return;
      }

      let payload = {
        url: `live/admin-reporting/logs`,
        headers: {
          'X-Consumer': body.x_consumer,
          'X-Report-Type': body.x_report_type,
        },
        data: {
          config_id: body.config_id,
          emails: [],
          start_time: startDate.unix(),
          end_time: endDate.unix(),
          mode: 'live',
          generated_by: '100000Razorpay',
        },
      };

      try {
        payload.data.template_overrides = JSON.parse(body.template_overrides);
      } catch (e) {
        notifyError(e);
        return;
      }

      if (body.emails) {
        payload.data.emails = splitAndFilter(body.emails, ',');
      }

      adminPost(payload).then(response => {
        if (response) {
          notifySuccess('Report has been successfully initiated.');
          closeModal();
        }
      });
    } else {
      notifyError('Start and end dates are mandatory.');
    }
  }

  return (
    <Form class="full-span sql-report-generator-form" onSubmit={onSubmit}>
      <Field required label="Config ID" type="text" name="config_id" />
      <Field required label="X-Consumer" type="text" name="x_consumer" />
      <Field required label="X-Report-Type" type="text" name="x_report_type" />
      <TextAreaField
        label="Email Ids"
        type="text"
        name="emails"
        required
        placeholder="Enter comma separated email ids"
      />
      <TextAreaField
        label="Body"
        type="text"
        name="template_overrides"
        required
        placeholder="Enter valid JSON payload"
      />
      <DateField
        required
        name="start_at_date"
        label="Starts at"
        component={<input type="time" name="start_at_time" />}
        allowAllDates={true}
      />
      <DateField
        required
        name="end_at_date"
        label="Ends at"
        component={<input type="time" name="end_at_time" />}
        allowAllDates={true}
      />
      <div class="form-actions text-right">
        <button class="btn" type="submit">
          Submit
        </button>
      </div>
    </Form>
  );
}

SQLReportGenerator.title = 'Raw SQL Report Generation';
SQLReportGenerator.permission = 'download_non_merchant_report';
