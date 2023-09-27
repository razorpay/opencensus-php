import React from 'react';
import { Box, EditComposeIcon, IconButton, TrashIcon } from '@razorpay/blade/components';
import {
  ShippingMethod,
  ShippingProfile,
  Zone,
} from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { ColumnDef } from 'merchant/views/MagicCheckout/common/components/Datatable/types';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import ProfileSlider from './ProfileSlider';

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
    item.zones?.map((z) => {
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
        text += `${amount ? ' and' : 'If'} weight is between ${weight.gte} kg and ${weight.lt} kg`;
      }
      return text;
    } else return '-';
  },
};

export const ETD = {
  title: 'Estimated Delivery',
  value: (item: ShippingMethod) => item?.etd || '-',
};

export const actions = ({ handleDeleteClick, handleEditClick }): ColumnDef<any> => ({
  title: 'Action',
  value: (item) => {
    return item && !item.is_default ? (
      <Box display="flex" alignItems="center" justifyContent="flex-end" gap="spacing.4">
        {handleEditClick && (
          <IconButton
            accessibilityLabel="edit"
            onClick={handleEditClick(item)}
            icon={() => <EditComposeIcon size="medium" color="action.icon.link.active" />}
          />
        )}
        {handleDeleteClick && (
          <Box>
            <IconButton
              accessibilityLabel="delete"
              onClick={handleDeleteClick(item)}
              icon={() => <TrashIcon size="medium" color="feedback.icon.negative.lowContrast" />}
            />
          </Box>
        )}
      </Box>
    ) : null;
  },
});
