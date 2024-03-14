import { delay } from 'common/utils/timeout';
import {
  PlaybookFiltersType,
  FetchPlaybookItemsResponse,
  PlaybookItems,
  ProgramFolder,
  PlaybookItemsStore,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

import { programItemsData } from './data';

export const FETCH_PLAYBOOK_ERRORS = {
  ERROR_NO_PLAYBOOK_ITEMS: 'No playbook items found',
};

// Note: 1st pageFold is for Introduction poster
const PAGE_FOLD_OFFSET = 2;

/*
 * A Simple Search Implementation for the Partner Playbook Items
 */
export const handleSearch = (
  query: string,
  programItemsData: PlaybookItemsStore,
): PlaybookItems => {
  const filteredData = [] as PlaybookItems;
  const lowerCaseQuery = query.toLowerCase();
  programItemsData.forEach(({ sectionKey, sectionItem }, sectionIndex) => {
    // Count of matches within the section
    let sectionMatchCount = 0;

    const matchedFolders: Array<ProgramFolder> = [];
    sectionItem.folders.forEach(({ header, items }) => {
      const matchedFolder = { header: null, items: [] } as ProgramFolder;

      // Returns case-insensitive matches in title + description
      let matchedItems = items.filter((item) => {
        if (
          item.title.toLowerCase().includes(lowerCaseQuery) ||
          item.description.toLowerCase().includes(lowerCaseQuery)
        ) {
          return true;
        }

        return false;
      });

      if (header !== null) {
        // overwrite with total count if query found in header
        if (
          header.title.toLowerCase().includes(lowerCaseQuery) ||
          header.description.toLowerCase().includes(lowerCaseQuery)
        ) {
          matchedItems = items;
        }

        // Update count of matches within the folder
        matchedFolder.header = {
          ...header,
          count: matchedItems.length,
        };
      }

      // Update total section match count
      sectionMatchCount += matchedItems.length;

      // patch folder items with id -
      matchedFolder.items = matchedItems.map((item) => ({
        ...item,
        id: `${item.download_url}#${sectionItem.header.hash}`,
      }));

      if (matchedItems.length > 0) {
        matchedFolders.push(matchedFolder);
      }
    });

    filteredData.push({
      sectionKey,
      sectionItem: {
        header: {
          ...sectionItem.header,
          count: sectionMatchCount,
          pageFold: sectionIndex + PAGE_FOLD_OFFSET,
        },
        folders: matchedFolders,
      },
    });
  });

  return filteredData;
};

export const fetchPlaybookItems = async (
  params: PlaybookFiltersType,
): Promise<FetchPlaybookItemsResponse> => {
  const { query = '' } = params;
  const filteredData = handleSearch(query, programItemsData);
  // Note: a small delay for loader to be visible for better UX
  await delay(1000);
  if (!filteredData)
    return {
      status_code: 200,
      success: false,
      errors: [FETCH_PLAYBOOK_ERRORS.ERROR_NO_PLAYBOOK_ITEMS],
    };

  return {
    data: filteredData,
    status_code: 200,
    success: true,
  };
};
