import { merchantFetch } from '@libs/web-nexus/merchant/utils/merchantFetch';

export type FeedbackAndRatingPayload = {
  merchant_id: string;
  merchant_name: string;
  rating: number;
  feedback?: string;
};

export const submitFeedbackAndRating = (data: FeedbackAndRatingPayload): any => {
  return merchantFetch({
    url: 'pg_onboarding/feedback',
    method: 'post',
    data,
  });
};
