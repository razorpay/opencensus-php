import React, { Component, Fragment } from 'react';

import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { CheckField, TextAreaField } from 'ui/Field';
import { snakeToTitleCase } from 'common/util';
import AsyncButton from 'ui/AsyncButton';

export default ({ title, issues, onIssuesSubmition }) => (
  <div class="review-notes entity-container">
    <header class="m-b">Issues:</header>
    <Form class="full-span full-elements limited">
      <Table
        animateRow={false}
        fields={fields}
        items={issues}
        customClass="table-bordered"
      />
      <TextAreaField
        label="Public Comment"
        name="issue_fields_reason"
        placeholder="Please give a brief explanation for the reasons."
      />
      <TextAreaField
        label="Internal notes"
        name="internal_notes"
        placeholder="Add an internal notes here."
      />
      <AsyncButton
        text="Save"
        class="btn"
        pendingClass="small spinner"
        onSubmit={onIssuesSubmition}
      />
    </Form>
  </div>
);

const fields = [
  ['Issue', issue => snakeToTitleCase(issue)],
  [
    'Action',
    issue => (
      <Fragment>
        <div
          class="link"
          onClick={() => this.props.onIssueSelection(null, issue)}
        >
          Resolve
        </div>
      </Fragment>
    ),
  ],
];
