import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'common/ui/Forms/Alert';
import RTracking from 'react-tracking';
// import Role from 'merchant/components/Role'
import ListContainer from 'merchant/containers/ListContainer';
import * as KeyActions from 'merchant/reducers/keys';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import KeysList from 'merchant/views/Settings/Keys/components/KeysList';
import RollKey from 'merchant/views/Settings/Keys/components/RollKey';
import NewKey from 'merchant/views/Settings/Keys/components/NewKey';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(
  (state) => {
    return {
      keys: state.keys,
      session: state.session,
    };
  },
  { ...KeyActions, ...ModalActions, ...NotificationsActions },
)
@RTracking(() => window.rzpQ.component('KeysListContainer'))
export default class KeysListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchKeys(
      { mode: this.props.session.mode },
      this.props.session.user.has_key_access,
    );
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

  showNewKeyModal = (key) => {
    this.props.openModal({
      component: <NewKey apiKey={key} />,
    });
  };

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Initiate_API_Key_Gen',
    }),
  )
  generateKey = (params) => {
    return this.props
      .generateKey(params)
      .then((response) => {
        var key = response.new || response;

        this.props.showNotification({
          type: 'success',
          message: 'New Key Generated',
          closeTimeout: 15000,
        });

        analyticsTrack({
          objectName: `regenerate ${this.props.session.mode} key`,
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'API Keys',
            status: 'Success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });

        this.props.closeModal();
        this.showNewKeyModal(key);
      })
      .catch((err) => {
        analyticsTrack({
          objectName: `regenerate ${this.props.session.mode} key`,
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'API Keys',
            status: 'Failure',
            failureReason: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };

  render() {
    let { loading, keys } = this.props.keys;
    let mode = this.props.session.modeFormatted;
    let status = this.state.status;
    let hasKeyAccess = this.props.session.user.has_key_access;
    let businessWebsite = this.props.session.user.business_website;
    const { isWebsiteInWorkflow, onWebsiteAdd } = this.props;

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
          hasKeyAccess={hasKeyAccess}
          businessWebsite={businessWebsite}
          isWebsiteInWorkflow={isWebsiteInWorkflow}
          onWebsiteAdd={onWebsiteAdd}
        />
      </div>
    );
  }
}
