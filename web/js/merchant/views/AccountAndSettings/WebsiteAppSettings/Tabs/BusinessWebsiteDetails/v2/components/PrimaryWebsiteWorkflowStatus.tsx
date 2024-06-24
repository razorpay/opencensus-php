import React, { useCallback, useEffect } from 'react';
import { Alert, Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
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
  alertText,
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
  const navigate = useNavigate();

  const { rejection_reason_message } = businessWebsiteWorkflow ?? {};

  const { status } = getWebsiteWorkflowStatus({
    businessWebsiteWorkflow,
    websiteUpdateData,
  });

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const title: string | undefined = alertText[status];
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const ctaText: string = alertCTAText[status] || '';

  useEffect(() => {
    if (status) {
      trackWebsiteRequestStatusBannerLoad({
        contentDisplayed: title,
        statusOfVerification: status,
        websiteCount: getWebsiteCount(user),
        newWebsiteLink: websiteUpdateData?.main_page_url,
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
      websiteCount: getWebsiteCount(user),
      newWebsiteLink: websiteUpdateData?.main_page_url,
      followupAction: actionText,
    });
  };

  const onClickGenerateAPIKeys = useCallback(() => {
    navigate(ROUTES_INFO.API_KEYS);
  }, []);

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
        })}`}
      />
    );
  }

  if (status === Status.WorkflowNeedsClarification) {
    return (
      <Alert
        title={title}
        description="To accept payments and for faster verification, kindly update the details"
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

  if (status === Status.Rejected) {
    return (
      <Alert
        color="negative"
        isDismissible={true}
        isFullWidth
        title={title}
        description={
          <Box display="flex" flexDirection="row">
            <Text weight="medium" color="surface.text.gray.subtle">
              From {org.business_name} support:&nbsp;
            </Text>
            <Text color="surface.text.gray.subtle">&quot;{rejection_reason_message}&quot;</Text>
          </Box>
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
