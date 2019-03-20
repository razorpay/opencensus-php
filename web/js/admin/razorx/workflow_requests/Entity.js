import React, { Component } from 'react';
import { observable, extendObservable, action } from 'mobx';
import { observer } from 'mobx-react';
import { openModal, notifySuccess, notifyDone } from 'common/modal';
import { adminFetch, adminPut } from 'common/fetch';
import Comments from 'admin/requests/Comments';
import RequestForm from 'admin/requests/RequestForm';
import RequestActions from 'admin/requests/RequestActions';
import { formatDate, titleCase } from 'common/util';

import ExperimentsEntity from '../experiments/Entity';

@observer
export default class RequestEntity extends Component {
  //mobx observables
  comments = observable.array();
  checkers = observable.array();
  data = observable.map();

  //immutables
  levels = {};

  componentWillMount() {
    extendObservable(this, { pending: true });
    const { id } = this.props.match.params;

    adminFetch(`live/w-actions/${id}/details`).then(
      action(response => {
        if (response) {
          //init levels map {level_num : [role1, role2, ...]}

          if (
            response.workflow &&
            response.workflow.steps &&
            response.workflow.steps.length
          ) {
            response.workflow.steps.forEach(step => {
              this.levels[step.level] = this.levels[step.level] || [];
              this.levels[step.level].push(step.role.name);
            });
          } else {
            this.levels[1] = [response.state_changer]; // For handling case where Superadmin has approved
          }

          //init comments array
          this.comments.replace(
            response.comments.map(comment => ({ type: 'comment', ...comment }))
          );

          //init checkers array
          this.checkers.replace(
            response.checkers.map(checker => ({ type: 'checker', ...checker }))
          );

          this.data.replace(response);
          this.pending = false;
        }
      })
    );
  }

  handleUploadForm = body => {
    const { id } = this.props.match.params;

    //Check whether form body has these values or not
    if (
      typeof body.title === 'undefined' &&
      typeof body.description === 'undefined'
    ) {
      return;
    }

    return adminPut({
      url: `live/w-actions/${id}`,
      data: body,
    }).then(response => {
      if (response) {
        notifyDone();
      }
    });
  };

  @action
  handleCommentAdd = response => {
    this.comments.push({ type: 'comment', ...response });
  };

  @action
  handleActionUpdate = response => {
    this.data.replace(response);
    this.checkers.replace(
      response.checkers.map(checker => ({ ...checker, type: 'checker' }))
    );
  };

  render() {
    if (this.pending) {
      return <div class="spinner center" />;
    }

    const { levels, checkers, comments } = this;
    const data = this.data.toJS();
    const shouldShowTick = ['approved', 'executed'].indexOf(data.state) !== -1;
    const { id } = this.props.match.params;

    const url = `${entityMap[data.entity_name]}/${data.entity_id}`;

    return (
      <div class="parent-container requests-container">
        <div class="header">
          <span class="title">
            {data.permission.description &&
              titleCase(data.permission.description)}{' '}
          </span>
          <a className="link" href={`/razorx/${url}`} target="_blank">
            {data.entity_id}
          </a>
        </div>
        <div class="container-group requests-content">
          <div class="list-container">
            <div className="box container">
              {/* Header */}
              <div className="heading">
                {data.maker.name && <strong>{data.maker.name}</strong>}
                {data.created_at ? (
                  <span className="secondary-label">
                    {` performed this action on `}
                    {formatDate(data.created_at)}
                  </span>
                ) : null}
                <span className={`pill ${RequestState[data.state]} m-l`}>
                  {data.state}
                </span>
              </div>

              {/* Title, description */}
              <RequestForm
                title={data.title}
                description={data.description}
                requestState={data.state}
                onSubmit={this.handleUploadForm}
              />

              {/* Steps Assigned to */}
              <div className="container levels-container">
                <label>
                  <b>Assigned To:</b>
                </label>
                {levels &&
                  Object.keys(levels).map((key, kdx) => {
                    kdx++;
                    return (
                      <div
                        className={`m-t m-b level ${
                          kdx != data.current_level ? 'inactive' : ''
                        }`}
                        key={key}
                      >
                        {kdx < data.current_level ||
                        (kdx == data.current_level && shouldShowTick) ? (
                          <i className="i-yes text-success" />
                        ) : null}

                        <span className="square-pills no-color">
                          STEP {key}
                        </span>
                        {levels[key].map((item, idx) => (
                          <span className="square-pills" key={idx}>
                            {item}
                          </span>
                        ))}
                      </div>
                    );
                  })}
              </div>
            </div>

            {/*Workflow Actions*/}
            {['open', 'approved'].indexOf(data.state) > -1 && (
              <div className="box container">
                <div className="heading">
                  <b>Actions:</b>
                </div>

                <RequestActions
                  id={data.id}
                  onUpdateAction={this.handleActionUpdate}
                  requestState={data.state}
                  checkers={checkers}
                  hideTitle
                />
              </div>
            )}

            {/* Comments container */}
            <Comments
              comments={comments}
              checkers={checkers}
              id={data.id}
              onCommentAdd={this.handleCommentAdd}
            />
          </div>

          <ExperimentsEntity id={data.entity_id} isReadOnly />
        </div>
      </div>
    );
  }
}

/**
 * Request State map
 */
const RequestState = {
  approved: 'approved-state',
  executed: 'executed-state',
  closed: 'closed-state',
  rejected: 'rejected-state',
  open: 'open-state',
};

const entityMap = {
  razorx_experiment_activate: 'experiments',
};
