import React, { Component } from 'react';
import { observable, extendObservable, action } from 'mobx';
import { observer } from 'mobx-react';
import { openModal, notifySuccess, notifyDone } from 'common/modal';
import { adminFetch, adminPut } from 'util/fetch';
import DiffModal from './DiffModal';
import Comments from './Comments';
import RequestForm from './RequestForm';
import RequestActions from './RequestActions';
import { formatDate, titleCase } from 'util/index';

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

    adminFetch({
      route_name: 'workflow_action_details',
      url_params: {
        id,
      },
    }).then(
      action(response => {
        if (response) {
          //init levels map {level_num : [role1, role2, ...]}
          response.workflow_steps.forEach(step => {
            this.levels[step.level] = this.levels[step.level] || [];
            this.levels[step.level].push(step.role.name);
          });

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

  openDiffModal = () => {
    const { id } = this.props.match.params;
    openModal(<DiffModal id={id} />);
  };

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
      route_name: 'workflow_action_update',
      url_params: {
        id,
      },
      body,
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

    return (
      <div>
        <header class="requests-header">
          {data.permission.description &&
            titleCase(data.permission.description)}{' '}
          ({data.entity_id})
          <button
            class="pull-right"
            style={{ margin: '0' }}
            onClick={this.openDiffModal}
          >
            View Changes
          </button>
        </header>
        <div class="box-container">
          <main class="box requests-content">
            <div class="box-label">
              {data.admin.name ? (
                <span>
                  <strong>{data.admin.name}</strong> performed this action{' '}
                </span>
              ) : null}
              {data.created_at ? (
                <span>{formatDate(data.created_at)}</span>
              ) : null}
              <span class={`request-state ${RequestState[data.state]}`}>
                {data.state}
              </span>
            </div>
            <RequestForm
              title={data.title}
              description={data.description}
              requestState={data.state}
              onSubmit={this.handleUploadForm}
            />
            <div class="levels-container">
              <label class="levels-label">
                <b>Assigned To: </b>
              </label>
              {levels &&
                Object.keys(levels).map((key, kdx) => {
                  kdx++;
                  return (
                    <div
                      class={
                        'level ' + (kdx != data.current_level ? 'inactive' : '')
                      }
                      key={key}
                    >
                      {kdx < data.current_level ||
                      (kdx == data.current_level && shouldShowTick) ? (
                        <i class="i-yes" />
                      ) : null}

                      <span class="level-box">STEP {key}</span>
                      {levels[key].map((item, idx) => (
                        <span class="level-box" key={idx}>
                          {item}
                        </span>
                      ))}
                    </div>
                  );
                })}
            </div>
            <span class="comments-count">{comments.length} comments</span>
            <Comments
              comments={comments}
              checkers={checkers}
              id={data.id}
              onCommentAdd={this.handleCommentAdd}
            />
          </main>
          <RequestActions
            id={data.id}
            onUpdateAction={this.handleActionUpdate}
            requestState={data.state}
            checkers={checkers}
          />
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
