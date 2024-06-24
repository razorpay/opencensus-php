import React from 'react';
import {
  Text,
  FileTextIcon,
  PackageIcon,
  PhoneIcon,
  RotateCounterClockWiseIcon,
  ShieldIcon,
  Heading,
  Button,
  Box,
} from '@razorpay/blade/components';

import ListSuggestionBox from './ListSuggestionBox';
import CheckTick from './assets/lottie/CheckTick.json';
import ErrorAlert from './assets/lottie/ErrorAlert.json';
import MainPageSubmitInProgress from './assets/lottie/MainPageSubmitInProgress.json';
import PolicyPagesSubmitInProgress from './assets/lottie/PolicyPagesSubmitInProgress.json';
import { PolicyPagesSuggestionsList } from './constants';
import {
  FormFieldType,
  InitialPolicyPagesFormStateData,
  PolicyPageFormData,
  SuggestionSteps,
  ValidationState,
  WebsitePolicyPages,
  WebsiteSubmitModalSteps,
  WebsiteVerificationPageStatus,
  WebsiteVerificationStatus,
} from '../types';
import { getUnderReviewETA } from '../utils';

export const playStoreRegex = /^https?:\/\/play\.google\.com\/store\/apps\/.*/;

export const appStoreRegex = /^https?:\/\/(itunes\.apple\.com|apps\.apple\.com)\/.*\/app\/.*/;

export const isAppstorePlaystoreUrl = (url: string): boolean =>
  playStoreRegex.test(url) || appStoreRegex.test(url);

export const getBusinessPlatformType = (url: string): string =>
  isAppstorePlaystoreUrl(url) ? 'App' : 'Website';

export const getInitialPolicyPagesFormState = (
  websiteUpdateStatusData: WebsiteVerificationPageStatus | undefined,
): InitialPolicyPagesFormStateData => {
  const initalFormState = {
    [WebsitePolicyPages.CONTACT]: {
      value: websiteUpdateStatusData?.contact?.url,
      verified: websiteUpdateStatusData?.contact?.verified,
      valid: ValidationState.NONE,
    },
    [WebsitePolicyPages.SHIPPING]: {
      value: websiteUpdateStatusData?.shipping?.url,
      verified: websiteUpdateStatusData?.shipping?.verified,
      valid: ValidationState.NONE,
    },
    [WebsitePolicyPages.TERMS]: {
      value: websiteUpdateStatusData?.terms?.url,
      verified: websiteUpdateStatusData?.terms?.verified,
      valid: ValidationState.NONE,
    },
    [WebsitePolicyPages.REFUND]: {
      value: websiteUpdateStatusData?.refund?.url,
      verified: websiteUpdateStatusData?.refund?.verified,
      valid: ValidationState.NONE,
    },
    [WebsitePolicyPages.PRIVACY]: {
      value: websiteUpdateStatusData?.privacy?.url,
      verified: websiteUpdateStatusData?.privacy?.verified,
      valid: ValidationState.NONE,
    },
  };

  const verifiedPages: Record<WebsitePolicyPages, FormFieldType> = {} as PolicyPageFormData;
  const missingPages: Record<WebsitePolicyPages, FormFieldType> = {} as PolicyPageFormData;
  const verifiedPagesKeys: WebsitePolicyPages[] = [];
  const missingPagesKeys: WebsitePolicyPages[] = [];

  for (const field in initalFormState) {
    /* istanbul ignore else */
    if (initalFormState.hasOwnProperty(field)) {
      const policyPageKey = field as WebsitePolicyPages;
      const page = initalFormState[policyPageKey];
      if (page.verified === WebsiteVerificationStatus.PASSED) {
        verifiedPages[field] = page;
        verifiedPagesKeys.push(policyPageKey);
      } else {
        missingPages[field] = {
          ...page,
          value: '',
        };
        missingPagesKeys.push(policyPageKey);
      }
    }
  }

  return { verifiedPages, missingPages, verifiedPagesKeys, missingPagesKeys };
};

export const PolicyPageContent = {
  [WebsitePolicyPages.CONTACT]: {
    Icon: PhoneIcon,
    title: 'Contact Us',
  },
  [WebsitePolicyPages.SHIPPING]: {
    Icon: PackageIcon,
    title: 'Shipping Policy',
  },
  [WebsitePolicyPages.TERMS]: {
    Icon: FileTextIcon,
    title: 'Terms and Conditions',
  },
  [WebsitePolicyPages.REFUND]: {
    Icon: RotateCounterClockWiseIcon,
    title: 'Cancellations and Refunds',
  },
  [WebsitePolicyPages.PRIVACY]: {
    Icon: ShieldIcon,
    title: 'Privacy Policy',
  },
};

export const badgeColorMap: Record<
  string,
  'notice' | 'primary' | 'positive' | 'negative' | 'information'
> = {
  Primary: 'primary',
  Website: 'primary',
  App: 'primary',
  Active: 'positive',
  'Under Review': 'notice',
  'Action Required': 'negative',
};

export const loaderVariant = {
  [WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS]: {
    lottieAnimation: MainPageSubmitInProgress,
  },
  [WebsiteSubmitModalSteps.POLICY_PAGES_SUBMIT_IN_PROGRESS]: {
    lottieAnimation: PolicyPagesSubmitInProgress,
  },
  [WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS]: {
    lottieAnimation: CheckTick,
    Content: ({ onClick }) => (
      <>
        <Heading size="large" textAlign="center">
          Your website is submitted for verification
        </Heading>
        <Box>
          <Text textAlign="center" color="surface.text.gray.subtle">
            We’re verifying your details and will share an update
          </Text>
          <Text weight="medium" textAlign="center" color="surface.text.gray.subtle">
            within 10 minutes
          </Text>
        </Box>
        <Button onClick={onClick}>Okay, got it</Button>
      </>
    ),
  },
  [WebsiteSubmitModalSteps.MANUAL_WF_RAISED]: {
    lottieAnimation: CheckTick,
    Content: ({ onClick }) => (
      <>
        <Heading size="large" textAlign="center">
          Your website is submitted for verification
        </Heading>
        <Box>
          <Text textAlign="center" color="surface.text.gray.subtle">
            We’re verifying your details and will share an update by
          </Text>
          <Text textAlign="center" weight="medium" color="surface.text.gray.subtle">
            {getUnderReviewETA({
              offset: 48 * 60 * 60 * 1000,
            })}
          </Text>
        </Box>
        <Button onClick={onClick}>Okay, got it</Button>
      </>
    ),
  },
  [WebsiteSubmitModalSteps.WEBSITE_UPDATE_SUCCESS]: {
    lottieAnimation: CheckTick,
    img: CheckTick,
    Content: ({ onClick }) => (
      <>
        <Heading size="large" textAlign="center">
          Your website has been successfully verified
        </Heading>
        <Text textAlign="center" color="surface.text.gray.subtle">
          To start accepting payments, you’ll need to download API keys and integrate them on the
          website
        </Text>
        <Button onClick={onClick}>Okay, got it</Button>
      </>
    ),
  },
  [WebsiteSubmitModalSteps.MAIN_PAGE_ERROR]: {
    lottieAnimation: ErrorAlert,
    Content: ({ onClick }) => (
      <>
        <Heading size="large" textAlign="center">
          Your website isn’t ready for verification
        </Heading>
        <Text textAlign="center" color="surface.text.gray.subtle">
          Kindly update your website details and retry verification
        </Text>
        <Button onClick={onClick}>Okay, got it</Button>
      </>
    ),
  },
};

export const suggestionBoxVariants = {
  [SuggestionSteps.POLICY_PAGES]: (
    <>
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        Make sure these pages are available on your website with the required details
      </Text>
      <ListSuggestionBox listItems={PolicyPagesSuggestionsList.POLICY_PAGES} />
    </>
  ),
  [SuggestionSteps.CREDS_NOT_REQUIRED]: (
    <Text color="surface.text.gray.subtle" wordBreak="break-word">
      If your website requires login from user to complete payment, provide a ‘Test account’ for us
      to verify your website
    </Text>
  ),
  [SuggestionSteps.CREDS_REQUIRED]: (
    <Text color="surface.text.gray.subtle" wordBreak="break-word">
      Provide a ‘Test account’ for us to verify your website
    </Text>
  ),
  [SuggestionSteps.MISSING_POLICY_PAGES_terms]: (
    <>
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        A <b>Terms and Conditions page</b> outlines the conditions of use for your website or app.
      </Text>
      <Text color="surface.text.gray.subtle" weight="medium">
        Details required:
      </Text>
      <ListSuggestionBox listItems={PolicyPagesSuggestionsList.MISSING_POLICY_PAGES_terms} />
    </>
  ),
  [SuggestionSteps.MISSING_POLICY_PAGES_privacy]: (
    <>
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        A <b>Privacy Policy page</b> discloses how your company will handle and protect user
        information.
      </Text>
      <Text color="surface.text.gray.subtle" weight="medium">
        Details required:
      </Text>
      <ListSuggestionBox listItems={PolicyPagesSuggestionsList.MISSING_POLICY_PAGES_privacy} />
    </>
  ),
  [SuggestionSteps.MISSING_POLICY_PAGES_contact]: (
    <>
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        A <b>Contact Us page</b> should contain information through which customers can reach you.
      </Text>
      <Text color="surface.text.gray.subtle" weight="medium">
        Details required:
      </Text>
      <ListSuggestionBox listItems={PolicyPagesSuggestionsList.MISSING_POLICY_PAGES_contact} />
    </>
  ),
  [SuggestionSteps.MISSING_POLICY_PAGES_refund]: (
    <>
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        A <b>Cancellations and Refunds page</b> outlines rules about how customers can return and
        exchange products/services they purchased.
      </Text>
      <Text color="surface.text.gray.subtle" weight="medium">
        Details required:
      </Text>
      <ListSuggestionBox listItems={PolicyPagesSuggestionsList.MISSING_POLICY_PAGES_refund} />
    </>
  ),
  [SuggestionSteps.MISSING_POLICY_PAGES_shipping]: (
    <>
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        A <b>Shipping Policy</b> contains information about rules, timelines, and processes for
        shipped items.
      </Text>
      <Text color="surface.text.gray.subtle" weight="medium">
        Details required:
      </Text>
      <ListSuggestionBox listItems={PolicyPagesSuggestionsList.MISSING_POLICY_PAGES_shipping} />
    </>
  ),
};

export const policySuggestionStep = {
  [WebsitePolicyPages.TERMS]: SuggestionSteps.MISSING_POLICY_PAGES_terms,
  [WebsitePolicyPages.PRIVACY]: SuggestionSteps.MISSING_POLICY_PAGES_privacy,
  [WebsitePolicyPages.CONTACT]: SuggestionSteps.MISSING_POLICY_PAGES_contact,
  [WebsitePolicyPages.REFUND]: SuggestionSteps.MISSING_POLICY_PAGES_refund,
  [WebsitePolicyPages.SHIPPING]: SuggestionSteps.MISSING_POLICY_PAGES_shipping,
};

export function isWebsiteRecentlyUpdated(current_status_updated_at) {
  const currentTime = new Date().getTime();
  const statusUpdatedAt = new Date(+current_status_updated_at * 1000).getTime();
  const diff = currentTime - statusUpdatedAt;

  return !(diff > 7 * 24 * 60 * 60 * 1000);
}
