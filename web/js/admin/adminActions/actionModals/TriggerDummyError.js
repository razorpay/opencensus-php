import React, { Component } from 'react';
import AsyncButton from 'ui/AsyncButton';
import { notifySuccess, closeModal } from 'common/modal';

import { adminFetch } from 'common/fetch';

TriggerDummyError.permission = 'trigger_dummy_error';
TriggerDummyError.title = 'Trigger Dummy Error';
export default function TriggerDummyError() {
  return (
    <div>
      <div class="field">Are you sure you want to trigger an error?</div>
      <br />
      <AsyncButton
        text="Trigger"
        class="btn"
        pendingClass="small spinner"
        onSubmit={() => {
          return adminFetch('dummy_critical_error').then(response => {
            if (response) {
              notifySuccess('Error triggerred successfully');
              closeModal();
            }
          });
        }}
      />
    </div>
  );
}
