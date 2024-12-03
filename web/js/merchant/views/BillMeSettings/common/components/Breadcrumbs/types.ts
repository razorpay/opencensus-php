import type { LinkProps, BreadcrumbItemProps } from '@razorpay/blade/components';
import type { LinkProps as RouterLinkProps } from 'react-router-dom';

export type BreadCrumbType = {
  label: string;
  href?: string;
};

export type BreadcrumbBackProps = {
  size?: LinkProps['size'];
  variant?: LinkProps['variant'];
  icon?: LinkProps['icon'];
  children?: LinkProps['children'];
  to?: RouterLinkProps['to'];
} & Pick<RouterLinkProps, 'relative'>;

export type BreadcrumbNavLinkProps = Pick<BreadcrumbItemProps, 'icon' | 'onClick' | 'children'> &
  Pick<RouterLinkProps, 'replace' | 'state' | 'target' | 'to' | 'relative'>;
