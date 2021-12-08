import { Banner, CTA } from './DashboardBannerTypes';
import { User } from '../../NotificationsDropdown/Neostone/TypeDeclare/XCATypeDeclare';

interface BannerComponentProps extends Banner {
  ctaArray: Array<CTA> | undefined;
  user: User;
  tracking: any;
  fromWhere: string;
}

export { BannerComponentProps };
