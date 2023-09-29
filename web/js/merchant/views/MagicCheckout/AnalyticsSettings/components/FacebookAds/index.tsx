import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ContainerHeading from 'merchant/views/MagicCheckout/AnalyticsSettings/common/ContainerHeading';
import ContainerContent from 'merchant/views/MagicCheckout/AnalyticsSettings/common/ContainerContent';
import AnalyticsEvents from 'merchant/views/MagicCheckout/AnalyticsSettings/common/AnalyticsEvents';
import DemoVideo from 'merchant/views/MagicCheckout/AnalyticsSettings/common/DemoVideo';

import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { FacebookAdsPropsTypes } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  deleteAccountConfigs as deleteConfig,
  addAccountConfigs,
  saveEventConfigs as saveEvents,
} from 'merchant/reducers/magicCheckout/analyticsSettings/actions';

import {
  accountConfigsFormatter,
  updateIntegrationMethod,
  addAnalyticsAccount,
  updateSaveEventsCtaState,
  updateAddAccountCtaState,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/utils';

import {
  InfoLink,
  IntegrationContainer,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/common';

import {
  FACEBOOK_ADS,
  FACEBOOK_INTEGRATION_OPTIONS,
  FACEBOOK_ANALYTICS_EVENTS,
  FACEBOOK_ADS_INTEGRATION_STEPS,
  DELETE_CONFIRMATION_TEXTS,
  NOTIFICATION_TEXTS,
  ANALYTICS_PLATFORM,
  INTEGRATION_TYPE,
  INTEGRATION_MODAL_TEXTS,
  FB_DEFAULT_EVENTS,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);

const INSTRUCTION_POINTS = [
  <span key="admin-link">
    Go to{' '}
    <InfoLink
      href="https://business.facebook.com/latest/home"
      target="_blank"
      rel="noreferrer noopener"
    >
      Manage integration
    </InfoLink>{' '}
    - In all activity graph
  </span>,
  'Click on Manage - beside conversion API',
  'Click on Sending events from server - under drop down',
  'Click Merchant protocol API - under events section',
  'Click Generate API - under Top section',
  'Click on generate access token',
  'Copy Token',
];

const FacebookAds = (props: FacebookAdsPropsTypes): JSX.Element => {
  const { tabHeader, inputTableHeader, headerIcon } = FACEBOOK_ADS;

  const { demoVideoInfoText, pointsHeader, videoLink } =
    INTEGRATION_MODAL_TEXTS?.[ANALYTICS_PLATFORM.facebookAds.key];

  const {
    analyticsSettingsConfigs,
    deleteConfig,
    showNotification,
    addConfigs,
    closeModal,
    saveEvents,
    openModal,
  } = props;

  const { merchantAnalyticsConfigs: magicAnalyticsConfigs } = analyticsSettingsConfigs;
  const { events, accounts: analyticsAccounts } =
    magicAnalyticsConfigs?.[ANALYTICS_PLATFORM.facebookAds.key] || {};

  const [fbAccountConfigs, setFbAccountConfigs] = useState<Record<string, any>[]>([{}]);
  const [fbEventConfigs, setFbEventConfigs] = useState<Record<string, boolean>>({});
  const [isAddAccountCtaDisabled, setIsAddAccountCtaDisabled] = useState(true);
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const [showPreviewMode, setShowPreviewMode] = useState<boolean>(false);
  const [isSaveEventsCtaDisabled, setIsSaveEventsCtaDisabled] = useState<boolean>(true);

  useEffect(() => {
    const { integrationMethod, data } = fbAccountConfigs?.[fbAccountConfigs.length - 1];
    const isAccountConfigsAvailable = !!data?.length;

    setIsAddAccountCtaDisabled(
      updateAddAccountCtaState(integrationMethod, isAccountConfigsAvailable),
    );
    setIsSaveEventsCtaDisabled(
      updateSaveEventsCtaState(integrationMethod, isAccountConfigsAvailable),
    );
  }, [fbAccountConfigs]);

  useEffect(() => {
    setFbAccountConfigs(
      accountConfigsFormatter(magicAnalyticsConfigs, ANALYTICS_PLATFORM.facebookAds.key),
    );
  }, [magicAnalyticsConfigs]);

  useEffect(() => {
    const isAccountConfigsAvailable = !!analyticsAccounts?.length;

    if (Array.isArray(events) && events.length === 0) {
      setFbEventConfigs(FB_DEFAULT_EVENTS);
    } else {
      setFbEventConfigs(events);
    }

    setShowPreviewMode(!!(events && Object.keys(events)?.length && isAccountConfigsAvailable));
  }, [events]);

  const setIntegrationMethod = (val: string): void => {
    setFbAccountConfigs(updateIntegrationMethod(val, fbAccountConfigs));
    setShowPreviewMode(false);
  };

  const deleteAccountConfig = (id: string): void => {
    deleteConfig(id, ANALYTICS_PLATFORM.facebookAds.key)
      .then(() => {
        showNotification({
          type: 'success',
          message: NOTIFICATION_TEXTS.success.deleteAccount,
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_TEXTS.error,
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  const openDeleteConfirmationModal = (id: string): void => {
    const { header, desc, affirmativeLabel, abortLabel } =
      DELETE_CONFIRMATION_TEXTS?.[ANALYTICS_PLATFORM.facebookAds.key];

    openModal({
      size: 'small',
      className: 'remove-configs-confirmation-modal',
      component: (
        <SuspenseWithLoader type="center">
          <ConfirmationModal
            header={header}
            desc={desc}
            affirmativeLabel={affirmativeLabel}
            abortLabel={abortLabel}
            onAffirm={() => deleteAccountConfig(id)}
          />
        </SuspenseWithLoader>
      ),
    });
  };

  const handleSavingAccountCreds = (creds: Record<string, string>): void => {
    const newConfig = fbAccountConfigs[fbAccountConfigs.length - 1];
    const params = {
      platform: ANALYTICS_PLATFORM.facebookAds.key,
      platform_user_id: creds?.pixelId,
      access_token: window?.btoa(creds?.accessToken),
      integration_method: newConfig.integrationMethod ?? INTEGRATION_TYPE.backend,
    };
    addConfigs(params)
      .then(() => {
        closeModal();
        showNotification({
          type: 'success',
          message: NOTIFICATION_TEXTS.success.addAccount,
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_TEXTS.error,
        });
      });
  };

  const updateEventConfigs = (toggleState: boolean, keyName: string): void => {
    setFbEventConfigs((prev) => ({
      ...prev,
      [keyName]: toggleState,
    }));
  };

  const saveEventConfigs = (): void => {
    saveEvents(fbEventConfigs, ANALYTICS_PLATFORM.facebookAds.key)
      .then(() => {
        setShowPreviewMode(true);
        showNotification({
          type: 'success',
          message: NOTIFICATION_TEXTS.success.saveEvents,
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_TEXTS.error,
        });
      });
  };

  const handleAddAccount = (): void => {
    const newConfig = addAnalyticsAccount(ANALYTICS_PLATFORM.facebookAds.key);
    setFbAccountConfigs((prev) => [...prev, newConfig]);
    setShowPreviewMode(false);
  };

  const integrationModalProps = {
    demoContainer: DemoVideo,
    modalIcon: headerIcon,
    stepTexts: FACEBOOK_ADS_INTEGRATION_STEPS,
    demoInfoText: demoVideoInfoText,
    points: INSTRUCTION_POINTS,
    pointsHeader,
    demoVideoLink: videoLink,
    onSavingAccountCreds: handleSavingAccountCreds,
    setIntegrationMethod,
  };
  return (
    <>
      <IntegrationContainer>
        <ContainerHeading
          header={tabHeader}
          onAddAccount={handleAddAccount}
          isCtaDisabled={isAddAccountCtaDisabled}
        />
        <ContainerContent
          tableHeader={inputTableHeader}
          headerIcon={headerIcon}
          customIntegrationOptions={FACEBOOK_INTEGRATION_OPTIONS}
          integrationModalProps={integrationModalProps}
          merchantAnalyticsConfigs={fbAccountConfigs}
          setIntegrationMethod={setIntegrationMethod}
          deleteAccountConfig={openDeleteConfirmationModal}
        />
      </IntegrationContainer>
      <AnalyticsEvents
        analyticsEvents={FACEBOOK_ANALYTICS_EVENTS}
        header={ANALYTICS_PLATFORM.facebookAds.label}
        eventConfigs={fbEventConfigs}
        updateEventConfigs={updateEventConfigs}
        magicAnalyticsConfigs={magicAnalyticsConfigs}
        showPreviewMode={showPreviewMode}
        setShowPreviewMode={setShowPreviewMode}
        saveEventConfigs={saveEventConfigs}
        isSaveEventsCtaDisabled={isSaveEventsCtaDisabled}
      />
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      deleteConfig,
      showNotification,
      closeModal,
      addConfigs: addAccountConfigs,
      saveEvents,
      openModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(FacebookAds);
