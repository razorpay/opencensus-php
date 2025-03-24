import React, { useMemo } from 'react';
import { SortableContainer } from 'react-sortable-hoc';

import { Box, Text } from '@razorpay/blade/components';
import { SocialMediaHandle, SocialMediaHandles } from 'merchant/reducers/paymentPages/types';
import SingleSocialHandle from './SingleSocialHandle';

import { SocialHandleListProps } from '../types';
import { getSocialHandleSrc } from '../utils';

const SortableList = SortableContainer<{
  items: SocialMediaHandles;
  isMobile: boolean;
  editSocialHandle: (handle: string) => void;
  showDeleteConfirmation: (handle: string) => void;
}>(({ items, isMobile, editSocialHandle, showDeleteConfirmation }) => (
  <Box marginTop="spacing.4">
    {items.map((item: SocialMediaHandle, index: number) => (
      <SingleSocialHandle
        index={index}
        key={item?.platform}
        logo_url={item?.logo_url ?? getSocialHandleSrc(item?.platform)}
        id={item?.position?.toString() ?? ''}
        platform={item?.platform}
        editSocialHandle={(val: string) => editSocialHandle(val)}
        showDeleteConfirmation={(val: string) => showDeleteConfirmation(val)}
      />
    ))}
  </Box>
));

const SocialHandleList: React.FC<SocialHandleListProps> = ({
  socialHandleList,
  isMobile,
  reorderSocialHandle,
  editSocialHandle,
  showDeleteConfirmation,
}) => {
  if (!socialHandleList?.length) return null;

  const sortedsocialHandleList = useMemo(
    () =>
      [...socialHandleList].sort((a, b) => {
        const positionA = a.position ?? 0;
        const positionB = b.position ?? 0;
        return positionA - positionB;
      }),
    [socialHandleList],
  );

  const handleSortEnd = ({ oldIndex, newIndex }: { oldIndex: number; newIndex: number }) => {
    const updatedList = Array.from(sortedsocialHandleList);
    const [movedItem] = updatedList.splice(oldIndex, 1);
    updatedList.splice(newIndex, 0, movedItem);
    const newOrder = updatedList.map((item, idx) => ({ ...item, position: idx }));
    reorderSocialHandle(newOrder);
  };

  return (
    <Box
      marginTop="spacing.7"
      borderTopStyle="dotted"
      paddingTop="spacing.7"
      borderTopColor="surface.border.gray.normal"
      borderTopWidth="thin"
      paddingBottom={isMobile ? 'spacing.11' : 'spacing.0'}
    >
      <Text color="surface.text.gray.normal" size="large" variant="body" weight="semibold">
      Your added handles
      </Text>
      <SortableList
        items={sortedsocialHandleList}
        onSortEnd={handleSortEnd}
        useDragHandle
        isMobile={isMobile}
        editSocialHandle={editSocialHandle}
        showDeleteConfirmation={showDeleteConfirmation}
        helperClass="sortableHelper"
      />
    </Box>
  );
};

export default SocialHandleList;
