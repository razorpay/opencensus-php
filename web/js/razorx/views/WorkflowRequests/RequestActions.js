import React, { Component } from 'react';
import { adminPost, adminPut } from 'razorx/helpers/admin-fetch';
import { confirm, closeModal, notifyError } from 'razorx/components/Modal';
import { notifyDone } from 'razorx/components/Modal';
import AsyncButton from 'razorx/components/ui/AsyncButton';

export default class RequestActions extends Component {
  //route config based on request actions i.e {<action>: [<action_url>, <action_http_func>]}
  actionRoutes = {
    approve: ['live/w-actions/{id}/checkers', adminPost],
    reject: ['live/w-actions/{id}/checkers', adminPost],
    close: ['live/w-actions/close/{id}', adminPut],
    execute: ['live/w-actions/{id}/execute', adminPost],
  };

  handleSubmit = action => {
    const { id, onUpdateAction } = this.props,
      routes = this.actionRoutes,
      requestFn = routes[action][1]; //default http func based on routes

    const params = {
      url: routes[action][0].replace('{id}', id),
    };

    //Add approved flag in body
    switch (action) {
      case 'approve':
      case 'reject':
        params.data = { approved: action === 'approve' ? 1 : 0 };
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
      <aside className="requests-actions">
        {requestState === 'open' || requestState === 'approved' ? (
          <div className="container">
            <div className="header">
              <b>ACTIONS</b>
            </div>
            {requestState !== 'approved' ? (
              <AsyncButton
                className="btn btn-default"
                pendingClass="btn btn-default btn-pending"
                confirm="Are you sure you want to approve this request?"
                onClick={_ => this.handleSubmit('approve')}
              >
                <i className="i i-yes label-success" /> Approve
                <span className="spin-btn" />
              </AsyncButton>
            ) : null}
            {requestState !== 'approved' ? (
              <AsyncButton
                className="btn btn-default"
                pendingClass="btn btn-default btn-pending"
                confirm="Are you sure you want to reject this request?"
                onClick={_ => this.handleSubmit('reject')}
              >
                <i className="i i-no label-danger" /> Reject
                <span className="spin-btn" />
              </AsyncButton>
            ) : null}
            <AsyncButton
              className="btn btn-default"
              pendingClass="btn btn-default btn-pending"
              confirm="Are you sure you want to close this request?"
              onClick={_ => this.handleSubmit('close')}
            >
              <i className="i i-no label-pending" /> Close
              <span className="spin-btn" />
            </AsyncButton>
            {requestState === 'approved' ? (
              <AsyncButton
                className="btn btn-default"
                pendingClass="btn btn-default btn-pending"
                confirm="Are you sure you want to execute this request?"
                onClick={_ => this.handleSubmit('execute')}
              >
                <i className="i i-upload label-success" /> Execute
                <span className="spin-btn" />
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
    <div className="request-checkers-list">
      {approvalMap['approved'].length ? (
        <div className="m-b">
          <div className="header">
            <b>Approved By:</b>
          </div>
          {approvalMap['approved'].map((name, idx) => (
            <span className="pill" key={idx}>
              {name}
            </span>
          ))}
        </div>
      ) : null}
      {approvalMap['rejected'].length ? (
        <div className="separate m-t">
          <div className="header">
            <b>Rejected By:</b>
          </div>
          {approvalMap['rejected'].map((name, idx) => (
            <span className="pill" key={idx}>
              {name}
            </span>
          ))}
        </div>
      ) : null}
    </div>
  );
};
