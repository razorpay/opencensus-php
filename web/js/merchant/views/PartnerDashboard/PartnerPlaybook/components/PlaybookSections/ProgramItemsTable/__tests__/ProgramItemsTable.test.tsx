import React from 'react';

import { render, screen, userEvent } from 'common/services/test/test-utils';
import * as analytics from 'common/utils/analytics';
import { handleSearch } from 'merchant/views/PartnerDashboard/PartnerPlaybook/api';
import ProgramItemsTable from 'merchant/views/PartnerDashboard/PartnerPlaybook/components/PlaybookSections/ProgramItemsTable';
import { programItemsData } from 'merchant/views/PartnerDashboard/PartnerPlaybook/data';

const analyticsTrackWithUserInfoSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');
const parsedProgramItems = handleSearch('', programItemsData);
const getStartedSection = parsedProgramItems[0].sectionItem;

const defaultProps = {
  ...getStartedSection.folders[0],
  sectionHeader: getStartedSection.header,
  openPreview: jest.fn(),
};
describe('ProgramItemsTable', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
    window.open = jest.fn();
  });
  afterEach(() => {
    jest.clearAllMocks();
  });
  const renderApp = (props = {}) => {
    // eslint-disable-next-line
    // @ts-ignore
    render(<ProgramItemsTable {...defaultProps} {...props} />);
  };

  test('item actions view cta', async () => {
    renderApp();
    expect(screen.getAllByLabelText('view')).toHaveLength(6);
    await userEvent.click(screen.getAllByLabelText('view')[0]);
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Section Cta',
        actionName: 'Clicked',
        properties: expect.objectContaining({
          section: 'Get Started',
          pageFold: 2,
          folderName: null,
          folderDescription: null,
          title: 'Welcome',
          ctaClicked: 'view',
        }),
      }),
    );
    expect(defaultProps.openPreview).toHaveBeenCalledWith(getStartedSection.folders[0].items[0]);
  });

  test('row item click action cta', async () => {
    renderApp();
    const rowTitle = defaultProps.items[0].title;
    const rowDescription = defaultProps.items[0].description;
    expect(screen.getAllByText(rowDescription)).toHaveLength(1);
    await userEvent.click(screen.getAllByText(rowDescription)[0], { pointerEventsCheck: 0 });
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Section Cta',
        actionName: 'Clicked',
        properties: expect.objectContaining({
          section: 'Get Started',
          pageFold: 2,
          folderName: null,
          folderDescription: null,
          title: rowTitle,
          ctaClicked: 'row item',
        }),
      }),
    );
    expect(defaultProps.openPreview).toHaveBeenCalledWith(getStartedSection.folders[0].items[0]);
  });

  test('item actions copy url cta', async () => {
    renderApp();
    expect(screen.getAllByLabelText('copy')).toHaveLength(6);
    await userEvent.click(screen.getAllByLabelText('copy')[0]);
    expect(document.execCommand).toHaveBeenCalledWith('copy');

    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Section Cta',
        actionName: 'Clicked',
        properties: expect.objectContaining({
          section: 'Get Started',
          pageFold: 2,
          folderName: null,
          folderDescription: null,
          title: 'Welcome',
          ctaClicked: 'copy',
        }),
      }),
    );
  });

  test('item actions download url cta', async () => {
    const growYourBusinessSection = parsedProgramItems[1].sectionItem;

    renderApp({
      ...growYourBusinessSection.folders[0],
      sectionHeader: growYourBusinessSection.header,
    });
    expect(screen.getAllByLabelText('download')).toHaveLength(2);
    await userEvent.click(screen.getAllByLabelText('download')[0]);

    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Section Cta',
        actionName: 'Clicked',
        properties: expect.objectContaining({
          section: 'Grow Your Business',
          pageFold: 3,
          folderName: 'Marketing Assets',
          title: 'E-commerce Pitch',
          ctaClicked: 'download',
        }),
      }),
    );
  });

  test('should hide download url cta if disable_download is true', () => {
    const growYourBusinessSection = parsedProgramItems[1].sectionItem;
    renderApp({
      ...growYourBusinessSection.folders[0],
      items: growYourBusinessSection.folders[0].items.map((item) => ({
        ...item,
        disable_download: true,
      })),
      sectionHeader: growYourBusinessSection.header,
    });
    expect(screen.queryAllByLabelText('download')).toHaveLength(0);
  });
});
