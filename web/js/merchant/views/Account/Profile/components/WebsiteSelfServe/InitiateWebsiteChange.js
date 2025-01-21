import { useContext, useState } from 'react';
import {
  Modal as BladeModal,
  ModalBody as BladeModalBody,
  ModalHeader as BladeModalHeader,
  ModalFooter as BladeModalFooter,
  Box,
  Button,
  Alert,
  List,
  ListItem,
} from '@razorpay/blade/components';
import wwwImg from 'assets/www.svg';

import { zIndicesMap } from 'common/constant';
import { useMobile } from 'common/hooks/useMobile';
import ModalHeader from 'common/ui/ModalHeader';
import TwoFactorVerificaionContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';

import { FLOWS } from './Constants';
import UpdateWebsiteDetails from './UpdateWebsiteDetails';

function InitiateWebsiteChange(props) {
  const context = useContext(TwoFactorVerificaionContext);
  const [isBladeModalOpen, setIsBladeModalOpen] = useState(true);
  const isMobile = useMobile(mobileBreakoints);

  const onContactVerified = () => {
    const { user } = props;

    if (props.openNewModal && typeof props.openNewModal === 'function') {
      props.openNewModal();
      return;
    }

    props.openModal({
      size: 'small',
      component: <UpdateWebsiteDetails flowType={props.flowType} />,
      overlayStyles: { display: 'block' },
    });

    // Avoid tracking for additional website flow
    if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) return;

    analyticsTrack({
      objectName: `Website 2fa result`,
      actionName: '2FA request',
      screen: 'My account',
      properties: {
        flow: user.has_key_access ? 'Website edit' : 'Website add',
        result: 'Success',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const onProceedClick = () => {
    const { user } = props;
    let analyticsObject;

    context.criticalFlow({
      modes: ['test', 'live'],
      onUserTwoFaVerified: () => {
        onContactVerified();
      },
      onWrongOtpCallback: () => {
        analyticsTrack({
          objectName: `Website 2fa result`,
          actionName: '2FA request',
          screen: 'My account',
          properties: {
            flow: user.has_key_access ? 'Website edit' : 'Website add',
            result: 'Failure',
            reason: 'Wrong OTP submitted',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
    });

    // Avoid tracking for additional website flow
    if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) return;

    // Edit flow
    if (user.has_key_access) {
      analyticsObject = {
        objectName: `Bmc acknowledged`,
        actionName: 'Proceed clicked',
        screen: 'My account',
        properties: {
          flow: 'Website edit',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    } else {
      // Add flow
      analyticsObject = {
        objectName: `Bmc acknowledged`,
        actionName: 'Proceed clicked',
        screen: 'My account',
        properties: {
          flow: 'Website add',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    }

    analyticsTrack(analyticsObject);
  };

  const title =
    props.flowType === FLOWS.ADDITIONAL_WEBSITE
      ? `Add Website/App`
      : props.user.has_key_access
      ? 'Update Website/App'
      : 'Add new Website/App';

  // user should have key access, user should not fully activated and user has payment enabled
  if (props.user.has_key_access && !props.user.isAccepted && props.user.isActivated) {
    return (
      <div class="website-self-serve-initiate-modal">
        <ModalHeader
          title="You can add a new website/app URL later"
          onCloseClick={props.closeModal}
        />
        <p class="amp-content">
          We're currently reviewing the website/app you've already shared. Once the review is
          complete, you'll be able to add a new website/app without any hassle.
        </p>
      </div>
    );
  }

  if (props.isBladeRevamp) {
    return (
      <BladeModal
        isOpen={isBladeModalOpen}
        onDismiss={() => {
          props.closeModal();
          setIsBladeModalOpen(false);
        }}
        size="large"
        zIndex={zIndicesMap.modalOverlay}
      >
        <BladeModalHeader title={title} />
        <BladeModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
          <Box
            display="flex"
            flexDirection="column"
            minHeight={isMobile ? 'none' : '440px'}
            justifyContent="center"
            alignItems="center"
            padding="spacing.6"
          >
            <Box display="flex" justifyContent="center" alignItems="center" margin="spacing.6">
              <img height="148px" width="148px" src={wwwImg} />
            </Box>
            <Alert
              title="Please note:"
              description={
                <List variant="ordered">
                  <ListItem>
                    The product/services that you are selling on the website/app should fall under “
                    {titleCase(props.user.business_category)}” category.
                  </ListItem>
                  <ListItem>
                    If your website/app belongs to a different category, please create a new
                    Razorpay account.
                  </ListItem>
                </List>
              }
              color="information"
              isDismissible={false}
              isFullWidth
            />
          </Box>
        </BladeModalBody>
        <BladeModalFooter>
          <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
            <Button
              variant="secondary"
              onClick={() => {
                props.closeModal();
                setIsBladeModalOpen(false);
              }}
            >
              Cancel
            </Button>
            <Button onClick={onProceedClick}>Proceed to update website/app</Button>
          </Box>
        </BladeModalFooter>
      </BladeModal>
    );
  }

  return (
    <div class="website-self-serve-initiate-modal">
      <ModalHeader title={title} onCloseClick={props.closeModal} />
      <div class="img-container">
        <img src="https://cdn.razorpay.com/static/assets/website-self-serve/Website-change.svg" />
      </div>
      <div class="content">
        <strong>Keep in mind</strong>
        <span>
          <ol>
            <li>
              The product/services that you are selling on the website/app should fall under
              “e-commerce” category
            </li>
            <li>
              Open a new Razorpay account if your new website/app falls under a different category
            </li>
          </ol>
        </span>
      </div>
      <div class="action">
        <button class="btn btn-primary" onClick={onProceedClick}>
          Proceed to update website/app
          <i class="i i-chevron-right" />
        </button>
      </div>
    </div>
  );
}

export default InitiateWebsiteChange;
