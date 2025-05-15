import { PosIcon, TransactionsIcon, WifiIcon } from '@razorpay/blade/components';

export interface FilterState {
  method?: string;
  deviceId?: string;
  channel?: string;
  storeId?: string[];
}

// TODO: This is a temporary function. To be removed once store transaction PR is merged.
export const TransformToTreeData = (data) => {
  const idMap = {};
  const root: any = [];

  data.forEach((item) => {
    const node = {
      label: item.name,
      value: item.store_id || item.group_id,
      children: [],
    };
    idMap[item.group_id] = node;
  });

  data.forEach((item) => {
    const node = idMap[item.group_id];
    const parent = item.parent_group_id ? idMap[item.parent_group_id] : null;
    if (parent) {
      parent.children.push(node);
    } else {
      root.push(node);
    }
  });

  return root;
};

export const ChannelIconMap = {
  online: WifiIcon,
  in_person: PosIcon,
  online_in_person: TransactionsIcon,
};

export const ChannelSourceMap = {
  online: 'Online',
  in_person: 'In Person',
  online_in_person: 'Online + In Person',
};

export const ValidFiltersWidgetTypes = ['payment_source_filter', 'hierarchy_level'];
