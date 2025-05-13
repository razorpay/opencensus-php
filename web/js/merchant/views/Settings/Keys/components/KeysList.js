import React from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';

import EditWebsiteDetailsModal from 'merchant/views/Account/Profile/components/EditWebsiteDetailsModal';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { useTrigger2Fa } from 'common/ui/TwoFactorVerification/hooks';
import { useSplitzService } from 'common/splitz';
import { shouldShowBusinessWebsiteV2 } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/utils';
import { useNavigate } from 'react-router-dom';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { isExperimentEnabled } from '@libs/shared-utils';

const KeysListItem = (props) => {
  const mode = props.mode;
  const { id, created_at, expired_at } = props.apiKey;

  const context = useTwoFactorVerificationContext();

  const onRegenerateKeys = () => {
    selfServeTrackInitiate({
      selfServeAction: 'API Key Regenerated',
      page: 'API Keys',
      screen: props?.user?.isAccountAndSettingsRevampEnabled ? 'Account & Settings' : 'Settings',
    });
    analyticsTrack({
      objectName: `regenerate ${mode} key`,
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'API Keys',
        apiKeyCreatedAt: created_at,
        apiKeyExpiry: expired_at ? expired_at : 'never',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    if (props.shouldTrigger2Fa) {
      return context.criticalFlow({
        enforceVerifyOtp: true,
        modes: ['test', 'live'],
        onUserTwoFaVerified: () => {
          props.showRollKeyModal({ id });
        },
      });
    } else {
      if (props.isSkip2FAEnabled) {
        return props.showRollKeyModal({ id });
      }
      return context.criticalFlow({
        modes: ['live'],
        onUserTwoFaVerified: () => {
          props.showRollKeyModal({ id });
        },
      });
    }
  };

  return (
    <tr>
      <td>{id}</td>
      <td>
        <Time value={created_at} format="MMM Do, YYYY hh:mm:ss A" />
      </td>
      <td>{expired_at ? <Time value={expired_at} format="MMM Do, YYYY hh:mm:ss A" /> : 'Never'}</td>
      <td>
        {expired_at ? (
          'None'
        ) : (
          <div className="row-action">
            <button className="btn btn-xs btn-primary" onClick={onRegenerateKeys}>
              <i className="i i-refresh" />
              <span data-test="regenerate-api-key">Regenerate {mode} Key</span>
            </button>
          </div>
        )}
      </td>
    </tr>
  );
};

export default connect(null, { openModal, closeModal })((props) => {
  const {
    user,
    mode,
    keys,
    isLoading,
    merchantId,
    hasKeyAccess,
    businessWebsite,
    showRollKeyModal = () => {},
    generateKey = () => {},
    isWebsiteInWorkflow,
    onWebsiteAdd,
  } = props;
  const shouldTrigger2Fa = useTrigger2Fa();
  const { criticalFlow } = useTwoFactorVerificationContext();
  const {
    abExperiments: { business_website_v2_automation, skip_2fa_for_protected_flows },
  } = useSplitzService();
  const isSkip2FAEnabled = isExperimentEnabled(skip_2fa_for_protected_flows);

  const navigate = useNavigate();

  const shouldShowV2 = shouldShowBusinessWebsiteV2(business_website_v2_automation, user);

  const params = {
    merchantId,
  };

  const handleAddUpdateCTA = () => {
    shouldShowV2
      ? navigate(ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS)
      : props.openModal({
          size: 'small',
          component: (
            <EditWebsiteDetailsModal onClose={props.closeModal} onWebsiteAdd={onWebsiteAdd} />
          ),
        });
  };

  function handleKeyGeneration() {
    if (shouldTrigger2Fa) {
      criticalFlow({
        enforceVerifyOtp: true,
        onUserTwoFaVerified: () => {
          // close the 2FA modal and generate the api key
          closeModal();
          generateKey(params, false);
        },
      });
    } else {
      generateKey(params, false);
    }
  }

  return (
    <div>
      <div className="table-responsive">
        <table className="table table-hover">
          <thead>
            <tr data-test="key-id-header-row">
              <th>Key Id</th>
              <th>Created At</th>
              <th>Expiry</th>
              <th>Action</th>
            </tr>
          </thead>
          <TableBody
            isLoading={isLoading}
            colSpan={4}
            rows={keys}
            emptyTableRow={
              <tr>
                <td className="text-center empty-table" colSpan={4}>
                  {mode === 'Test' || hasKeyAccess ? (
                    <React.Fragment>
                      {!hasKeyAccess && (
                        <p>
                          You can generate API keys in Test Mode.
                          <br />
                          For generating keys in Live Mode, you need to provide your business
                          website/app details while filling the activation form.
                        </p>
                      )}
                      <button
                        className="btn btn-primary"
                        onClick={handleKeyGeneration}
                        data-test="generate-api-key"
                      >
                        Generate {mode} Key
                      </button>
                    </React.Fragment>
                  ) : !businessWebsite && !isWebsiteInWorkflow ? (
                    <div>
                      <p>{`Please provide your Business Website/App details in order to generate API keys in Live Mode`}</p>
                      <button className="btn btn-primary" onClick={handleAddUpdateCTA}>
                        Add Website/App URL
                      </button>
                    </div>
                  ) : (
                    <div>
                      The website/app details that you have provided are under review. You can
                      generate API keys once the details are approved.
                    </div>
                  )}
                </td>
              </tr>
            }
          >
            {keys.map((key) => (
              <KeysListItem
                user={user}
                key={key.id}
                apiKey={key}
                mode={mode}
                showRollKeyModal={showRollKeyModal}
                shouldTrigger2Fa={shouldTrigger2Fa}
                isSkip2FAEnabled={isSkip2FAEnabled}
              />
            ))}
          </TableBody>
        </table>
      </div>
    </div>
  );
});
