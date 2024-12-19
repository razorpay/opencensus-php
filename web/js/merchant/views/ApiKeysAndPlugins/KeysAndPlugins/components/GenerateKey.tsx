import React, { useMemo, useState, useReducer, useEffect } from 'react';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import Alert from 'common/new-ui/Alert';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import Time from 'common/ui/Time';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { useTrigger2Fa } from 'common/ui/TwoFactorVerification/hooks';
import copyToClipboard from 'common/utils/copyToClipboard';
import fileDownload from 'common/utils/file-download';
import * as KeyActions from 'merchant/reducers/keys';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import ajax from 'merchant/utils/ajax';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { useGetWebsiteUpdate } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useGetWebsiteData';
import {
  Status as WebsiteStatusEnum,
  getWebsiteWorkflowStatus,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils';
import {
  WebsiteApiTimeline,
  WebsiteApiModeSwitchFooter,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/WebsiteApiTimeline';
import { INTEGRATION_TITLE } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import {
  trackCTAClick,
  trackAsyncResult,
  trackKeyCopy,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/events';
import {
  KeyField,
  MerchantProduct,
  Platform,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import { getLatestKey } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/utils';
import RollKey from 'merchant/views/Settings/Keys/components/RollKey';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const copyState: Record<KeyField, boolean> = { [KeyField.ID]: false, [KeyField.SECRET]: false };

const copyReducer = (
  state: Record<KeyField, boolean>,
  action: { type: 'set' | 'reset'; field: KeyField },
): Record<KeyField, boolean> => {
  switch (action.type) {
    case 'set':
      return { ...state, [action.field]: true };
    case 'reset':
      return { ...state, [action.field]: false };
    default:
      return state;
  }
};

export interface GenerateKeyProps {
  selectedPlatform: Platform;
  product: MerchantProduct;
  switchMode: (mode: string, callback?: () => void) => void;
  user: any;
  keys: any;
  mode: string;
  merchantId: string;
  generateKey: any;
  openModal: any;
  closeModal: any;
  showNotification: any;
  workflows: Record<string, any>;
  fetchWorkflowStatus: any;
  merchantLoginMode: string;
}

const GenerateKey = ({
  // from parent
  selectedPlatform,
  product,
  switchMode,
  // state from redux
  user,
  keys: keysState,
  mode,
  merchantId,
  // actions from redux
  generateKey,
  openModal,
  closeModal,
  showNotification,
  workflows,
  fetchWorkflowStatus,
  merchantLoginMode,
}: GenerateKeyProps) => {
  const navigate = useNavigate();
  const splitz = useSplitzService();

  const isFtux1Point5Enabled = isExperimentEnabled(splitz?.abExperiments?.show_ftux_V_1Point5);

  const { data: websiteUpdateData, isLoading: isWebsiteUpdateDataFetching } = useGetWebsiteUpdate(
    merchantLoginMode,
    !!isFtux1Point5Enabled,
  );

  const websiteWorkflowData = workflows?.[WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE];

  const { status: websiteReviewStatus } = getWebsiteWorkflowStatus({
    businessWebsiteWorkflow: websiteWorkflowData,
    websiteUpdateData: websiteUpdateData ?? {},
  });

  const [currentKeySecret, setCurrentKeySecret] = useState<string | null>(null);
  const [isKeyGenerating, setIsKeyGenerating] = useState(false);
  const [copied, dispatch] = useReducer(copyReducer, copyState);

  const shouldTrigger2Fa = useTrigger2Fa();
  const context = useTwoFactorVerificationContext();
  const { keys } = keysState;
  const platformURL = user[selectedPlatform];

  const trackProps = {
    paymentChannel: INTEGRATION_TITLE[selectedPlatform],
    product,
  };

  //* For getting the latest generated key
  const latestKey = useMemo(() => getLatestKey(keys), [keys]);

  const downloadKey = (): Promise<void | Record<string, unknown>> => {
    trackCTAClick('Download Key', { paymentChannel: INTEGRATION_TITLE[selectedPlatform], product });

    return ajax({
      url: '/keys/csv',
      method: 'post',
      appendModeInQueryParam: true,
      data: {
        id: latestKey?.id,
        secret: currentKeySecret,
      },
    })
      .then((data) => {
        trackAsyncResult('Download Key', { status: 'Success', ...trackProps });
        fileDownload(data, 'rzp-key.csv');
        showNotification({
          type: 'success',
          message: 'Key Downloaded Successfully',
        });
        setCurrentKeySecret(null);
        closeModal();
      })
      .catch(({ errors }) => {
        trackAsyncResult('Download Key', {
          status: 'Failure',
          failureReason: errors?.[0],
          ...trackProps,
        });
        showNotification({
          type: 'error',
          message: errors?.[0],
        });
      });
  };

  const onCopy = (field: KeyField, text) => {
    if (copied[field]) return;

    dispatch({ type: 'set', field });
    showNotification({
      type: 'success',
      message: `Key ${field} copied`,
      closeTimeout: 2000,
    });
    copyToClipboard(text);
    trackKeyCopy({ key: field, ...trackProps });

    // reset copy after two seconds
    setTimeout(() => {
      dispatch({ type: 'reset', field });
    }, 2000);
  };

  const onGenerateKey = (params, isRegenerating?: boolean) => {
    setIsKeyGenerating(true);
    if (!isRegenerating) {
      trackCTAClick('Generate Key', trackProps);
    }
    return generateKey(params)
      .then((response) => {
        const key = response.new || response;

        showNotification({
          type: 'success',
          message: 'New Key Generated Successfully',
        });

        trackAsyncResult(isRegenerating ? 'Generate New Key' : 'Generate Key', {
          status: 'Success',
          ...trackProps,
        });
        closeModal();
        setCurrentKeySecret(key?.secret);
      })
      .catch(({ errors }) => {
        trackAsyncResult(isRegenerating ? 'Generate New Key' : 'Generate Key', {
          status: 'Failure',
          failureReason: errors?.[0],
          paymentChannel: INTEGRATION_TITLE[selectedPlatform],
          product,
        });
        showNotification({
          type: 'error',
          message: errors?.[0],
        });
      })
      .finally(() => {
        setIsKeyGenerating(false);
      });
  };

  const showRollKeyModal = (params: { id: string } | null = null): void => {
    openModal({
      size: 'small',
      component: (
        <RollKey
          params={params}
          merchantId={merchantId}
          // Regeneration flow for onGenerateKey function
          generateKey={(params) => onGenerateKey(params, true)}
          onClose={() => setIsKeyGenerating(false)}
        />
      ),
    });
  };

  const regenerateKey = () => {
    setIsKeyGenerating(true);
    trackCTAClick('Generate New Key', trackProps);

    if (shouldTrigger2Fa) {
      return context.criticalFlow({
        enforceVerifyOtp: true,
        modes: ['live', 'test'],
        onUserTwoFaVerified: () => {
          showRollKeyModal({ id: latestKey?.id });
        },
        onFlowTermination: () => {
          setIsKeyGenerating(false);
        },
      });
    } else {
      return context.criticalFlow({
        modes: ['live'],
        onUserTwoFaVerified: () => {
          showRollKeyModal({ id: latestKey?.id });
        },
        onFlowTermination: () => {
          setIsKeyGenerating(false);
        },
      });
    }
  };

  const handleNavigateToWebsite = (navigateParams?: Record<string, string>) => {
    navigate(
      `/onboarding/business-website-details${
        navigateParams ? `?${new URLSearchParams(navigateParams).toString()}` : ''
      }`,
    );
  };

  useEffect(() => {
    if (isFtux1Point5Enabled) {
      fetchWorkflowStatus?.(WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE);
    }
  }, [isFtux1Point5Enabled]);

  return (
    <>
      <div className="keys-plugins-step__heading">
        {latestKey && !currentKeySecret && !isFtux1Point5Enabled ? (
          <>API key downloaded &#11015;</>
        ) : (
          'Get API key'
        )}
      </div>
      <div
        className="keys-plugins-step__content"
        style={{ gap: isFtux1Point5Enabled ? '16px' : '32px' }}
      >
        {mode.toLowerCase() === 'live' && isFtux1Point5Enabled && (
          <WebsiteApiTimeline
            status={websiteReviewStatus}
            latestKey={latestKey}
            handleNavigateToWebsite={handleNavigateToWebsite}
            loading={websiteWorkflowData?.loading || isWebsiteUpdateDataFetching}
          />
        )}

        {mode.toLowerCase() === 'live' &&
        platformURL &&
        !user?.has_key_access &&
        !isFtux1Point5Enabled ? (
          <div>
            <strong>You can generate API keys in Test Mode</strong>
            <br />
            <br />
            The website/app details that you have provided are under review. You can generate API
            keys here once the details are approved.
          </div>
        ) : latestKey ? (
          <>
            {currentKeySecret && (
              <Alert.Warning iconBefore="i i-triangle-alert alert-icon">
                <strong className="alert-text">Download and store your key safely</strong>
                <div className="alert-text">
                  If you leave or refresh this page, you cannot view the key secret again.
                </div>
              </Alert.Warning>
            )}

            <div className="keys-plugins-section__field-group">
              <div className="keys-plugins-section__label">{mode} Key ID</div>
              <div className="keys-plugins-section__field">
                <div data-testid="key-id">{latestKey?.id}</div>
                <i
                  className={`i ${copied[KeyField.ID] ? 'i-done-all' : 'i-copy'}`}
                  role="button"
                  data-testid={copied[KeyField.ID] ? 'copied-id' : 'copy-id'}
                  onClick={() => onCopy(KeyField.ID, latestKey?.id)}
                />
              </div>
            </div>
            <div className="keys-plugins-section__field-group">
              <div className="keys-plugins-section__label"> {mode} Key Secret</div>
              <div className="keys-plugins-section__field">
                {currentKeySecret ? (
                  <>
                    <div data-tesetid="key-secret">{currentKeySecret}</div>
                    <i
                      className={`i ${copied[KeyField.SECRET] ? 'i-done-all' : 'i-copy'}`}
                      role="button"
                      data-testid={copied[KeyField.SECRET] ? 'copied-secret' : 'copy-secret'}
                      onClick={() => onCopy(KeyField.SECRET, currentKeySecret)}
                    />
                  </>
                ) : (
                  <div>xxxxxxxxxxxxxxxxxxxxxxxx</div>
                )}
              </div>
              {!currentKeySecret && (
                <div className="keys-plugins-section__helper-text">
                  You’ll not be able to view the key secret again for security. If you’ve lost it or
                  need a new key, request another below.
                </div>
              )}
            </div>

            {!currentKeySecret && (
              <div className="key-details">
                <div className="keys-plugins-section__field-group">
                  <div className="keys-plugins-section__label">Created on</div>
                  <Time value={latestKey?.created_at} format="MMM Do, YYYY" />
                </div>
                <div className="keys-plugins-section__field-group">
                  <div className="keys-plugins-section__label">Expiry on</div>
                  {latestKey?.expired_at ? (
                    <Time value={latestKey?.expired_at} format="MMM Do, YYYY" />
                  ) : (
                    'Never'
                  )}{' '}
                </div>
              </div>
            )}

            {currentKeySecret ? (
              //* if key is generated in this session
              <button className="btn btn-primary btn-block" onClick={downloadKey}>
                Download keys
                <i className="i i-download-blue" />
              </button>
            ) : (
              ///* if key was generated previously
              <button
                className="btn btn-outline btn-block"
                onClick={regenerateKey}
                disabled={isKeyGenerating}
              >
                {isKeyGenerating ? (
                  'Generating...'
                ) : (
                  <>
                    {' '}
                    Generate new key <i className="i i-refresh" />
                  </>
                )}
              </button>
            )}
          </>
        ) : (
          // no key is generated yet
          <button
            className="btn btn-primary btn-block"
            onClick={(e) => {
              if (shouldTrigger2Fa) {
                context.criticalFlow({
                  enforceVerifyOtp: true,
                  modes: ['live', 'test'],
                  onUserTwoFaVerified: () => {
                    // close the 2FA modal and generate the api key
                    closeModal();
                    onGenerateKey(e);
                  },
                });
              } else {
                onGenerateKey(e);
              }
            }}
            disabled={
              isKeyGenerating ||
              (mode.toLowerCase() === 'live' &&
                isFtux1Point5Enabled &&
                (websiteReviewStatus !== WebsiteStatusEnum.Success ||
                  websiteWorkflowData?.loading ||
                  isWebsiteUpdateDataFetching))
            }
          >
            {isKeyGenerating ? 'Generating...' : 'Generate key'}
          </button>
        )}

        {mode.toLowerCase() === 'live' &&
          isFtux1Point5Enabled &&
          websiteReviewStatus !== WebsiteStatusEnum.Success &&
          !websiteWorkflowData?.loading &&
          !isWebsiteUpdateDataFetching && <WebsiteApiModeSwitchFooter switchMode={switchMode} />}
      </div>
    </>
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
    keys: state.keys,
    mode: state.session.modeFormatted,
    merchantId: state.session.user?.current,
    workflows: state.workflows,
    merchantLoginMode: state.session.mode,
  }),
  (dispatch) =>
    bindActionCreators(
      {
        ...KeyActions,
        ...ModalActions,
        ...NotificationsActions,
        fetchWorkflowStatus: fetchWorkflowStatusReducer,
      },
      dispatch,
    ),
)(GenerateKey);
