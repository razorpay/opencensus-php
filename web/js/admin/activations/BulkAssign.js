import React, { Component } from 'react';

import Form from 'ui/Form';
import BaseModal from 'ui/BaseModal';
import AsyncButton from 'ui/AsyncButton';
import Field, { SearchableSelectField } from 'ui/Field';
import { openModal } from 'common/modal';

import { adminPost } from 'common/fetch';
import { notifySuccess, notifyError, closeModal } from 'common/modal';

export default class BulkAssign extends Component {
  render() {
    const { reviewers, selectedMerchants, onReviewerAssignment } = this.props;
    const totalForms = selectedMerchants.length;

    return (
      <BaseModal header="Assign Reviewers">
        <Form>
          <p>
            <strong>
              {totalForms} form{totalForms > 1 ? 's' : ''} selected.
            </strong>
          </p>
          <SearchableSelectField
            label="Reviewer"
            name="reviewer_id"
            options={reviewers}
            trackBy="id"
          />
          <AsyncButton
            text="Assign"
            class="btn pull-right"
            pendingClass="pull-right small spinner"
            onSubmit={body => {
              //TODO: add admin_
              body.reviewer_id = `admin_${body.reviewer_id}`;

              body.merchants = selectedMerchants;
              return onReviewerAssignment(body);
            }}
          />
        </Form>
      </BaseModal>
    );
  }
}
