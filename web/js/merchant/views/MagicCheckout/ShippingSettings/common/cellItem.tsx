import React from 'react';
import {
  Box,
  EditComposeIcon,
  IconButton,
  TrashIcon,
  DownloadIcon,
} from '@razorpay/blade/components';
import {
  ShippingMethod,
  ShippingProfile,
  Zone,
  Actions,
} from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { ColumnDef } from 'merchant/views/MagicCheckout/common/components/Datatable/types';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import ProfileSlider from './ProfileSlider';
import { gramsToKilos } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/helpers';

export const Profile = {
  title: 'Profile',
  value: (item: ShippingProfile) => {
    return item?.name ? <ProfileSlider profile={item} /> : '-';
  },
};

export const Zones = {
  title: 'Zones',
  value: (item: ShippingProfile) => item?.zones?.map((z) => z.name).join(', ') || '-',
};

export const ShippingMethods = {
  title: 'Shipping Methods',
  value: (item: ShippingProfile) => {
    const methods: string[] = [];
    item.zones?.forEach((z) => {
      if (z?.shipping_methods) {
        methods.push(...(z.shipping_methods.map((method) => method.name) || []));
      }
    });
    return methods.length ? methods.join(', ') : '-';
  },
};

export const CategoryName = {
  title: 'Category Name',
  value: (item: ShippingProfile) => item?.name || '-',
};

export const ProductCount = {
  title: 'Product Count',
  value: (item: ShippingProfile) => item?.item_count || '-',
};

export const Action = {
  title: 'Action',
  value: (item) => item?.range || '-',
};

export const ZoneName = {
  title: 'Name',
  value: (item: Zone) => item?.name || '-',
};

export const ZipCodes = {
  title: 'Count',
  value: (item: Zone) => (item?.location_count ? `${item?.location_count} zipcodes` : '-'),
};

export const COD = {
  title: 'COD',
  value: (item: ShippingMethod) => (item?.allow_cod ? 'Yes' : 'No' || '-'),
};

export const SubscribeRate = {
  title: 'Subscribe Rate',
  value: (item: ShippingMethod) => {
    const rateInfo =
      (item?.attribute_rules?.customer_tags &&
        Object.entries(item?.attribute_rules?.customer_tags)?.map(([tag, fee]) => (
          <>
            <span>
              {getFormattedAmountNew(fee, true)} For {tag}
            </span>{' '}
            <br />
          </>
        ))) ||
      '-';
    return <div>{rateInfo}</div>;
  },
};

export const Slab = {
  title: 'Slab',
  value: (item: ShippingMethod) => {
    if (item.fee_rules) {
      let text = '';
      const { amount, weight } = item.fee_rules;
      if (amount) {
        text = `If amount is between ${getFormattedAmountNew(
          amount.gte,
          true,
        )} and ${getFormattedAmountNew(amount.lt, true)}`;
      }
      if (weight) {
        text += `${amount ? ' and' : 'If'} weight is between ${gramsToKilos(
          weight.gte,
        )} kg and ${gramsToKilos(weight.lt)} kg`;
      }
      return text;
    } else return '-';
  },
};

export const ETD = {
  title: 'Estimated Delivery',
  value: (item: ShippingMethod) => {
    if (item.estimated_delivery_details) {
      const { min_timeframe, max_timeframe, unit } = item.estimated_delivery_details;

      return `${min_timeframe} - ${max_timeframe} ${unit}`;
    }

    return item?.etd || '-';
  },
};

export const actions = ({
  handleDeleteClick,
  handleEditClick,
  downloadable,
}: Actions): ColumnDef<any> => ({
  title: 'Action',
  value: (item) => {
    return item && !item.is_default ? (
      <Box display="flex" alignItems="center" justifyContent="flex-end" gap="spacing.4">
        {downloadable && downloadable.showDownloadIcon(item) && (
          <IconButton
            accessibilityLabel="download"
            onClick={() => downloadable?.handleDownloadClick(item)}
            icon={() => <DownloadIcon size="medium" color="interactive.icon.primary.normal" />}
          />
        )}
        {handleEditClick && (
          <IconButton
            accessibilityLabel="edit"
            onClick={handleEditClick(item)}
            icon={() => <EditComposeIcon size="medium" color="interactive.icon.primary.normal" />}
          />
        )}
        {handleDeleteClick && (
          <Box>
            <IconButton
              accessibilityLabel="delete"
              onClick={handleDeleteClick(item)}
              icon={() => <TrashIcon size="medium" color="feedback.icon.negative.intense" />}
            />
          </Box>
        )}
      </Box>
    ) : null;
  },
});
