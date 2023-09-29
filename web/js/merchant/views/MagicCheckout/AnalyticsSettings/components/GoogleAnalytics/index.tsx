import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ContainerHeading from 'merchant/views/MagicCheckout/AnalyticsSettings/common/ContainerHeading';
import ContainerContent from 'merchant/views/MagicCheckout/AnalyticsSettings/common/ContainerContent';
import AnalyticsEvents from 'merchant/views/MagicCheckout/AnalyticsSettings/common/AnalyticsEvents';
import DemoVideo from 'merchant/views/MagicCheckout/AnalyticsSettings/common/DemoVideo';

import {
  accountConfigsFormatter,
  updateIntegrationMethod,
  addAnalyticsAccount,
  updateSaveEventsCtaState,
  updateAddAccountCtaState,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/utils';

import {
  deleteAccountConfigs as deleteConfig,
  addAccountConfigs,
  saveEventConfigs as saveEvents,
} from 'merchant/reducers/magicCheckout/analyticsSettings/actions';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { GoogleAnalyticsPropsTypes } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  IntegrationContainer,
  InfoLink,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/common';

import {
  GOOGLE_ANALYTICS,
  GOOGLE_ANALYTICS_EVENTS,
  GOOGLE_ANALYTICS_INTEGRATION_STEPS,
  DELETE_CONFIRMATION_TEXTS,
  NOTIFICATION_TEXTS,
  ANALYTICS_PLATFORM,
  INTEGRATION_TYPE,
  INTEGRATION_MODAL_TEXTS,
  GA4_DEFAULT_EVENTS,
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
    <InfoLink href="https://analytics.google.com/" target="_blank" rel="noreferrer noopener">
      Admin
    </InfoLink>{' '}
    - on the bottom left
  </span>,
  'Go to Data Streams - under admin Section',
  'Copy Measurement ID - under stream details',
  'Click Merchant protocol API - under events section',
  'Click Create - on top right',
  'Enter Nickname- Inside the field',
  'Click Create- on top right',
  'Copy Secret value - Under API secrets section',
];

const GoogleAnalytics = (props: GoogleAnalyticsPropsTypes): JSX.Element => {
  const { tabHeader, inputTableHeader, headerIcon }: Record<string, string> = GOOGLE_ANALYTICS;

  const { demoVideoInfoText, pointsHeader, videoLink } =
    INTEGRATION_MODAL_TEXTS?.[ANALYTICS_PLATFORM.googleAnalytics.key];

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
    magicAnalyticsConfigs?.[ANALYTICS_PLATFORM.googleAnalytics.key] || {};

  const [gaAccountConfigs, setGaAccountConfigs] = useState<Record<string, any>[]>([{}]);
  const [gaEventConfigs, setGaEventConfigs] = useState<Record<string, boolean>>({});
  const [isAddAccountCtaDisabled, setIsAddAccountCtaDisabled] = useState<boolean>(true);
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const [showPreviewMode, setShowPreviewMode] = useState<boolean>(false);
  const [isSaveEventsCtaDisabled, setIsSaveEventsCtaDisabled] = useState<boolean>(true);

  const setIntegrationMethod = (val: string): void => {
    setGaAccountConfigs(updateIntegrationMethod(val, gaAccountConfigs));
    setShowPreviewMode(false);
  };

  const deleteAccountConfig = (id: string): void => {
    deleteConfig(id, ANALYTICS_PLATFORM.googleAnalytics.key)
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
      DELETE_CONFIRMATION_TEXTS[ANALYTICS_PLATFORM.googleAnalytics.key];

    openModal({
      size: 'small',
      className: 'remove-configs-confirmation-modal',
      component: (
        <SuspenseWithLoader>
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
    const newConfig = gaAccountConfigs?.[gaAccountConfigs.length - 1];

    const params = {
      platform: ANALYTICS_PLATFORM.googleAnalytics.key,
      platform_user_id: creds?.measurementId || '',
      access_token: window?.btoa(creds?.apiSecret) || '',
      integration_method: newConfig?.integrationMethod || INTEGRATION_TYPE.backend,
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
    setGaEventConfigs((prev) => ({
      ...prev,
      [keyName]: toggleState,
    }));
  };

  const saveEventConfigs = (): void => {
    saveEvents(gaEventConfigs, ANALYTICS_PLATFORM.googleAnalytics.key)
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
    const newConfig = addAnalyticsAccount(ANALYTICS_PLATFORM.googleAnalytics.key);
    setGaAccountConfigs((prev) => [...prev, newConfig]);
    setShowPreviewMode(false);
  };

  const integrationModalProps = {
    demoContainer: DemoVideo,
    modalIcon: headerIcon,
    stepTexts: GOOGLE_ANALYTICS_INTEGRATION_STEPS,
    demoInfoText: demoVideoInfoText,
    points: INSTRUCTION_POINTS,
    pointsHeader,
    demoVideoLink: videoLink,
    onSavingAccountCreds: handleSavingAccountCreds,
    setIntegrationMethod,
  };

  useEffect(() => {
    setGaAccountConfigs(
      accountConfigsFormatter(magicAnalyticsConfigs, ANALYTICS_PLATFORM.googleAnalytics.key),
    );
  }, [magicAnalyticsConfigs]);

  useEffect(() => {
    const isAccountConfigsAvailable = !!analyticsAccounts?.length;

    if (Array.isArray(events) && events.length === 0) {
      setGaEventConfigs(GA4_DEFAULT_EVENTS);
    } else {
      setGaEventConfigs(events);
    }

    setShowPreviewMode(!!(events && Object.keys(events)?.length && isAccountConfigsAvailable));
  }, [events]);

  useEffect(() => {
    const { integrationMethod, data } = gaAccountConfigs?.[gaAccountConfigs.length - 1];
    const isAccountConfigsAvailable = !!data?.length;

    setIsAddAccountCtaDisabled(
      updateAddAccountCtaState(integrationMethod, isAccountConfigsAvailable),
    );
    setIsSaveEventsCtaDisabled(
      updateSaveEventsCtaState(integrationMethod, isAccountConfigsAvailable),
    );
  }, [gaAccountConfigs]);

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
          integrationModalProps={integrationModalProps}
          merchantAnalyticsConfigs={gaAccountConfigs}
          setIntegrationMethod={setIntegrationMethod}
          deleteAccountConfig={openDeleteConfirmationModal}
        />
      </IntegrationContainer>
      <AnalyticsEvents
        analyticsEvents={GOOGLE_ANALYTICS_EVENTS}
        header={ANALYTICS_PLATFORM.googleAnalytics.label}
        eventConfigs={gaEventConfigs}
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

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      deleteConfig,
      showNotification,
      addConfigs: addAccountConfigs,
      closeModal,
      saveEvents,
      openModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(GoogleAnalytics);
