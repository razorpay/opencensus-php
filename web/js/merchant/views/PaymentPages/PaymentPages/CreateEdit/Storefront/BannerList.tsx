import React, { useMemo } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import SingleBanner from './SingleBanner';
import { IBannerImage } from 'merchant/reducers/paymentPages/types';

interface IBannersListProps {
  bannerData: IBannerImage[];
  onReorder: (banner: IBannerImage) => void;
  onToggleEnabled: (val: boolean, banner: IBannerImage) => void;
  handleReplaceImage: (banner: IBannerImage) => void;
  handleDeleteImage: (banner: IBannerImage) => void;
  isMobile: boolean;
}

const BannersList: React.FC<IBannersListProps> = ({
  bannerData,
  onReorder,
  onToggleEnabled,
  handleDeleteImage,
  handleReplaceImage,
  isMobile,
}) => {
  if (!bannerData?.length) return null;

  const sortedBannerData = useMemo(
    () => [...bannerData].sort((a, b) => a.position - b.position),
    [bannerData],
  );

  const handleReorder = (banner: IBannerImage) => onReorder(banner);
  const handleToggle = (val: boolean, banner: IBannerImage) => onToggleEnabled(val, banner);
  const handleReplace = (banner: IBannerImage) => handleReplaceImage(banner);
  const handleDelete = (banner: IBannerImage) => handleDeleteImage(banner);

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
      <Box marginTop="spacing.4">
        {sortedBannerData.map((banner) => (
          <SingleBanner
            key={banner.croppedSrc}
            src={banner.croppedSrc}
            enabled={banner.enabled}
            id={banner.position.toString()}
            onReorder={() => handleReorder(banner)}
            onToggleEnabled={(val) => handleToggle(val, banner)}
            handleDeleteImage={() => handleDelete(banner)}
            handleReplaceImage={() => handleReplace(banner)}
            isMobile={isMobile}
          />
        ))}
      </Box>
    </Box>
  );
};

export default BannersList;
