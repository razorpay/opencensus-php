import { trackKnowMoreCommunityInitiated } from 'merchant/views/RizeMarketplace/common/analytics';

export interface TestimonialCardProps {
  avatarSrc: string;
  name: string;
  role: string;
  testimonial: React.ReactNode;
}

export interface RizeFooterProps {
  source: Parameters<typeof trackKnowMoreCommunityInitiated>[0];
}
