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

const KeysListItem = (props) => {
  const mode = props.mode;
  const { id, created_at, expired_at } = props.apiKey;

  const context = useTwoFactorVerificationContext();

  const onRegenerateKeys = () => {
    selfServeTrackInitiate({
      selfServeAction: 'API Key Regenerated',
      page: 'API Keys',
      screen: 'Settings',
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

    return context.criticalFlow({
      modes: ['live'],
      onUserTwoFaVerified: () => {
        props.showRollKeyModal({ id });
      },
    });
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
          <div class="row-action">
            <button class="btn btn-xs btn-primary" onClick={onRegenerateKeys}>
              <i class="i i-refresh" />
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

  const params = {
    merchantId,
  };

  return (
    <div>
      <div class="table-responsive">
        <table class="table table-hover">
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
                <td class="text-center empty-table" colSpan={4}>
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
                        class="btn btn-primary"
                        onClick={() => {
                          generateKey(params);
                        }}
                        data-test="generate-api-key"
                      >
                        Generate {mode} Key
                      </button>
                    </React.Fragment>
                  ) : !businessWebsite && !isWebsiteInWorkflow ? (
                    <div>
                      <p>{`Please provide your Business Website/App details in order to generate API keys in Live Mode`}</p>
                      <button
                        class="btn btn-primary"
                        onClick={() =>
                          props.openModal({
                            size: 'small',
                            component: (
                              <EditWebsiteDetailsModal
                                onClose={props.closeModal}
                                onWebsiteAdd={onWebsiteAdd}
                              />
                            ),
                          })
                        }
                      >
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
                key={key.id}
                apiKey={key}
                mode={mode}
                showRollKeyModal={showRollKeyModal}
              />
            ))}
          </TableBody>
        </table>
      </div>
    </div>
  );
});
