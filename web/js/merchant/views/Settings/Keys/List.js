import React from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import Alert from 'common/ui/Forms/Alert';
import RTracking from 'react-tracking';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import * as KeyActions from 'merchant/reducers/keys';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import KeysList from 'merchant/views/Settings/Keys/components/KeysList';
import RollKey from 'merchant/views/Settings/Keys/components/RollKey';
import NewKey from 'merchant/views/Settings/Keys/components/NewKey';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import WebsiteComplianceNudge from 'merchant/views/Account/WebsiteAppDetails/Nudge';
import {
  shouldShowWebsiteComplianceModal,
  isPolicyWizardV2Enabled,
} from 'merchant/views/Account/WebsiteAppDetails/utils';
import WebsiteComplianceMobilePrompt from 'merchant/views/Account/WebsiteAppDetails/Prompt.mobile';
import WebsiteComplianceBanner from 'merchant/components/Announcements/WebsiteCompliance';
import { isMobileDevice } from 'merchant/components/Home/data';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { withSplitzService } from 'common/splitz';

class KeysListContainer extends ListContainer {
  fetchEntityList() {
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

  generateKey = (params, isKeyRegeneration = false) => {
    const { user } = this.props;
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Initiate_API_Key_Gen',
    });

    return this.props
      .generateKey(params)
      .then((response) => {
        const key = response.new || response;

        this.props.showNotification({
          type: 'success',
          message: 'New Key Generated',
          closeTimeout: 15000,
        });

        // track success only for key regeneration
        if (isKeyRegeneration) {
          selfServeTrackSuccess({
            selfServeAction: 'API Key Regenerated',
            page: 'API Keys',
            screen: user?.isAccountAndSettingsRevampEnabled ? 'Account & Settings' : 'Settings',
          });
        }
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

  componentDidUpdate() {
    const { activationData } = this.props;
    const { websiteSectionDetailsData } = this.props;
    const { websiteComplianceModalVisibility } = this.props;
    const { user } = this.props;

    const { splitz } = this.props;

    const isMobileResolution = isMobileDevice();

    if (
      activationData.data &&
      websiteSectionDetailsData.data &&
      websiteComplianceModalVisibility.data
    ) {
      const shouldShowModal =
        !isPolicyWizardV2Enabled({ splitz, user, activationData: activationData.data }) &&
        shouldShowWebsiteComplianceModal(
          activationData,
          websiteSectionDetailsData,
          websiteComplianceModalVisibility,
        );

      if (shouldShowModal && user.isWebsiteComplianceFlowEnabled && isMobileResolution) {
        this.props.openModal({
          component: <WebsiteComplianceMobilePrompt screen="API Keys" />,
          size: 'small',
        });
      }
    }
  }

  render() {
    const { loading, keys } = this.props.keys;
    const mode = this.props.session.modeFormatted;
    const status = this.state.status;
    const hasKeyAccess = this.props.session.user.has_key_access;
    const businessWebsite = this.props.session.user.business_website;
    const { isWebsiteInWorkflow, onWebsiteAdd, user } = this.props;
    const isMobileResolution = isMobileDevice();

    return (
      <>
        <div className="banner-container">
          {!isMobileResolution ? <WebsiteComplianceBanner screen="API Keys" /> : null}
        </div>
        <WebsiteComplianceNudge screen="API Keys" />
        <div className="content-wrapper">
          <Alert type={status.type} message={status.message} />
          <KeysList
            user={user}
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
      </>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        keys: state.keys,
        session: state.session,
        user: state.session.user,
        websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
        websiteComplianceModalVisibility: state.websiteCompliance.bannerAndModalVisibility,
        activationData: state.websiteCompliance.activationData,
      };
    },
    { ...KeyActions, ...ModalActions, ...NotificationsActions },
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('KeysListContainer')),
)(withRouter(withSplitzService(KeysListContainer)));
