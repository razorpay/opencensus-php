import React, { Component } from 'react';

import Form from 'ui/Form';
import { ModalContent } from 'component/Modal';
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
      <ModalContent header="Assign Reviewers">
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
              body.merchants = selectedMerchants;

              return onReviewerAssignment(body);
            }}
          />
        </Form>
      </ModalContent>
    );
  }
}
