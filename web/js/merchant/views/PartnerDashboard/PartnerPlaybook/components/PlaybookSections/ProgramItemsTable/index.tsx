import React, { useMemo } from 'react';
import { Text } from '@razorpay/blade/components';
import styled from 'styled-components';

import DataTable from 'common/ui/Table/DataTable';
import { trackPageSectionCtaClicked } from 'merchant/views/PartnerDashboard/PartnerPlaybook/analytics';
import {
  ProgramFolder,
  ProgramItem,
  ProgramSection,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

import ItemActions from './ItemActions';
import ItemLogo from './ItemLogo';

// Note: custom styling needed as DataTable is not supported in Blade yet.
const StyledProgramItemsTable = styled.div(
  ({ theme }) => `
  .program-items-logo {
    min-width: 60px;
    vertical-align: middle;
  }
  .program-items-title {
    min-width: 245px;
    vertical-align: middle;
  }
  .program-items-description {
    min-width: 500px;
    vertical-align: middle;
  }
  .program-items-action {
    min-width: 170px;
    vertical-align: middle;
    text-align: center;
  }
  .table-responsive{
    .table{ 
      margin-bottom: 0px;
      tr {
        cursor: pointer;
        background-color: ${theme.colors.surface.background.gray.intense};
      }
      thead > tr > th{
        cursor: default;
        color: ${theme.colors.interactive.text.gray.normal};
        background-color: ${theme.colors.feedback.background.neutral.subtle};
        border-top: 0px;
        border-bottom: 0px;
      } 
    }
  }
`,
);

const title = {
  title: 'Title',
  columnClass: 'program-items-title',
  value: (item: ProgramItem) => <Text weight="semibold"> {item.title} </Text>,
};

const logo = {
  title: '',
  columnClass: 'program-items-logo',
  value: (item: ProgramItem) => <ItemLogo item={item} />,
};

const description = {
  title: 'Description',
  columnClass: 'program-items-description',
  value: (item: ProgramItem) => item.description,
};
interface ProgramItemsTableProps {
  items: Array<ProgramItem>;
  sectionHeader: ProgramSection['header'];
  header: ProgramFolder['header'];
  openPreview: (item: ProgramItem) => void;
}

const ProgramItemsTable = ({
  items,
  sectionHeader,
  header,
  openPreview,
}: ProgramItemsTableProps): JSX.Element => {
  const trackItemActionCta = (ctaClicked, item) => {
    trackPageSectionCtaClicked({
      section: sectionHeader.title,
      pageFold: sectionHeader.pageFold,
      folderName: header?.title || null,
      folderDescription: header?.description || null,
      title: item.title,
      description: item.description,
      ctaClicked,
    });
  };
  const itemsMap = useMemo(() => {
    const idToItem = {};
    items.forEach((item) => {
      idToItem[item.id] = item;
    });
    return idToItem;
  }, [items]);

  const openItemPreview = (item) => {
    openPreview(item);
  };

  const onRowClick = (id) => {
    const rowItem = itemsMap[id];
    trackItemActionCta('row item', rowItem);
    openItemPreview(rowItem);
  };
  const actions = {
    title: 'Actions',
    columnClass: 'program-items-action',
    value: (item: ProgramItem) => (
      <ItemActions
        trackItemActionCta={trackItemActionCta}
        openItemPreview={openItemPreview}
        item={item}
      />
    ),
  };
  return (
    <StyledProgramItemsTable>
      <DataTable
        title="Items"
        columns={[logo, title, description, actions]}
        items={items}
        onRowClick={onRowClick}
        hasMoreData={false}
      />
    </StyledProgramItemsTable>
  );
};

export default ProgramItemsTable;
