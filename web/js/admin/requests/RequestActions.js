import React, { Component } from 'react';
import { adminPost, adminPut } from 'common/fetch';
import { confirm, closeModal, notifyError } from 'common/modal';
import { notifyDone } from '../../common/modal';
import AsyncButton from 'ui/AsyncButton';

export default class RequestActions extends Component {
  //route config based on request actions i.e {<action>: [<action_route_name>, <action_http_func>]}
  actionRoutes = {
    approve: ['action_checker_create', adminPost],
    reject: ['action_checker_create', adminPost],
    close: ['workflow_action_close', adminPut],
    execute: ['action_request_execute', adminPost],
  };

  handleSubmit = action => {
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

    return requestFn({
      ...params,
    }).then(response => {
      if (response) {
        onUpdateAction(response);
        notifyDone();
      }
    });
  };

  render() {
    const { requestState } = this.props;
    const checkers = this.props.checkers.peek();

    return (
      <aside class="requests-actions">
        {requestState === 'open' || requestState === 'approved' ? (
          <div class="container">
            <div class="header">
              <b>ACTIONS</b>
            </div>
            {requestState !== 'approved' ? (
              <AsyncButton
                class="btn btn-default"
                pendingClass="btn btn-default btn-pending"
                confirm="Are you sure you want to approve this request?"
                onClick={_ => this.handleSubmit('approve')}
              >
                <i class="i i-yes label-success" /> Approve
                <span class="spin-btn" />
              </AsyncButton>
            ) : null}
            {requestState !== 'approved' ? (
              <AsyncButton
                class="btn btn-default"
                pendingClass="btn btn-default btn-pending"
                confirm="Are you sure you want to reject this request?"
                onClick={_ => this.handleSubmit('reject')}
              >
                <i class="i i-no label-danger" /> Reject
                <span class="spin-btn" />
              </AsyncButton>
            ) : null}
            <AsyncButton
              class="btn btn-default"
              pendingClass="btn btn-default btn-pending"
              confirm="Are you sure you want to close this request?"
              onClick={_ => this.handleSubmit('close')}
            >
              <i class="i i-no label-pending" /> Close
              <span class="spin-btn" />
            </AsyncButton>
            {requestState === 'approved' ? (
              <AsyncButton
                class="btn btn-default"
                pendingClass="btn btn-default btn-pending"
                confirm="Are you sure you want to execute this request?"
                onClick={_ => this.handleSubmit('execute')}
              >
                <i class="i i-upload label-success" /> Execute
                <span class="spin-btn" />
              </AsyncButton>
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
        <div class="m-b">
          <div class="header">
            <b>Approved By:</b>
          </div>
          {approvalMap['approved'].map((name, idx) => (
            <span class="pill" key={idx}>
              {name}
            </span>
          ))}
        </div>
      ) : null}
      {approvalMap['rejected'].length ? (
        <div class="separate m-t">
          <div class="header">
            <b>Rejected By:</b>
          </div>
          {approvalMap['rejected'].map((name, idx) => (
            <span class="pill" key={idx}>
              {name}
            </span>
          ))}
        </div>
      ) : null}
    </div>
  );
};
