import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ContainerHeading from 'merchant/views/MagicCheckout/AnalyticsSettings/common/ContainerHeading';
import ContainerContent from 'merchant/views/MagicCheckout/AnalyticsSettings/common/ContainerContent';
import DemoVideo from 'merchant/views/MagicCheckout/AnalyticsSettings/common/DemoVideo';
import AnalyticsEvents from 'merchant/views/MagicCheckout/AnalyticsSettings/common/AnalyticsEvents';

import {
  deleteAccountConfigs as deleteConfig,
  addAccountConfigs,
  saveEventConfigs as saveEvents,
} from 'merchant/reducers/magicCheckout/analyticsSettings/actions';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { GoogleAdsPropsTypes } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  accountConfigsFormatter,
  updateIntegrationMethod,
  addAnalyticsAccount,
  updateAddAccountCtaState,
  updateSaveEventsCtaState,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/utils';

import {
  IntegrationContainer,
  InfoLink,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/common';

import {
  GOOGLE_ADS,
  GOOGLE_ADS_INTEGRATION_STEPS,
  GOOGLE_ADS_ANALYTICS_EVENTS,
  DELETE_CONFIRMATION_TEXTS,
  NOTIFICATION_TEXTS,
  ANALYTICS_PLATFORM,
  INTEGRATION_TYPE,
  INTEGRATION_MODAL_TEXTS,
  GOOGLE_ADS_DEFAULT_EVENTS,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

const ConfirmationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
    ),
);

export const INSTRUCTION_POINTS = [
  <span key="admin-link">
    Go to{' '}
    <InfoLink
      href="https://ads.google.com/aw/settings/account"
      target="_blank"
      rel="noreferrer noopener"
    >
      Admin
    </InfoLink>{' '}
    - on the bottom right
  </span>,
  'Go to Data Streams - under admin Section',
  'Copy Measurement ID - under stream details',
  'Click Merchant protocol API - under events section',
  'Click Create - on top Left',
  'Enter Nickname- In side drawer',
];

const GoogleAds = (props: GoogleAdsPropsTypes): JSX.Element => {
  const { tabHeader, inputTableHeader, headerIcon } = GOOGLE_ADS;

  const { demoVideoInfoText, pointsHeader, videoLink } =
    INTEGRATION_MODAL_TEXTS?.[ANALYTICS_PLATFORM.googleAds.key];

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

  const { accounts: oAuthAccounts = [] } = magicAnalyticsConfigs?.google || {};

  const { events, accounts: analyticsAccounts } =
    magicAnalyticsConfigs?.[ANALYTICS_PLATFORM.googleAds.key] || {};

  const [googleAdsAccountConfigs, setGoogleAdsAccountConfigs] = useState<Record<string, any>[]>([
    {},
  ]);
  const [googleAdsEventConfigs, setGoogleAdsEventConfigs] = useState<Record<string, boolean>>({});
  const [isAddAccountCtaDisabled, setIsAddAccountCtaDisabled] = useState(true);
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const [showPreviewMode, setShowPreviewMode] = useState<boolean>(false);
  const [isSaveEventsCtaDisabled, setIsSaveEventsCtaDisabled] = useState<boolean>(true);

  useEffect(() => {
    const { integrationMethod, data } =
      googleAdsAccountConfigs?.[googleAdsAccountConfigs.length - 1];
    const isAccountConfigsAvailable = !!data?.length;

    setIsAddAccountCtaDisabled(
      updateAddAccountCtaState(integrationMethod, isAccountConfigsAvailable),
    );
    setIsSaveEventsCtaDisabled(
      updateSaveEventsCtaState(integrationMethod, isAccountConfigsAvailable),
    );
  }, [googleAdsAccountConfigs]);

  useEffect(() => {
    setGoogleAdsAccountConfigs(
      accountConfigsFormatter(magicAnalyticsConfigs, ANALYTICS_PLATFORM.googleAds.key),
    );
  }, [magicAnalyticsConfigs]);

  useEffect(() => {
    const isAccountConfigsAvailable = !!analyticsAccounts?.length;
    const isOAuthIdAvailable = !!Object.keys(oAuthAccounts).length && oAuthAccounts[0]?.id;

    if (Array.isArray(events) && events.length === 0) {
      setGoogleAdsEventConfigs(GOOGLE_ADS_DEFAULT_EVENTS);
    } else {
      setGoogleAdsEventConfigs(events);
    }

    setShowPreviewMode(
      !!(events && Object.keys(events)?.length && isAccountConfigsAvailable && isOAuthIdAvailable),
    );
  }, [events]);

  const setIntegrationMethod = (val: string): void => {
    setGoogleAdsAccountConfigs(updateIntegrationMethod(val, googleAdsAccountConfigs));
    setShowPreviewMode(false);
  };

  const deleteAccountConfig = (id: string): void => {
    deleteConfig(id, ANALYTICS_PLATFORM.googleAds.key)
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
      DELETE_CONFIRMATION_TEXTS?.[ANALYTICS_PLATFORM.googleAds.key];
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
    const newConfig = googleAdsAccountConfigs?.[googleAdsAccountConfigs?.length - 1];
    const params = {
      platform: ANALYTICS_PLATFORM.googleAds.key,
      google_ads_conversion_id: creds?.conversionId,
      google_ads_conversion_label: window.btoa(creds?.conversionLabel),
      platform_user_id: creds?.adwordAccountNumber,
      integration_method: newConfig.integrationMethod ?? INTEGRATION_TYPE.backend,
      analytics_auth_account_id: !!Object.keys(oAuthAccounts).length ? oAuthAccounts[0]?.id : '',
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
    setGoogleAdsEventConfigs((prev) => ({
      ...prev,
      [keyName]: toggleState,
    }));
  };

  const saveEventConfigs = (): void => {
    saveEvents(googleAdsEventConfigs, ANALYTICS_PLATFORM.googleAds.key)
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
    const newConfig = addAnalyticsAccount(ANALYTICS_PLATFORM.googleAds.key);
    setGoogleAdsAccountConfigs((prev) => [...prev, newConfig]);
    setShowPreviewMode(false);
  };

  const integrationModalProps = {
    demoContainer: DemoVideo,
    modalIcon: headerIcon,
    stepTexts: GOOGLE_ADS_INTEGRATION_STEPS,
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
          integrationModalProps={integrationModalProps}
          merchantAnalyticsConfigs={googleAdsAccountConfigs}
          oAuthAccountConfigs={oAuthAccounts}
          setIntegrationMethod={setIntegrationMethod}
          deleteAccountConfig={openDeleteConfirmationModal}
        />
      </IntegrationContainer>
      <AnalyticsEvents
        analyticsEvents={GOOGLE_ADS_ANALYTICS_EVENTS}
        header={ANALYTICS_PLATFORM.googleAds.label}
        eventConfigs={googleAdsEventConfigs}
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

export default connect(null, mapDispatchToProps)(GoogleAds);
