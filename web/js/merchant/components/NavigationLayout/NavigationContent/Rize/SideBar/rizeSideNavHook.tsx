import { CompanyRegistrationIcon } from '@razorpay/blade/components';
import type { IconComponent } from '@razorpay/blade/components';

type SideNavSectionList = {
  section_name?: string;
  section_id: string;
  product_options: Array<{
    label: string;
    href: string;
    icon: IconComponent;
    title: string;
    to: string;
    end: Boolean;
    product_id?: string;
    routeRegex: string | undefined;
  }>;
};
interface useSideNavigationProps {
  listItems: Array<SideNavSectionList>;
  footer: React.ReactElement | null;
  banner: React.ReactElement | null;
}
const getRizeSideTab = (): useSideNavigationProps => {
  return {
    listItems: [
      {
        section_name: '',
        section_id: 'rize_incorporation_top_nav_id',
        product_options: [
          {
            label: 'Company Registration',
            title: 'Company Registration',
            href: '/company-registration',
            icon: CompanyRegistrationIcon,
            to: '/company-registration',
            end: true,
            routeRegex: undefined,
          },
        ],
      },
    ],
    footer: null,
    banner: null,
  };
};

export default getRizeSideTab;
