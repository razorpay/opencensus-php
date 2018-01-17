import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
// import Role from 'merchant/components/Role'
import KeysList from 'merchant/components/Keys/KeysList';
import ListContainer from 'merchant/containers/ListContainer';
import * as KeyActions from 'merchant/modules/keys';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import RollKey from './RollKey';
import NewKey from './NewKey';

@connect(
  state => {
    return {
      keys: state.keys,
      session: state.session,
    };
  },
  { ...KeyActions, ...ModalActions, ...NotificationsActions }
)
export default class KeysListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchKeys({
      id: this.props.session.user.id,
    });
  }

  showRollKeyModal = (params = null) => {
    this.props.openModal({
      size: 'small',
      component: (
        <RollKey
          params={params}
          merchantId={this.props.session.user.id}
          generateKey={this.generateKey}
        />
      ),
    });
  };

  showNewKeyModal = key => {
    this.props.openModal({
      component: <NewKey apiKey={key} />,
    });
  };

  generateKey = params => {
    return this.props.generateKey(params).then(response => {
      var key = response.new || response;

      this.props.showNotification({
        type: 'success',
        message: 'New Key Generated',
        closeTimeout: 15000,
      });
      this.props.closeModal();
      this.showNewKeyModal(key);
    });
  };

  render() {
    let { loading, keys } = this.props.keys;
    let mode = this.props.session.modeFormatted;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <Alert type={status.type} message={status.message} />

        <KeysList
          keys={keys}
          isLoading={loading}
          mode={mode}
          generateKey={this.generateKey}
          showRollKeyModal={this.showRollKeyModal}
          merchantId={this.props.session.user.id}
        />
      </div>
    );
  }
}
