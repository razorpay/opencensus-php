import React from 'react';
import { Box, Link } from '@razorpay/blade/components';
import imagePlaceholder from 'assets/billme-integration/brand-logo-placeholder.svg';

import type { Brand } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

type BrandNameCellProps = {
  tableItem: Brand;
  onBrandNameClick: (brandId: string) => void;
};

const BrandNameCell = ({ tableItem, onBrandNameClick }: BrandNameCellProps): React.ReactElement => {
  const { id, name, logo } = tableItem;
  return (
    <Box display="flex" alignItems="center" justifyContent="flex-start" gap="spacing.3">
      <img
        src={logo || imagePlaceholder}
        width="32px"
        height="32px"
        alt={`${name} logo`}
        style={{ borderRadius: 'medium', borderWidth: 'thin' }}
      />
      <Link variant="button" onClick={() => onBrandNameClick(id)}>
        {name}
      </Link>
    </Box>
  );
};

export default BrandNameCell;
