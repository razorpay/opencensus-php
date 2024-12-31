import React, { useMemo, Fragment } from 'react';
import { Box, Breadcrumb, Divider, HomeIcon } from '@razorpay/blade/components';

import BreadcrumbBack from '@apps/digital-bills/src/common/components/Breadcrumbs/BreadcrumbBack';
import BreadcrumbNavLink from '@apps/digital-bills/src/common/components/Breadcrumbs/BreadcrumbNavLink';

import type {
  BreadCrumbType,
  BreadcrumbsProps,
} from '@apps/digital-bills/src/common/components/Breadcrumbs/types';

const getBreadCrumbHref = ({
  currentLevel = 0,
  breadCrumb,
  maxLevel = 0,
}: {
  currentLevel?: number;
  breadCrumb: BreadCrumbType;
  maxLevel?: number;
}): Required<BreadCrumbType> => {
  if (breadCrumb.href) {
    return { label: breadCrumb.label, href: breadCrumb.href };
  }

  const href = currentLevel === maxLevel ? './' : '../'.repeat(maxLevel - currentLevel - 1);
  return { ...breadCrumb, href };
};

const Breadcrumbs = (props: BreadcrumbsProps): React.ReactElement => {
  const { items: breadCrumbs = [], hideBackButton = false } = props;
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

  const homeHref = `${breadCrumbWithHrefs[0]?.href || ''}../`;
  return (
    <Box display="flex" alignItems="center" marginBottom="spacing.7">
      {!hideBackButton ? (
        <Fragment>
          <BreadcrumbBack size="large" variant="anchor" />
          <Divider
            orientation="vertical"
            variant="normal"
            thickness="thinner"
            marginX="spacing.5"
          />
        </Fragment>
      ) : null}
      <Breadcrumb size="medium" color="primary">
        <BreadcrumbNavLink icon={HomeIcon} to={homeHref} />
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
