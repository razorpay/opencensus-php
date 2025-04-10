import React, { useMemo, Fragment } from 'react';
import { Box, Breadcrumb, Divider, HomeIcon } from '@razorpay/blade/components';

import BreadcrumbBack from 'merchant/views/BillMeSettings/common/components/Breadcrumbs/BreadcrumbBack';
import BreadcrumbNavLink from 'merchant/views/BillMeSettings/common/components/Breadcrumbs/BreadcrumbNavLink';

import type { BreadCrumbType } from 'merchant/views/BillMeSettings/common/components/Breadcrumbs/types';

type BreadcrumbsProps = {
  items: BreadCrumbType[];
  shouldHideBackButton?: boolean;
  backPath?: string;
};

const getBreadCrumbHref = ({
  currentLevel = 0,
  breadCrumb,
  maxLevel = 0,
}: {
  currentLevel?: number;
  breadCrumb: BreadCrumbType;
  maxLevel?: number;
}): Required<BreadCrumbType> => {
  if (breadCrumb.href) return { label: breadCrumb.label, href: breadCrumb.href };

  let href = '../'.repeat(maxLevel - currentLevel - 1);
  if (currentLevel === maxLevel) href = './';
  return { ...breadCrumb, href };
};

const Breadcrumbs = (props: BreadcrumbsProps): React.ReactElement => {
  const { items: breadCrumbs = [], shouldHideBackButton = false, backPath } = props;
  const breadCrumbWithHrefs = useMemo(
    () =>
      breadCrumbs.map((breadcrumb, index) =>
        getBreadCrumbHref({
          currentLevel: index,
          breadCrumb: breadcrumb,
          maxLevel: breadCrumbs.length,
        }),
      ),
    [breadCrumbs],
  );

  return (
    <Box display="flex" alignItems="center">
      {!shouldHideBackButton ? (
        <Fragment>
          <BreadcrumbBack size="large" variant="button" to={backPath} />
          <Divider
            orientation="vertical"
            variant="normal"
            thickness="thinner"
            marginX="spacing.5"
          />
        </Fragment>
      ) : null}
      <Breadcrumb size="medium" color="primary" data-analytics-name="page-breadcrumbs">
        <BreadcrumbNavLink icon={HomeIcon} to="/dashboard" />
        {breadCrumbWithHrefs.map((breadCrumb) => (
          <BreadcrumbNavLink key={`${breadCrumb.href}-${breadCrumb.label}`} to={breadCrumb.href}>
            {breadCrumb.label}
          </BreadcrumbNavLink>
        ))}
      </Breadcrumb>
    </Box>
  );
};

export default Breadcrumbs;
