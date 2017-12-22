import React, { Component } from 'react';
import { observer } from 'mobx-react';

import { snakeToTitleCase } from 'util/index';

import Form from 'ui/Form';
import { SelectField } from 'ui/Field';
import { adminPatch } from 'util/fetch';
import { isWorkflow } from 'util/index';
import {
  openModal,
  closeModal,
  confirm,
  notifySuccess,
  notifyError,
} from 'common/modal';

import {
  NeedClarificationActivation,
  RejectActivation,
} from '../entityModals/ActivationReasons';

export default class ActivationDetails extends Component {
  state = {
    status: this.props.status,
    allowedStatuses: this.props.allowedStatuses,
  };

  openActivationModal = body => {
    const prevStatus = this.state.status;

    //check whether status has changed or is undefined/null/empty
    if (!body.activation_status) {
      notifyError('Please change the status from the drop down menu.');
      return;
    }

    if (body.activation_status === 'rejected') {
      openModal(
        <RejectActivation
          status={body.activation_status}
          fetchFn={this.updateActivationStatus}
        />
      );
      return;
    }

    if (body.activation_status === 'needs_clarification') {
      openModal(
        <NeedClarificationActivation fetchFn={this.updateActivationStatus} />
      );
      return;
    }

    confirm(`Change Status to ${statusMap[body.activation_status]}?`).then(
      () => {
        this.updateActivationStatus({
          activation_status: body.activation_status,
        });
      }
    );
  };

  updateActivationStatus = body => {
    return adminPatch({
      route_name: 'merchant_activation_status',
      url_params: {
        id: this.props.merchantId,
      },
      body,
    }).then(response => {
      if (response) {
        if (isWorkflow(response)) {
          return;
        }
        this.setState(
          {
            status: response.activation_status,
            allowedStatuses: response.allowed_next_activation_statuses,
          },
          _ => {
            this.props.onStatusChange(response.activation_status);
          }
        );
        notifySuccess('Status updated successfully.');
        closeModal();
      }
    });
  };

  render() {
    return (
      <Form onSubmit={this.openActivationModal}>
        <SelectField name="activation_status" defaultValue={this.state.status}>
          <option value="">{snakeToTitleCase(this.state.status)}</option>
          {this.state.allowedStatuses.map(status => (
            <option key={status} value={status}>
              {snakeToTitleCase(status)}
            </option>
          ))}
        </SelectField>
        <button>Change</button>
      </Form>
    );
  }
}

export const statusMap = {
  under_review: 'Under Review',
  needs_clarification: 'Needs Clarification',
  activated: 'Activated',
  rejected: 'Rejected',
};
