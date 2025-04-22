import React, { useCallback, useEffect } from 'react';
import { Alert, Text } from '@razorpay/blade/components';
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
import { BusinessWebsiteWorkflow, WebsiteUpdateApiData } from '../types';
import {
  Status,
  alertCTAText,
  getAlertText,
  getUnderReviewETA,
  getWebsiteCount,
  getWebsiteWorkflowStatus,
} from '../utils';

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
      <Alert
        color="notice"
        isDismissible={false}
        isFullWidth
        title={title}
        description={`We’ll verify your details and share an update by ${getUnderReviewETA({
          offset: 48 * 60 * 60 * 1000,
        })}.`}
      />
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
    return (
      <Alert
        color="negative"
        isDismissible={false}
        isFullWidth
        title={title}
        description={
          <Text color="surface.text.gray.subtle" wordBreak="break-word">
            Please submit your request after the website is fully live and functional.
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
