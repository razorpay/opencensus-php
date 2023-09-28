import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { STATUS_DATA } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';

import type { PaymentType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

interface PricingPlansProps extends RouteComponentProps {
  enrollmentStatus: {
    loading: boolean;
    hasEnrolled: boolean | null;
    message: string | null;
    error: unknown;
  };
  user: Record<string, unknown>;
  fetchEnrollmentStatus: () => void;
}

type SubscriptionPlanDataT = {
  id?: string;
  merchant_id?: string;
  account_key?: string;
  plan_id?: string;
  type?: PaymentType;
  frequency?: 'monthly' | 'yearly';
  payment_subscription_id?: string;
  status?: 'created' | 'processing' | 'approved' | 'rejected' | 'canceled';
  internal_subscription?: {
    status?: 'pending' | 'processed';
  };
  payment_subscription?: {
    id?: string;
    entity?: string;
    plan_id?: string;
    customer_id?: string;
    status?: 'created' | 'active' | 'authenticated' | 'pending' | 'halted' | 'cancelled';
    current_start?: string; // Time in epoch as string
    current_end?: string; // Time in epoch as string
    quantity?: number;
    charge_at?: string; // Time in epoch as string
    end_at?: string; // Time in epoch as string
    total_count?: number;
    paid_count?: number;
    customer_notify?: true;
    created_at?: string; // Time in epoch as string
    short_url?: string; // URL to __
    source?: string;
    remaining_count?: number;
  };
  plan?: {
    id?: string;
    name?: string;
    yearly_plan_id?: string;
    monthly_plan_id?: string;
    yearly_plan_amount?: string; // Amount in paise, number as string
    monthly_plan_amount?: string; // Amount in paise, number as string
    details?: {
      icon?: {
        src?: string;
        alt?: string;
      };
      feature?: Array<{ feature_copy?: string; offering?: string }>;
    };
    bundle?: string;
    created_by?: string; // Email
    created_at?: string; // Time in string in RFC3339
    updated_at?: string; // Time in string in RFC3339
  };
};

type StatusDataT = (typeof STATUS_DATA)[keyof typeof STATUS_DATA];

export { PricingPlansProps, SubscriptionPlanDataT, StatusDataT };
