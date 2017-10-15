import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { openModal } from 'common/modal';

export default class GatewayRuleEntity extends Component {
  render() {
    return (
      <div>
        <header>Create new Gateway Rule</header>
        <Form>
          <SelectField />
        </Form>
      </div>
    );
  }
}

export function showEntity() {
  return openModal(<GatewayRuleEntity />);
}
