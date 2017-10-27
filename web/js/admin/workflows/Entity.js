import React, { Component } from 'react';
import { openModal } from 'common/modal';
import { observer } from 'mobx-react';
import Field from 'ui/Field';

export function showEntity() {
  open(`/admin/_#/app/workflows/${this.id}/edit`);
}

@observer
export default class EditWorkflow extends Component {
  render() {
    return (
      <div>
        <header>{(this.id && 'Edit') || 'Create'} Workflow</header>
        <Field label="name" name="name" />
      </div>
    );
  }
}
