import React, { Component } from 'react';
import { openModal } from 'common/modal';
import { observer } from 'mobx-react';
import { adminFetch } from 'util/fetch';

import WorkflowForm from './WorkflowForm';

@observer
export default class EditWorkflow extends Component {
  state = {
    name: '',
    allPerms: [],
    selectedPerms: [],
    allRoles: [],
    selectedRoles: [],
    levels: [],
    pending: true,
  };

  componentWillMount() {
    let { model } = this.props;
    if (!model) {
      let requests = [
        adminFetch({ route_name: 'role_get_multiple' }),
        adminFetch({
          count: 1000,
          query_params: { type: 'workflow' },
          route_name: 'permission_get_multiple',
        }),
      ];

      Promise.all(requests).then(([allRoles, allPerms]) => {
        this.setState({
          allPerms: allPerms.items,
          allRoles: allRoles.items,
          pending: false,
        });
      });
    }
  }

  addSteps = () => {
    let { levels } = this.state;
    levels.push('asdasdsad');
    this.setState({ levels });
  };

  selectPerms = e => {
    this.state.allPerms.some(p => {
      if (p.id === e.target.value) {
        this.setState({
          selectedPerms: this.state.selectedPerms.concat(p),
          allPerms: this.state.allPerms.filter(q => q.id !== p.id),
        });
        return 1;
      }
    });
  };

  deletePerms = perm => {
    this.setState({
      allPerms: this.state.allPerms.concat(perm),
      selectedPerms: this.state.selectedPerms.filter(q => q.id !== perm.id),
    });
  };

  selectRoles = e => {
    this.state.allRoles.some(p => {
      if (p.id === e.target.value) {
        this.setState({
          selectedRoles: this.state.selectedRoles.concat(p),
          allRoles: this.state.allRoles.filter(q => q.id !== p.id),
        });
        return 1;
      }
    });
  };

  deleteRoles = role => {
    this.setState({
      allRoles: this.state.allRoles.concat(perm),
      selectedRoles: this.state.selectedRoles.filter(q => q.id !== perm.id),
    });
  };

  render() {
    let { model } = this.props;

    if (this.state.pending) {
      return <div class="spinner" />;
    }

    return (
      <WorkflowForm
        {...this.state}
        onStepsAdd={this.addSteps}
        onSelectPerms={this.selectPerms}
        onDeletePerms={this.deletePerms}
        onSelectRoles={this.selectRoles}
        onDeleteRoles={this.deleteRoles}
      />
    );
  }
}

export function showEntity() {
  return <EditWorkflow model={this} />;
}
