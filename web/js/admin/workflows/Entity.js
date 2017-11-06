import React, { Component } from 'react';
import { openModal } from 'common/modal';
import { observer } from 'mobx-react';
import Field from 'ui/Field';

export function showEntity() {
  return <EditWorkflow model={this} />;
}

@observer
export default class EditWorkflow extends Component {
  render() {
    let { model } = this.props;

    return (
      <div>
        <header>{model ? 'Edit' : 'Create'} Workflow</header>
        <Field label="name" name="name" />
      </div>
    );
  }
}
