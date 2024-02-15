import React from 'react';
import { Box, Text, Link } from '@razorpay/blade/components';

import { PosSubmerchantDetailsResponseDataType } from 'merchant/views/PartnerDashboard/SubMerchant/POS/TypeDeclares';
import SubMerchantKycStatusLabel from 'merchant/views/PartnerDashboard/SubMerchant/components/SubMerchantKycStatusLabel';

export const DetailsRow = ({
  label,
  values,
  isLink,
  href,
  isActivationStatus,
  kycAccess,
}: {
  label: string;
  values: (number | string | undefined)[];
  isLink?: boolean;
  href?: string;
  isActivationStatus?: boolean;
  kycAccess?: PosSubmerchantDetailsResponseDataType['kyc_access'];
}): JSX.Element => {
  return (
    <Box display="flex" gap="spacing.0" padding="spacing.3">
      <Box width={isActivationStatus ? '38%' : '39%'}>
        <Text color="surface.text.muted.lowContrast">{label}</Text>
      </Box>
      <Box display="flex" flexDirection="column">
        {values.map((item, index) => (
          <Box key={`${item}-${index}`}>
            {isLink && item && href ? (
              <Link href={href} target="_blank">
                {item}
              </Link>
            ) : (
              <Box>
                {isActivationStatus ? (
                  <SubMerchantKycStatusLabel
                    activation_status={item ?? 'Not Available'}
                    kyc_access={kycAccess}
                    showDescriptionAsTooltip
                  />
                ) : (
                  <Text color="surface.text.normal.lowContrast">
                    {item && item !== '' ? item : 'N/A'}
                  </Text>
                )}
              </Box>
            )}
          </Box>
        ))}
      </Box>
    </Box>
  );
};
