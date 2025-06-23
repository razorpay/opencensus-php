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
import RequesSubmitSuccessLoader from './RequesSubmitSuccessLoader';
import CheckTick from './assets/lottie/CheckTick.json';
import ErrorAlert from './assets/lottie/ErrorAlert.json';
import MainPageSubmitInProgress from './assets/lottie/MainPageSubmitInProgress.json';
import PolicyPagesSubmitInProgress from './assets/lottie/PolicyPagesSubmitInProgress.json';
import { PolicyPagesSuggestionsList, mappingPolicyPageKeyToQuestionaire } from './constants';
import {
  InitialPolicyPagesFormStateData,
  MissingPagesFormFieldType,
  PartialPolicyPages,
  PolicyPageFormData,
  PolicyPagesSelection,
  SuggestionSteps,
  ValidationState,
  WebsitePolicyPages,
  WebsitePolicyPagesDetailsKeys,
  WebsiteSubmitModalSteps,
  WebsiteVerificationPageStatus,
  WebsiteVerificationStatus,
} from '../types';

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
      radioValue: undefined,
    },
    [WebsitePolicyPages.SHIPPING]: {
      value: websiteUpdateStatusData?.shipping?.url,
      verified: websiteUpdateStatusData?.shipping?.verified,
      valid: ValidationState.NONE,
      radioValue: undefined,
    },
    [WebsitePolicyPages.TERMS]: {
      value: websiteUpdateStatusData?.terms?.url,
      verified: websiteUpdateStatusData?.terms?.verified,
      valid: ValidationState.NONE,
      radioValue: undefined,
    },
    [WebsitePolicyPages.REFUND]: {
      value: websiteUpdateStatusData?.refund?.url,
      verified: websiteUpdateStatusData?.refund?.verified,
      valid: ValidationState.NONE,
      radioValue: undefined,
    },
    [WebsitePolicyPages.PRIVACY]: {
      value: websiteUpdateStatusData?.privacy?.url,
      verified: websiteUpdateStatusData?.privacy?.verified,
      valid: ValidationState.NONE,
      radioValue: undefined,
    },
  };

  const verifiedPages: Record<WebsitePolicyPages, MissingPagesFormFieldType> =
    {} as PolicyPageFormData;
  const missingPages: Record<WebsitePolicyPages, MissingPagesFormFieldType> =
    {} as PolicyPageFormData;
  const verifiedPagesKeys: WebsitePolicyPages[] = [];
  const missingPagesKeys: WebsitePolicyPages[] = [];
  const notApplicablePagesKeys: WebsitePolicyPages[] = [];

  for (const field in initalFormState) {
    /* istanbul ignore else */
    if (initalFormState.hasOwnProperty(field)) {
      const policyPageKey = field as WebsitePolicyPages;
      const page = initalFormState[policyPageKey];
      if (page.verified === WebsiteVerificationStatus.PASSED) {
        verifiedPages[field] = page;
        verifiedPagesKeys.push(policyPageKey);
      } else {
        const isNotApplicable = page.verified === WebsiteVerificationStatus.NOT_APPLICABLE;
        const initValue = isNotApplicable ? PolicyPagesSelection.NA : undefined;
        missingPages[field] = {
          ...page,
          value: '',
          radioValue: initValue,
        };
        missingPagesKeys.push(policyPageKey);
        if (isNotApplicable) {
          notApplicablePagesKeys.push(policyPageKey);
        }
      }
    }
  }

  return {
    verifiedPages,
    missingPages,
    verifiedPagesKeys,
    missingPagesKeys,
    notApplicablePagesKeys,
  };
};

interface FormField {
  value: string;
}

type ReviewPageType = Record<WebsitePolicyPages, FormField>;

export const getReviewPagesData = (
  websiteUpdateStatusData: WebsiteVerificationPageStatus | undefined,
  pagesBeingVerified: Array<WebsitePolicyPages>,
) => {
  const verifiedPages: ReviewPageType = {} as ReviewPageType;
  const missingPages: ReviewPageType = {} as ReviewPageType;
  const verifiedPagesKeys: WebsitePolicyPages[] = [];
  const missingPagesKeys: WebsitePolicyPages[] = [];

  for (const page of Object.keys(websiteUpdateStatusData || {})) {
    const pageData = websiteUpdateStatusData?.[page];
    if (pagesBeingVerified.includes(page as WebsitePolicyPages)) {
      if (pageData?.verified !== WebsiteVerificationStatus.NOT_APPLICABLE) {
        missingPages[page] = {
          value: pageData?.url,
        };
        missingPagesKeys.push(page as WebsitePolicyPages);
      }
    } else {
      verifiedPages[page] = {
        value: pageData?.url,
      };
      verifiedPagesKeys.push(page as WebsitePolicyPages);
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
    Content: ({ onClick }) => <RequesSubmitSuccessLoader onClick={onClick} eta="10 minutes" />,
  },
  [WebsiteSubmitModalSteps.WEBSITE_UPDATE_WORKFLOW_RAISED]: {
    lottieAnimation: CheckTick,
    img: CheckTick,
    Content: ({ onClick }) => (
      <RequesSubmitSuccessLoader onClick={onClick} eta="1-3 working days" />
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
  [WebsiteSubmitModalSteps.MAIN_PAGE_LIVENESS_ERROR]: {
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
  [SuggestionSteps.POLICY_PAGES]: {
    element: ({ platform = 'website' }) => (
      <>
        <Text
          color="surface.text.gray.subtle"
          wordBreak="break-word"
          marginBottom="spacing.2"
          weight="medium"
        >
          Mandatory details required on your {platform}:
        </Text>
        <ListSuggestionBox listItems={PolicyPagesSuggestionsList.POLICY_PAGES} />
      </>
    ),
    positioningStyles: {
      alignContent: 'flex-start',
    },
  },
  [SuggestionSteps.URL_FIELD]: {
    element: ({ platform = 'website' }) => (
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        This should be the {platform} where you intend to collect payments.
      </Text>
    ),
    positioningStyles: {
      alignContent: 'flex-start',
      marginTop: '25%',
    },
  },
  [SuggestionSteps.CREDS_NOT_REQUIRED]: {
    element: ({ platform = 'website' }) => (
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        If your {platform} requires users to log in before making a purchase or payment, then please
        provide us test credentials for us to verify your {platform}.
      </Text>
    ),
    positioningStyles: {
      alignContent: 'center',
      marginTop: '15%',
    },
  },
  [SuggestionSteps.CREDS_REQUIRED]: {
    element: () => (
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        Please share the credentials to a test account. This helps Razorpay understand your checkout
        flow, so we can ensure accurate integration, user tracking, and support for features like
        refunds, order mapping, and fraud prevention.
      </Text>
    ),
    positioningStyles: {
      alignContent: 'center',
      marginTop: '10%',
    },
  },
  [SuggestionSteps.CREDS_FIELDS]: {
    element: () => (
      <Text color="surface.text.gray.subtle" wordBreak="break-word">
        Please share the credentials to a test account. This helps Razorpay understand your checkout
        flow, so we can ensure accurate integration, user tracking, and support for features like
        refunds, order mapping, and fraud prevention.
      </Text>
    ),
    positioningStyles: {
      alignContent: 'flex-end',
      marginBottom: '6%',
    },
  },
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

export const isPolicyPageCreatedByRazorpay = (policyPageUrl: string) => {
  const isProd = window.APP_ENV === 'production';
  let regexToMatch = /sme-dashboard\.dev\.razorpay\.in/;
  if (isProd) regexToMatch = /merchant\.razorpay\.com/;
  return regexToMatch.test(policyPageUrl);
};

export const getQuestionaireDetailsFromPolicyPagesToBeGenerated = (
  policyPagesToGenerate: PartialPolicyPages,
) => {
  const questions: Array<Partial<WebsitePolicyPagesDetailsKeys>> = [];

  policyPagesToGenerate.forEach((policyPage) => {
    const mappedQuestions = mappingPolicyPageKeyToQuestionaire[policyPage];

    if (mappedQuestions) {
      mappedQuestions.forEach((question) => {
        if (!questions.includes(question)) {
          questions.push(question);
        }
      });
    }
  });
  const keys = Object.values(WebsitePolicyPagesDetailsKeys);

  const result: Record<WebsitePolicyPagesDetailsKeys, boolean> = {} as Record<
    WebsitePolicyPagesDetailsKeys,
    boolean
  >;
  let isEmpty = true;
  keys.forEach((key) => {
    const isPresent = questions.includes(key as WebsitePolicyPagesDetailsKeys);
    if (isPresent) {
      isEmpty = false;
    }
    result[key] = isPresent;
  });

  return { isEmpty, questionaireMapping: result };
};
