import React, { useContext } from 'react';

import {
  CouponCard,
  CouponPreviewSection,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponPreview';
import { useParams } from 'react-router-dom';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';
import { connect } from 'react-redux';
import { Box, Heading, EyeIcon } from '@razorpay/blade/components';
import couponPreviewMapping from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponPreview/CouponPreviewMapping';
import { ModalContextValue } from 'merchant/views/MagicCheckout/CouponEngine/types';

const CouponPreview = ({ isRcodEnabled }) => {
  const params = useParams();
  const widgetsData: ModalContextValue = useContext(ModalContext);

  const couponSummarySections = () => {
    return Object.entries(couponPreviewMapping(widgetsData, params?.couponName, isRcodEnabled)).map(
      ([key, data]) => (
        <CouponPreviewSection
          key={key}
          couponPreviewSectionTitle={data.title}
          couponPreviewSectionList={data.list
            .filter((item) => item.condition())
            .map((item) => item.text)}
        />
      ),
    );
  };

  return (
    <Box display="flex" padding="spacing.7" flexDirection="column" gap="spacing.6">
      <Box display="flex" gap="spacing.4" alignItems="center">
        <EyeIcon color="surface.icon.onSea.onSubtle" size="xlarge" />
        <Heading as="h4" color="surface.text.gray.subtle">
          Coupon Preview
        </Heading>
      </Box>
      <CouponCard />
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.5"
        marginLeft="spacing.4"
        marginTop="spacing.4"
      >
        {couponSummarySections()}
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  isRcodEnabled: state.magicCheckout.rcod,
});

export default connect(mapStateToProps, null)(CouponPreview);
