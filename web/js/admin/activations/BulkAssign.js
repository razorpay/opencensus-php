import React, { Component } from 'react';

import Form from 'ui/Form';
import { ModalContent } from 'component/Modal';
import AsyncButton from 'ui/AsyncButton';
import { SearchableSelectField } from 'ui/Field';

import { notifyError } from 'common/modal';

export default class BulkAssign extends Component {
  render() {
    const { reviewers, selectedMerchants, onReviewerAssignment } = this.props;
    const totalForms = selectedMerchants.length;

    return (
      <ModalContent header="Assign Reviewers">
        <Form class="full-span full-elements">
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
              if (body.reviewer_id) {
                body.merchants = selectedMerchants;
                return onReviewerAssignment(body);
              } else {
                notifyError('Please select a reviewer to proceed.');
              }
            }}
          />
        </Form>
      </ModalContent>
    );
  }
}
