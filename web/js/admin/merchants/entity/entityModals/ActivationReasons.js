import React, { Component } from 'react';
import { observer } from 'mobx-react';

import BaseModal from 'ui/BaseModal';
import fetch, { adminFetch } from 'util/fetch';
import { SelectField, SwitchField } from 'ui/Field';
import Form from 'ui/Form';
import Table from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';
import { notifyError } from 'common/modal';

//Modal when activation status is set to "Rejected"
@observer
export class RejectActivation extends Component {
  state = {
    selectedCategory: '',
    selectedCode: '',
    selectedReasons: [],
    pending: true,
  };

  componentWillMount() {
    this.selectedStatus = this.props.status;
    adminFetch({
      route_name: 'merchant_get_rejection_reasons',
    }).then(response => {
      if (response) {
        this.allReasons = response;
        this.setState({
          pending: false,
          //Pre select default values
          selectedCategory: Object.keys(response)[0],
          selectedCode: response[Object.keys(response)[0]][0]['code'],
        });
      }
    });
  }

  save = () => {
    if (this.state.selectedReasons.length <= 0) {
      return notifyError('Please add a reason.');
    }

    return this.props.fetchFn({
      activation_status: this.props.status,
      rejection_reasons: this.state.selectedReasons.map(r => ({
        reason_code: r.reason_code,
        reason_category: r.reason_category,
      })),
    });
  };

  handleCategoryChange = e => {
    this.setState({
      selectedCategory: e.target.value,
      selectedCode: this.allReasons[e.target.value][0]['code'],
    });
  };

  handleCodeChange = e => {
    this.setState({ selectedCode: e.target.value });
  };

  handleReasonAdd = () => {
    const { selectedCode, selectedCategory } = this.state;
    let selectedReasons = [...this.state.selectedReasons];

    const idx = selectedReasons.findIndex(
      r => r.reason_code === this.state.selectedCode
    );

    if (idx < 0) {
      selectedReasons.unshift({
        reason_code: selectedCode,
        reason_category: selectedCategory,
        desc: this.allReasons[selectedCategory].find(
          r => r.code === selectedCode
        ).description,
      });

      this.setState({ selectedReasons });
    }
  };

  handleReasonDelete = code => {
    let selectedReasons = [...this.state.selectedReasons];
    selectedReasons = selectedReasons.filter(r => r.reason_code !== code);
    this.setState({ selectedReasons });
  };

  fields = () => {
    return [
      ['Category', item => categoryMap[item.reason_category]],
      ['Reason', item => item.desc],
      [
        '',
        item => (
          <div
            class="link danger"
            onClick={() => this.handleReasonDelete(item.reason_code)}
          >
            Delete
          </div>
        ),
      ],
    ];
  };

  render() {
    const {
      selectedCategory,
      selectedReasons,
      selectedCode,
      pending,
    } = this.state;

    if (pending) {
      return <div class="spinner" />;
    }

    return (
      <BaseModal header="Change status to: Rejected">
        <Form class="full-span full-elements">
          <SelectField
            label="Select Category:"
            name="category"
            value={selectedCategory}
            onChange={this.handleCategoryChange}
          >
            {Object.keys(categoryMap).map((key, idx) => (
              <option value={key} key={idx}>
                {categoryMap[key]}
              </option>
            ))}
          </SelectField>
          <SelectField
            label="Select Code:"
            name="code"
            value={selectedCode}
            onChange={this.handleCodeChange}
          >
            {this.allReasons[selectedCategory].map(reason => (
              <option value={reason.code} key={reason.code}>
                {reason.description}
              </option>
            ))}
          </SelectField>
          <div class="btn" onClick={this.handleReasonAdd}>
            + Add Reason
          </div>
          <AsyncButton
            onSubmit={this.save}
            text="Save"
            class="btn"
            pendingClass="small spinner"
          />
          {selectedReasons.length > 0 && (
            <Table
              animateRow={false}
              items={selectedReasons}
              fields={this.fields()}
            />
          )}
        </Form>
      </BaseModal>
    );
  }
}

//Modal when activation state is set to "needs_clarification"
export const NeedClarificationActivation = observer(({ fetchFn }) => (
  <BaseModal header="Change Status to: Needs Clarification">
    <Form>
      <input
        type="hidden"
        name="activation_status"
        value="needs_clarification"
      />
      <SwitchField
        name="clarification_mode"
        nocaption
        disabledLabel="Through Email"
        enabledLabel="Through Call"
        disabledValue="email"
        enabledValue="call"
      />
      <br />
      <AsyncButton
        onSubmit={fetchFn}
        text="Save"
        class="btn"
        pendingClass="small spinner"
      />
    </Form>
  </BaseModal>
));

const categoryMap = {
  others: 'Others',
  risky_business: 'Risky Business',
  unsupported_business_model: 'Unsupported Business Model',
};
