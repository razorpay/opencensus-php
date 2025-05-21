import { DateTime } from 'merchant/widgets/types';

export type PerformanceCard = {
  id: string;
  label: string;
  value: string;
};

export type PerformanceComponent = {
  data: {
    cards: PerformanceCard[];
  };
  id: string;
  title: string;
  variant: 'positive' | 'negative';
};

export type BusinessPerformanceProps = {
  id: string;
  components: PerformanceComponent[];
  inputs: {
    default_value: string;
    name: string;
    type: string;
    values: string[];
  }[];
  title: string;
  type: string;
  filters: {
    date_time: DateTime;
    store_ids: string[];
    payment_source: string;
  };
};
