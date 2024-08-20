import { User } from 'merchant/views/MagicCheckout/types';
import { Layout } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/types';

type MagicDashboardLayout = Layout & {
  condition?: (user: User) => boolean;
  type: string;
  onRCOD?: boolean;
};
