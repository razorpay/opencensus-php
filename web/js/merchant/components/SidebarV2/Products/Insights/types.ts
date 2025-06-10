import { type IconComponent } from '@razorpay/blade/components';

export type InsightMetric = Record<string, string>;
export type InsightsApiResponse = Record<string, InsightMetric[]>;

export interface MethodAggregateResponse {
  data: InsightsApiResponse;
}

export type InsightsL2Product = {
  title: string;
  href: string;
  icon: IconComponent;
  metricCount: string | null;
};

export type InsightsL1Product = {
  title: string;
  href: string;
  icon: IconComponent;
  items: InsightsL2Product[];
};

export type InsightsL1ProductsData = InsightsL1Product[]; 