import React, { useState } from 'react';
import {
  Box,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  SelectInput,
} from '@razorpay/blade/components';

import Breadcrumbs from '@apps/digital-bills/src/common/components/Breadcrumbs';
import Iframe from '@apps/digital-bills/src/common/components/Iframe';
import {
  PAGE_ROUTES,
  PAGE_BREADCRUMBS,
  BILL_CAMPAIGN_OPTIONS,
  ListingOptions,
} from '@apps/digital-bills/src/views/BillCampaigns/constants';
import { getActiveListingFromRoute } from '@apps/digital-bills/src/views/BillCampaigns/helpers';

const BillCampaigns = (): React.ReactElement => {
  const [currentListing, setCurrentListing] = useState<ListingOptions>(
    BILL_CAMPAIGN_OPTIONS.BANNER_IN_BILL,
  );

  return (
    <>
      <Box
        marginTop={{ base: 'spacing.6', m: 'spacing.0' }}
        marginBottom="spacing.4"
        display="flex"
        flexWrap="wrap"
        justifyContent="space-between"
      >
        <Breadcrumbs items={PAGE_BREADCRUMBS} />
        <Box minWidth={{ base: '100%', m: '400px' }}>
          <Dropdown selectionType="single">
            <SelectInput
              labelPosition="left"
              value={currentListing}
              label="Select Listing"
              placeholder="Select Listing"
              name="listing"
              onChange={({ values }): void => {
                setCurrentListing(values[0] as ListingOptions);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.keys(PAGE_ROUTES).map((option) => (
                  <ActionListItem key={option} title={PAGE_ROUTES[option].label} value={option} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </Box>
      <Box height="70vh">
        <Iframe
          pathname={PAGE_ROUTES[currentListing].link}
          onRouteChange={(pathname: string): void =>
            setCurrentListing(getActiveListingFromRoute(pathname))
          }
        />
      </Box>
    </>
  );
};

export default BillCampaigns;
