import { ReactElement } from 'react';

export enum AddWebsiteBannerActions {
  RESOLVE_CLARIFICATION = 'resolve-clarification',
  RESOLVE_BVS_CLARIFICATION = 'resolve-bvs-clarification',
  ADD_WEBSITE = 'add-website',
  UPDATE_WEBSITE = 'update-website',
}

export enum AddWebsiteBadgeContent {
  VERIFIED = 'Verified',
  MISSING_BVS_PAGES = 'Policy pages are missing',
  NEEDS_CLARIFICATIONS = 'Needs Clarifications',
  UNDER_REVIEW = 'Under review',
  ERROR_OCCURED = 'Error Occured',
  LIVENESS_FAILED = 'Not an active website',
  REJECTED = 'Rejected',
  KLA_ACTIVATED = 'Not verified',
}

type BadgeColor = 'information' | 'negative' | 'neutral' | 'notice' | 'positive' | 'primary';
type ButtonVariant = 'primary' | 'secondary' | 'tertiary';

export type WebsitePlatformData = {
  title: string;
  content?: string | string[];
  badge?: {
    color: BadgeColor;
    content: AddWebsiteBadgeContent;
  };
  cta?: {
    label: string;
    action: AddWebsiteBannerActions;
    variant?: ButtonVariant;
  };
  customLabel?: ReactElement;
};
