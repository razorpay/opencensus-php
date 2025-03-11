import React, { useMemo } from 'react';
import { SortableContainer } from 'react-sortable-hoc';

import { Box, Text } from '@razorpay/blade/components';
import SingleBanner from './SingleBanner';

import { IBannerImage } from 'merchant/reducers/paymentPages/types';

interface IBannersListProps {
  bannerData: IBannerImage[];
  onReorder: (banner: IBannerImage[]) => void;
  onToggleEnabled: (val: boolean, banner: IBannerImage) => void;
  handleReplaceImage: (banner: IBannerImage) => void;
  handleDeleteImage: (banner: IBannerImage) => void;
  handleEditImage: (banner: IBannerImage) => void;
  isMobile: boolean;
}

const SortableList = SortableContainer<{
  items: IBannerImage[];
  isMobile: boolean;
  onToggleEnabled: IBannersListProps['onToggleEnabled'];
  handleReplaceImage: IBannersListProps['handleReplaceImage'];
  handleDeleteImage: IBannersListProps['handleDeleteImage'];
  handleEditImage: IBannersListProps['handleEditImage'];
}>(
  ({
    items,
    isMobile,
    onToggleEnabled,
    handleReplaceImage,
    handleDeleteImage,
    handleEditImage,
  }) => (
    <Box marginTop="spacing.4">
      {items.map((banner: IBannerImage, index: number) => (
        <SingleBanner
          key={banner?.position}
          index={index}
          id={banner?.position?.toString()}
          src={banner?.cropped}
          enabled={banner?.enabled}
          isSwitchEnabled={banner?.isSwitchEnabled ?? true}
          isMobile={isMobile}
          onToggleEnabled={(val) => onToggleEnabled(val, banner)}
          handleReplaceImage={() => handleReplaceImage(banner)}
          handleDeleteImage={() => handleDeleteImage(banner)}
          handleEditImage={() => handleEditImage(banner)}
        />
      ))}
    </Box>
  ),
);

const BannersList: React.FC<IBannersListProps> = ({
  bannerData,
  onReorder,
  onToggleEnabled,
  handleReplaceImage,
  handleDeleteImage,
  isMobile,
  handleEditImage,
}) => {
  if (!bannerData?.length) return null;

  const sortedBannerData = useMemo(
    () => [...bannerData].sort((a, b) => a.position - b.position),
    [bannerData],
  );

  const handleSortEnd = ({ oldIndex, newIndex }: { oldIndex: number; newIndex: number }) => {
    const updatedList = Array.from(sortedBannerData);
    const [movedItem] = updatedList.splice(oldIndex, 1);
    updatedList.splice(newIndex, 0, movedItem);
    const newOrder = updatedList.map((item, idx) => ({ ...item, position: idx }));
    onReorder(newOrder);
  };

  return (
    <Box
      marginTop="24px"
      borderTopStyle="dotted"
      paddingTop="24px"
      borderTopColor="surface.border.gray.normal"
      borderTopWidth="thin"
    >
      <Text color="surface.text.gray.normal" size="large" variant="body" weight="semibold">
        Your added banner images
      </Text>
      <SortableList
        items={sortedBannerData}
        onSortEnd={handleSortEnd}
        useDragHandle
        isMobile={isMobile}
        onToggleEnabled={onToggleEnabled}
        handleReplaceImage={handleReplaceImage}
        handleDeleteImage={handleDeleteImage}
        helperClass="sortableHelper"
        handleEditImage={handleEditImage}
      />
    </Box>
  );
};

export default BannersList;
