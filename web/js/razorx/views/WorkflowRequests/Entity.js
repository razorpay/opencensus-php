import React, { Component } from 'react';
import { observable, extendObservable, action } from 'mobx';
import { observer } from 'mobx-react';

import { withRouter } from 'common/deprecated/withRouter';
import { adminGet } from 'razorx/helpers/admin-fetch';
import { formatDate } from 'razorx/helpers/utils';
import ExperimentsEntity from 'razorx/views/Experiments/Entity';

import Comments from './Comments';
import RequestActions from './RequestActions';

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

@observer
class RequestEntity extends Component {
  //mobx observables
  comments = observable.array();
  checkers = observable.array();
  data = observable.map();

  //immutables
  levels = {};

  state = {};

  UNSAFE_componentWillMount() {
    extendObservable(this, { pending: true });
    const { id } = this.props.match.params;

    adminGet(`live/w-actions/${id}/details`).then(
      action((response) => {
        if (response) {
          //init levels map {level_num : [role1, role2, ...]}

          if (response.workflow && response.workflow.steps && response.workflow.steps.length) {
            response.workflow.steps.forEach((step) => {
              this.levels[step.level] = this.levels[step.level] || [];
              this.levels[step.level].push(step.role.name);
            });
          } else {
            this.levels[1] = [response.state_changer]; // For handling case where Superadmin has approved
          }

          //init comments array
          this.comments.replace(
            response.comments.map((comment) => ({ type: 'comment', ...comment })),
          );

          //init checkers array
          this.checkers.replace(
            response.checkers.map((checker) => ({ type: 'checker', ...checker })),
          );

          this.data.replace(response);
          this.pending = false;
        }
      }),
    );
  }

  @action
  handleCommentAdd = (response) => {
    this.comments.push({ type: 'comment', ...response });
  };

  @action
  handleActionUpdate = (response) => {
    this.data.replace(response);
    this.checkers.replace(response.checkers.map((checker) => ({ ...checker, type: 'checker' })));
  };

  updateEntityData = (entityData) => {
    this.setState({
      entityData,
    });
  };

  render() {
    if (this.pending) {
      return <div class="spinner center" />;
    }

    const { entityData } = this.state;
    const { checkers, comments } = this;
    const data = this.data.toJS();

    const url = `${entityMap[data.entity_name]}/${data.entity_id}`;

    return (
      <div class="parent-container requests-container">
        <div class="header">
          <span class="title">
            Experiment:{' '}
            <a className="link" href={`/razorx/${url}`} target="_blank" rel="noopener noreferrer">
              {entityData && entityData.description}
            </a>
          </span>
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
                <span className={`pill ${RequestState[data.state]} m-l`}>{data.state}</span>
              </div>
            </div>

            <div class="box container">
              <ExperimentsEntity
                id={data.entity_id}
                updateEntityData={this.updateEntityData}
                isReadOnly
                isCustomLayout
              />
            </div>

            {/* Comments container */}
            <Comments
              comments={comments}
              checkers={checkers}
              id={data.id}
              onCommentAdd={this.handleCommentAdd}
            />
          </div>

          {/*Workflow Actions*/}
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

export default withRouter(RequestEntity);
