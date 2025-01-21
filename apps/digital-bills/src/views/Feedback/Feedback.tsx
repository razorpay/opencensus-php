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
import { PAGE_ROUTES, PAGE_BREADCRUMBS } from '@apps/digital-bills/src/views/Feedback/constants';

const Feedback = (): React.ReactElement => {
  const [currentListing, setCurrentListing] = useState<string>('campaign');
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
              onChange={({ values }: { name?: string; values: string[] }): void => {
                setCurrentListing(values[0]);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem title="Campaign" value="campaign" />
                <ActionListItem title="Responses" value="responses" />
                <ActionListItem title="Customer Complaints" value="customerComplaints" />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </Box>
      <Box height="70vh">
        <Iframe
          pathname={PAGE_ROUTES[currentListing].link}
          onRouteChange={(location): void => {
            switch (location) {
              case '/feedback/campaigns':
                setCurrentListing('campaign');
                break;
              case '/feedback':
              case '/feedback/responses':
                setCurrentListing('responses');
                break;
              case '/bill-complaints':
                setCurrentListing('customerComplaints');
                break;
              default:
                break;
            }
          }}
        />
      </Box>
    </>
  );
};

export default Feedback;
