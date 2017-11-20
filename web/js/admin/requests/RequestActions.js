import React, { Component } from 'react';
import Form from 'ui/Form';
import Field from 'ui/Field';
import { adminPost, adminPut } from 'util/fetch';
import { confirm, closeModal, notifyError } from 'common/modal';
import { notifyDone } from '../../common/modal';

export default class RequestActions extends Component {
  //route config based on request actions i.e {<action>: [<action_route_name>, <action_http_func>]}
  actionRoutes = {
    approve: ['action_checker_create', adminPost],
    reject: ['action_checker_create', adminPost],
    close: ['workflow_action_close', adminPut],
    execute: ['action_request_execute', adminPost],
  };

  submitAction = action => {
    const { id, onUpdateAction } = this.props,
      routes = this.actionRoutes,
      requestFn = routes[action][1]; //default http func based on routes

    const params = {
      route_name: routes[action][0],
      url_params: { id },
    };

    //Add approved flag in body
    switch (action) {
      case 'approve':
      case 'reject':
        params.body = { approved: action === 'approve' ? 1 : 0 };
        break;
      default:
        break;
    }

    requestFn({
      ...params,
    }).then(response => {
      if (response) {
        onUpdateAction(response);
        notifyDone();
      }
    });
  };

  confirmAction = action => {
    const message = `Are you sure you want to ${action} this request?`;

    confirm(message, 'OK', 'Cancel').then(() => {
      this.submitAction(action);
    });
  };

  render() {
    const { requestState } = this.props;
    const checkers = this.props.checkers.peek();

    return (
      <aside class="requests-actions">
        {requestState === 'open' || requestState === 'approved' ? (
          <div>
            <div class="header">
              <b>ACTIONS</b>
            </div>
            {requestState !== 'approved' ? (
              <div
                class="btn btn-default"
                onClick={_ => this.confirmAction('approve')}
              >
                <i class="i i-yes label-success" /> Approve
              </div>
            ) : null}
            {requestState !== 'approved' ? (
              <div
                class="btn btn-default"
                onClick={_ => this.confirmAction('reject')}
              >
                <i class="i i-no label-danger" /> Reject
              </div>
            ) : null}
            <div
              class="btn btn-default"
              onClick={_ => this.confirmAction('close')}
            >
              <i class="i i-no label-pending" /> Close
            </div>
            {requestState === 'approved' ? (
              <div
                class="btn btn-default"
                onClick={_ => this.confirmAction('execute')}
              >
                <i class="i i-upload label-success" /> Execute
              </div>
            ) : null}
          </div>
        ) : null}
        {checkers.length ? <RequestActionCheckers checkers={checkers} /> : null}
      </aside>
    );
  }
}

/*
* Renders list of users(checkers) based on who have 'approved' or 'rejected' workflow requests
*/
const RequestActionCheckers = ({ checkers }) => {
  let approvalMap = { approved: [], rejected: [] };

  //Create map of {approved: [users], rejected: [users]}
  checkers.forEach(checker => {
    const status = checker.approved ? 'approved' : 'rejected';
    approvalMap[status].push(checker.admin.name);
  });

  return (
    <div class="request-checkers-list">
      {approvalMap['approved'].length ? (
        <div>
          <label class="checker-label">
            <b>Approved By:</b>
          </label>
          {approvalMap['approved'].map((name, idx) => (
            <span class="checker" key={idx}>
              {name}
            </span>
          ))}
        </div>
      ) : null}
      {approvalMap['rejected'].length ? (
        <div>
          <label class="checker-label">
            <b>Rejected By:</b>
          </label>
          {approvalMap['rejected'].map((name, idx) => (
            <span class="checker" key={idx}>
              {name}
            </span>
          ))}
        </div>
      ) : null}
    </div>
  );
};
