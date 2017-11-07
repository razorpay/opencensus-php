import React, { Component } from 'react';
import { openModal, notifyDone } from 'common/modal';
import { observer } from 'mobx-react';
import { adminFetch, adminPost, adminPut } from 'util/fetch';

import user from 'admin/user';
import Level from './Level';
import WorkflowForm from './WorkflowForm';

@observer
export default class EditWorkflow extends Component {
  state = {
    id: '',
    name: '',
    levels: [],
    permissions: [],
    allRoles: [],
    allPerms: [],
    pending: true,
  };

  componentWillMount() {
    let { id } = this.props.match.params;

    let requests = [
      adminFetch({ route_name: 'role_get_multiple' }),
      adminFetch({
        count: 1000,
        query_params: { type: 'workflow' },
        route_name: 'permission_get_multiple',
      }),
    ];

    if (id !== 'new') {
      requests.push(
        adminFetch({
          route_name: 'workflow_get',
          url_params: { id },
        })
      );
    }

    Promise.all(requests).then(([allRoles, allPerms, workflow = null]) => {
      this.setState({
        allPerms: allPerms.items,
        allRoles: allRoles.items,
        ...(id !== 'new' && { id }),
        ...(workflow && { name: workflow.name }),
        ...(workflow && { permissions: workflow.permissions }),
        ...(workflow && { levels: workflow.levels }),
        pending: false,
      });
    });
  }

  addLevel = () => {
    let levels = [...this.state.levels];
    levels.push({
      op_type: 'and',
      level: levels.length + 1,
      steps: [],
    });
    this.setState({ levels });
  };

  deleteLevel = levelNum => {
    let { levels } = this.state;

    levels = levels.filter(l => l.level !== levelNum);
    //remap levelnum once deleted
    levels = levels.map((l, idx) => {
      l.level = idx + 1;
      return l;
    });
    this.setState({ levels });
  };

  selectRole = (e, levelNum) => {
    let { allRoles, levels } = this.state;
    let idx = levels.findIndex(l => l.level === levelNum);
    let roleIdx = allRoles.findIndex(aRole => aRole.id === e.target.value);

    levels[idx].steps.push({
      role_id: allRoles[roleIdx].id,
      reviewer_count: 1,
    });

    this.setState({ levels });
  };

  deleteRole = (step, levelNum) => {
    let { levels } = this.state;
    let idx = levels.findIndex(l => l.level === levelNum);

    levels[idx].steps = levels[idx].steps.filter(
      s => s.role_id !== step.role_id
    );
    this.setState({ levels });
  };

  selectPerms = e => {
    this.state.allPerms.some(p => {
      if (p.id === e.target.value) {
        this.setState({
          permissions: this.state.permissions.concat(p),
          allPerms: this.state.allPerms.filter(q => q.id !== p.id),
        });
        return 1;
      }
    });
  };

  deletePerms = perm => {
    this.setState({
      allPerms: this.state.allPerms.concat(perm),
      permissions: this.state.permissions.filter(q => q.id !== perm.id),
    });
  };

  updateReviewerCount = (e, step, levelNum) => {
    let { levels } = this.state;
    let idx = levels.findIndex(l => l.level === levelNum);
    let stepIdx = levels[idx].steps.findIndex(s => s.role_id === step.role_id);

    levels[idx].steps[stepIdx].reviewer_count = e.target.value;

    this.setState({ levels });
  };

  updateOpType = (e, levelNum) => {
    let { levels } = this.state;
    let idx = levels.findIndex(level => level.level === levelNum);

    levels[idx].op_type = e.target.value;
    this.setState({ levels });
  };

  save = body => {
    let { id } = this.props.match.params;
    let data = { body };

    data.body.permissions = this.state.permissions;
    data.body.levels = this.state.levels.map(l => {
      l.steps = l.steps.map(s => ({
        role_id: s.role_id,
        reviewer_count: s.reviewer_count,
      }));
      return l;
    });

    data.body.permissions = this.state.permissions.map(s => s.id);

    if (id !== 'new') {
      data.route_name = 'workflow_update';
      data.url_params = {
        id,
      };

      return adminPut(data).then(response => {
        if (response) {
          notifyDone();
        }
      });
    } else {
      data.body.org_id = user.org_id;
      data.route_name = 'workflow_create';

      return adminPost(data).then(response => {
        if (response) {
          notifyDone();
        }
      });
    }
  };

  render() {
    let { model } = this.props;
    let { levels, allRoles } = this.state;

    if (this.state.pending) {
      return <div class="spinner" />;
    }

    return (
      <WorkflowForm
        {...this.state}
        onSelectPerms={this.selectPerms}
        onDeletePerms={this.deletePerms}
        onLevelAdd={this.addLevel}
        onSubmit={this.save}
      >
        {levels.length
          ? levels.map((level, idx) => (
              <Level
                key={idx}
                level={level}
                allRoles={allRoles}
                onRoleSelect={this.selectRole}
                onDeleteRole={this.deleteRole}
                onOpTypeUpdate={this.updateOpType}
                onReviewerCountUpdate={this.updateReviewerCount}
                onLevelDelete={this.deleteLevel}
              />
            ))
          : null}
      </WorkflowForm>
    );
  }
}

export function showEntity() {
  return <EditWorkflow model={this} />;
}
