import React, { useCallback, useEffect } from 'react';
import { Alert, Text, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useNavigate, useLocation } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import { Environments, ShowNotificationType, Store, User as UserType } from 'common/typings';
import User from 'merchant/models/User';
import { updateSession } from 'merchant/reducers/session';
import { merchantFetch } from 'merchant/utils/ajax';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';

import {
  trackWebsiteRequestStatusBannerLoad,
  trackWebsiteRequestStatusBannerOptionClick,
} from '../tracking';
import { BusinessWebsiteWorkflow, WebsiteLivenssCheckStatus, WebsiteUpdateApiData } from '../types';
import {
  Status,
  alertCTAText,
  getAlertText,
  getUnderReviewETA,
  getWebsiteCount,
  getWebsiteWorkflowStatus,
} from '../utils';

const getLivenessError = (status: WebsiteLivenssCheckStatus | undefined, mainPageUrl: string) => {
  switch (status) {
    case WebsiteLivenssCheckStatus.liveness_check_failed: {
      return {
        title: `The website (${mainPageUrl}) could not be verified at the moment.`,
        description:
          "We couldn't verify if your website is currently live. Please ensure it's live and try again.",
      };
    }
    case WebsiteLivenssCheckStatus.website_request_creation_failed: {
      return {
        title: `Your website (${mainPageUrl}) verification failed due to a system issue on our end.`,
        description:
          'We are facing an internal error while verifying your website. Please try again after sometime.',
      };
    }
    case WebsiteLivenssCheckStatus.website_dns_lookup_failed: {
      return {
        title: `We could not access your website domain (${mainPageUrl}).`,
        description:
          'Please ensure your website domain is correct and publicly accessible. You can open it on a browser (like Chrome) to confirm.',
      };
    }
    case WebsiteLivenssCheckStatus.website_tls_cert_invalid: {
      return {
        title: `Your website's (${mainPageUrl}) security certificate is either invalid or expired.`,
        description:
          'Please renew or install a valid SSL/TLS certificate to make your website secure and accessible.',
      };
    }
    case WebsiteLivenssCheckStatus.website_connection_refused: {
      return {
        title: `Your website (${mainPageUrl}) is not accepting connections right now.`,
        description:
          "Please ensure your server is up and accepting requests. You can check your hosting provider's status or restart your server.",
      };
    }
    case WebsiteLivenssCheckStatus.website_too_many_redirects: {
      return {
        title: `Your website (${mainPageUrl}) is redirecting multiple times.`,
        description:
          'Please fix any infinite redirects on your homepage or root URL. You can test this by accessing the website in incognito mode or using redirect-checker.org.',
      };
    }
    case WebsiteLivenssCheckStatus.website_forbidden_access: {
      return {
        title: `Your website (${mainPageUrl}) is actively blocking our verification request.`,
        description:
          "Please allow access to Razorpay's verification servers by updating your firewall or server access rules.",
      };
    }
    case WebsiteLivenssCheckStatus.website_unrecognized_tls_name: {
      return {
        title: `The server does not recognise the website domain (${mainPageUrl}) during a secure connection.`,
        description:
          'Please ensure that the domain name in your SSL/TLS certificate matches the one you add here. Ensure your server supports SNI (Server Name Indication).',
      };
    }
    case WebsiteLivenssCheckStatus.website_timeout: {
      return {
        title: `Your website (${mainPageUrl}) is taking too long to respond.`,
        description:
          'Please ensure the website is live and responsive. You can check for hosting issues, high load times or network instability.',
      };
    }
    case WebsiteLivenssCheckStatus.website_connection_reset: {
      return {
        title: `Your website (${mainPageUrl}) closed the connection unexpectedly.`,
        description:
          'Please check your server logs to identify connection drops. Ensure it accepts external traffic and does not auto-block requests.',
      };
    }
    case WebsiteLivenssCheckStatus.website_network_unreachable: {
      return {
        title: `We could not reach your website (${mainPageUrl}) from our network.`,
        description:
          'Your website might be behind a restricted firewall or IP block. Please ensure it is publicly accessible from all locations.',
      };
    }
    case WebsiteLivenssCheckStatus.website_internal_server_error: {
      return {
        title: `Your website (${mainPageUrl}) is returning an internal server error.`,
        description:
          'Please check your hosting/server logs for HTTP 500 errors and resolve any underlying issues.',
      };
    }
    case WebsiteLivenssCheckStatus.website_service_unavailable: {
      return {
        title: `Your website (${mainPageUrl}) is temporarily unavailable.`,
        description:
          'This is usually due to server overload or maintenance. Please try again once the website is back online.',
      };
    }
    case WebsiteLivenssCheckStatus.website_not_found: {
      return {
        title: `Your website (${mainPageUrl}) page was not found.`,
        description: 'Please verify that the entered URL exists and is available to the public.',
      };
    }
    case WebsiteLivenssCheckStatus.website_bad_request: {
      return {
        title: `Your website (${mainPageUrl}) request was malformed.`,
        description:
          'Ensure the website URL is correctly formatted (e.g., starts with http:// or https:// and contains a valid domain).',
      };
    }
    case WebsiteLivenssCheckStatus.website_unauthorized: {
      return {
        title: `Your website (${mainPageUrl}) requires authentication to view.`,
        description:
          'Please provide a publicly accessible URL that does not require a login, OTP or credentials.',
      };
    }
    default: {
      return {
        title: `The website (${mainPageUrl}) could not be verified at the moment.`,
        description:
          "We couldn't verify that your website is currently live. Please ensure it's live and try again.",
      };
    }
  }
};

const NegativeKeywordError = ({
  negativeKeywordData,
  mainPageUrl,
}: {
  negativeKeywordData: string[];
  mainPageUrl: string;
}) => {
  if (negativeKeywordData.length > 0) {
    const errorMsg = `Please review and update your website to align with our content policies. Content related to topics such as ${negativeKeywordData
      .slice(0, 5)
      .map((word) => `"${word}"`)
      .join(', ')} may not be allowed.`;
    return (
      <Alert
        color="negative"
        isDismissible={false}
        isFullWidth
        title={`Some content on your website (${mainPageUrl}) doesn't comply with Razorpay's policies.`}
        description={errorMsg}
      />
    );
  }
  return null;
};

interface PrimaryWebsiteWorkflowStatusProps {
  websiteUpdateData: WebsiteUpdateApiData;
  businessWebsiteWorkflow: BusinessWebsiteWorkflow;
  onFixMissingPages: () => void;
  onReplyClick: () => void;
  org: { business_name: string };
  user: UserType;
  mode: Environments;
  updateSession: (fieldsToOverride: any) => Promise<void>;
  showNotification: ShowNotificationType;
}

const PrimaryWebsiteWorkflowStatus: React.FC<PrimaryWebsiteWorkflowStatusProps> = ({
  websiteUpdateData,
  businessWebsiteWorkflow,
  onFixMissingPages,
  onReplyClick,
  org,
  user,
  mode,
  updateSession,
  showNotification,
}) => {
  const location = useLocation();
  const navigate = useNavigate();

  const { rejection_reason_message, needs_clarification: needs_clarification_message } =
    businessWebsiteWorkflow ?? {};

  const { status, analyticsStatus } = getWebsiteWorkflowStatus({
    businessWebsiteWorkflow,
    websiteUpdateData,
  });

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const mainPageUrl = websiteUpdateData?.main_page_url ?? '';
  const livenessCheckStatus = websiteUpdateData?.liveness_check_status ?? '';
  const title = getAlertText({
    status,
    mainPageUrl,
    websiteUpdateData,
  });
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const ctaText: string = alertCTAText[status] || '';

  useEffect(() => {
    if (status) {
      trackWebsiteRequestStatusBannerLoad({
        contentDisplayed: title,
        statusOfVerification: status,
        analyticsStatus,
        websiteCount: getWebsiteCount(user),
        newWebsiteLink: websiteUpdateData?.main_page_url,
        verificationStatus: websiteUpdateData?.website_verification_stage,
      });
      if (status === Status.Success) {
        updateMainPageUrl();
      }
    }
  }, [status, title]);

  async function updateMainPageUrl() {
    try {
      const response = await merchantFetch({
        url: 'merchant/activation',
        mode,
      });

      if (response.data) {
        const {
          business_website,
          merchant: { has_key_access },
        } = response.data;
        const newUser = new User({
          ...user,
          business_website,
          has_key_access,
          merchant: {
            ...user.merchant,
            has_key_access,
          },
        });
        updateSession({
          user: newUser,
        });
      } else {
        showNotification({
          type: 'error',
          message: 'Something went wrong. Try again later',
        });
      }
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Try again later',
      });
    }
  }

  const trackBannerClick = (actionText: string) => {
    trackWebsiteRequestStatusBannerOptionClick({
      contentDisplayed: title,
      statusOfVerification: status,
      analyticsStatus,
      websiteCount: getWebsiteCount(user),
      newWebsiteLink: websiteUpdateData?.main_page_url,
      followupAction: actionText,
    });
  };

  const onClickGenerateAPIKeys = useCallback(() => {
    navigate(ROUTES_INFO.API_KEYS);
  }, []);

  useEffect(() => {
    // If landed on this page with query params for opening modals
    const queryParams = new URLSearchParams(location.search);
    let deleteUrlQuery = '';
    if (queryParams.get('clarificationModalVisible') === 'true') {
      // open needs clarification modal
      onReplyClick();
      deleteUrlQuery = 'clarificationModalVisible';
    } else if (queryParams.get('bvsModalVisible') === 'true') {
      // open bvs website pages update modal
      onFixMissingPages();
      deleteUrlQuery = 'bvsModalVisible';
    }

    // Remove the query params after the page load is successfull
    if (deleteUrlQuery) {
      queryParams.delete(deleteUrlQuery);
      const newUrl = queryParams.toString()
        ? `/app${location.pathname}?${queryParams.toString()}`
        : `/app${location.pathname}`;
      window.history.replaceState(null, '', newUrl);
    }
  }, [location.search]);

  if (status === Status.Success) {
    return (
      <Alert
        color="positive"
        isDismissible={false}
        isFullWidth
        title={title}
        description="To start accepting payments, you’ll need to download API keys and integrate them on the website"
        actions={{
          primary: {
            onClick: () => {
              onClickGenerateAPIKeys();
              trackBannerClick(ctaText);
            },
            text: ctaText,
          },
        }}
      />
    );
  }

  if (status === Status.BvsNeedsClarification) {
    return (
      <Box flexDirection="column" display="flex" gap="spacing.4">
        <Alert
          color="negative"
          isDismissible={false}
          isFullWidth
          title={title}
          description="Kindly update and add your website details"
          actions={{
            primary: {
              onClick: () => {
                onFixMissingPages();
                trackBannerClick(ctaText);
              },
              text: ctaText,
            },
          }}
        />
        <NegativeKeywordError
          negativeKeywordData={
            websiteUpdateData?.website_verification_stage?.negative_keyword_data ?? []
          }
          mainPageUrl={mainPageUrl}
        />
      </Box>
    );
  }

  if (status === Status.BvsInProgress) {
    return (
      <Alert
        color="notice"
        isDismissible={false}
        isFullWidth
        title={title}
        description="We’ll verify your details and share an update within 10 minutes"
      />
    );
  }

  if (status === Status.WorkflowInReview) {
    return (
      <Box flexDirection="column" display="flex" gap="spacing.4">
        <Alert
          color="notice"
          isDismissible={false}
          isFullWidth
          title={title}
          description={`We’ll verify your details and share an update by ${getUnderReviewETA({
            offset: 48 * 60 * 60 * 1000,
          })}`}
        />
        <NegativeKeywordError
          negativeKeywordData={
            websiteUpdateData?.website_verification_stage?.negative_keyword_data ?? []
          }
          mainPageUrl={mainPageUrl}
        />
      </Box>
    );
  }

  if (status === Status.WorkflowNeedsClarification) {
    return (
      <Alert
        title={title}
        description={
          <Text color="surface.text.gray.subtle" wordBreak="break-word">
            <b>From {org.business_name} support:&nbsp;</b>
            {`"${needs_clarification_message}"`}
          </Text>
        }
        isDismissible={false}
        actions={{
          primary: {
            text: ctaText,
            onClick: () => {
              onReplyClick();
              trackBannerClick(ctaText);
            },
          },
        }}
        isFullWidth
        color="negative"
      />
    );
  }

  if (status === Status.Rejected && rejection_reason_message) {
    return (
      <Alert
        color="negative"
        isDismissible={false}
        isFullWidth
        title={title}
        description={
          <Text color="surface.text.gray.subtle" wordBreak="break-word">
            <b>From {org.business_name} support:&nbsp;</b>
            {`"${rejection_reason_message}"`}
          </Text>
        }
      />
    );
  }

  if (status === Status.WebsiteUpdateFailed) {
    return (
      <Alert
        color="negative"
        isDismissible={false}
        isFullWidth
        title={title}
        description={
          <Text color="surface.text.gray.subtle" wordBreak="break-word">
            Please try adding the website again. If the issue persists, reach out to our support
            team for assistance.
          </Text>
        }
      />
    );
  }

  if (status === Status.WebsiteLivenessFailed) {
    const { title: livenessTitle, description: livenessDescription } = getLivenessError(
      livenessCheckStatus,
      mainPageUrl,
    );
    return (
      <Alert
        color="negative"
        isDismissible={false}
        isFullWidth
        title={livenessTitle || title}
        description={
          <Text color="surface.text.gray.subtle" wordBreak="break-word">
            {livenessDescription ||
              'Please submit your request after the website is fully live and functional.'}
          </Text>
        }
      />
    );
  }

  return null;
};
const mapStateToProps = (state: Store) => ({
  org: state.session.org,
  user: state.session.user,
  mode: state.session.mode,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification: showNotificationReducer,
      updateSession: bindActionCreators(updateSession, dispatch),
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(PrimaryWebsiteWorkflowStatus);
