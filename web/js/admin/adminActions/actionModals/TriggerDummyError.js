import React, { Component } from 'react';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminFetch } from 'util/fetch';

TriggerDummyError.title = 'Trigger Dummy Error';
export default function TriggerDummyError() {
  return (
    <div>
      <header>{TriggerDummyError.title}</header>
      <div className="field">
        <p>Are you sure you want to trigger an error?</p>
      </div>
      <AsyncButton
        text="Trigger"
        class="btn"
        pendingClass="small spinner"
        onSubmit={() => {
          return adminFetch('dummy_critical_error')
            .then(response => {
              if (response.data.success) {
                notifySuccess('Error triggerred successfully');
                closeModal();
              } else {
                notifyError('Error not triggerred successfully');
              }
            })
            .catch(err => {
              notifyError(JSON.stringify(err.response));
            });
        }}
      />
    </div>
  );
}
