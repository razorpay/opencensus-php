import React, { useMemo } from 'react';
import { Box, ChevronRightIcon, Text } from '@razorpay/blade/components';
import { Link, matchPath, useLocation } from 'react-router-dom';

import { ROUTE_PATTERNS } from 'merchant/views/POS/constants';

type Step = {
  link: string;
  label: string;
};

const PosBreadcrumbs = (): JSX.Element => {
  const location = useLocation();

  const steps = useMemo<Step[]>(() => {
    const currentPath = location.state?.breadcrumbsPath ?? location.pathname;
    if (currentPath.startsWith('/pos')) {
      const patternObj = ROUTE_PATTERNS.find((pattern) => !!matchPath(pattern.path, currentPath));
      if (patternObj) {
        const matchedInfo = matchPath(patternObj.path, currentPath);
        return patternObj.steps.map(({ link, label }) => ({
          link: typeof link === 'function' ? link({ params: matchedInfo?.params }) : link,
          label: typeof label === 'function' ? label({ params: matchedInfo?.params }) : label,
        }));
      }
    }
    return [];
  }, [location]);

  return (
    <Box display="flex" alignItems="center" marginBottom="spacing.6">
      {steps.map(({ link, label }, index) => (
        <Box key={link} display="flex" alignItems="center">
          {index === steps.length - 1 ? (
            <Text type="subdued" marginRight="spacing.4">
              {label}
            </Text>
          ) : (
            <Link to={link}>
              <Text type="subdued" marginRight="spacing.4">
                {label}
              </Text>
            </Link>
          )}
          {index !== steps.length - 1 ? (
            <ChevronRightIcon
              size="medium"
              color="surface.text.subdued.lowContrast"
              marginRight="spacing.4"
            />
          ) : null}
        </Box>
      ))}
    </Box>
  );
};

export default PosBreadcrumbs;
