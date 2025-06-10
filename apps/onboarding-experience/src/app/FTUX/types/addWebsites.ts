import { ReactElement } from 'react';

export enum AddWebsiteBannerActions {
  ResolveClarification = 'resolve-clarification',
  ResolveBvsClarification = 'resolve-bvs-clarification',
  AddWebsite = 'add-website',
  UpdateWebsite = 'update-website',
}

export enum AddWebsiteBadgeContent {
  VERIFIED = 'Verified',
  MISSING_BVS_PAGES = 'Policy pages are missing',
  NEEDS_CLARIFICATIONS = 'Needs Clarifications',
  UNDER_REVIEW = 'Under review',
  ERROR_OCCURED = 'Error Occured',
  LIVENESS_FAILED = 'Not an active website',
  REJECTED = 'Rejected',
  KLA_ACTIVATED = 'Website not verified',
}

type BadgeColor = 'information' | 'negative' | 'neutral' | 'notice' | 'positive' | 'primary';
type ButtonVariant = 'primary' | 'secondary' | 'tertiary';

export type WebsitePlatformData = {
  title: string;
  content?: string;
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
