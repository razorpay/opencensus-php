import React from 'react';

import { render, screen, userEvent } from 'common/services/test/test-utils';
import * as analytics from 'common/utils/analytics';
import { handleSearch } from 'merchant/views/PartnerDashboard/PartnerPlaybook/api';
import ProgramFolders from 'merchant/views/PartnerDashboard/PartnerPlaybook/components/PlaybookSections/ProgramFolders';
import { programItemsData } from 'merchant/views/PartnerDashboard/PartnerPlaybook/data';

const analyticsTrackWithUserInfoSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');
const parsedProgramItems = handleSearch('', programItemsData);
const growYourBusinessSection = parsedProgramItems[1].sectionItem;

const defaultProps = {
  folders: growYourBusinessSection.folders,
  isSearchQueryPresent: false,
  sectionHeader: growYourBusinessSection.header,
};
describe('ProgramFolders', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });
  const renderApp = () => {
    // eslint-disable-next-line
    // @ts-ignore
    render(<ProgramFolders {...defaultProps} />);
  };

  test('folder expand/collapse behavior', async () => {
    renderApp();
    expect(screen.getByText('Marketing Assets')).toBeInTheDocument();
    expect(screen.queryByText('Ecommerce Banner')).toBeNull();
    // Test view all click
    await userEvent.click(screen.getAllByText('View all')[0]);
    expect(screen.getByText('Ecommerce Banner')).toBeInTheDocument();
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Section Cta',
        actionName: 'Clicked',
        properties: expect.objectContaining({
          section: 'Grow Your Business',
          pageFold: 3,
          folderName: 'Marketing Assets',
          ctaClicked: 'View all',
        }),
      }),
    );
    // Test folder header clicking
    await userEvent.click(screen.getAllByText('Marketing Assets')[0]);
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Section Cta',
        actionName: 'Clicked',
        properties: expect.objectContaining({
          section: 'Grow Your Business',
          ctaClicked: 'Folder header',
        }),
      }),
    );
    // Should collapse again
    expect(screen.queryByText('Ecommerce Banner')).toBeNull();
  });
});
