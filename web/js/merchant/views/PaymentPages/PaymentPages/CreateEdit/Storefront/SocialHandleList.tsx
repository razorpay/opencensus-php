import React, { useMemo } from 'react';
import { SortableContainer } from 'react-sortable-hoc';

import { Box, Text } from '@razorpay/blade/components';
import { SocialMediaHandle, SocialMediaHandles } from 'merchant/reducers/paymentPages/types';
import SingleSocialHandle from './SingleSocialHandle';
import { SocialHandleListProps } from './types';
import { getSocialHandleSrc } from './utils';

const SortableList = SortableContainer<{
  items: SocialMediaHandles;
  isMobile: boolean;
  handleEdit: (handle: string) => void;
  handleDelete: (handle: string) => void;
}>(({ items, isMobile, handleEdit, handleDelete }) => (
  <Box marginTop="spacing.4">
    {items.map((item: SocialMediaHandle, index: number) => (
      <SingleSocialHandle
        index={index}
        key={item?.platform}
        isMobile={isMobile}
        logo_url={item?.logo_url ?? getSocialHandleSrc(item?.platform)}
        id={item?.position?.toString() ?? ''}
        platform={item?.platform}
        handleEdit={(val: string) => handleEdit(val)}
        handleDelete={(val: string) => handleDelete(val)}
      />
    ))}
  </Box>
));

const SocialHandleList: React.FC<SocialHandleListProps> = ({
  socialHandleList,
  isMobile,
  reorderSocialHandle,
  handleEditClick,
  handleDeleteClick,
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
        Your added banner images
      </Text>
      <SortableList
        items={sortedsocialHandleList}
        onSortEnd={handleSortEnd}
        useDragHandle
        isMobile={isMobile}
        handleEdit={handleEditClick}
        handleDelete={handleDeleteClick}
        helperClass="sortableHelper"
      />
    </Box>
  );
};

export default SocialHandleList;
