import React from 'react';
import './rc_tree_select.min.css';
import RcTreeSelect from 'rc-tree-select';
import {
  Box,
  Text,
  XCircleIcon,
} from '@razorpay/blade/components';
import { TreeSelectProps, ShowCheckedStrategy } from './types';
import { treeSelectFilterTreeNode, NoResultFound, renderSwitcherIcon } from './utils';

const TreeSelect: React.FC<TreeSelectProps> = ({
  treeData,
  value,
  onChange,
  width = '100%',
  showCheckedStrategy = ShowCheckedStrategy.SHOW_PARENT,
  disabled = false,
  placeholder = 'Please select',
  maxTagCountToShow = 5,
  maxTagTextLength = 20,
  label = 'Select Items',
}) => {
  return (
    <Box width="100%">
      <Text
        variant="body"
        size="medium"
        weight="semibold"
        color="surface.text.gray.normal"
        marginBottom="spacing.3"
      >
        {label}
      </Text>
      <RcTreeSelect
        treeData={treeData}
        value={value}
        onChange={onChange}
        style={{ width }}
        treeLine={false}
        treeCheckable={true}
        showCheckedStrategy={showCheckedStrategy}
        treeIcon={false}
        showTreeIcon={false}
        switcherIcon={renderSwitcherIcon}
        multiple={true}
        disabled={disabled}
        allowClear={true}
        clearIcon={<XCircleIcon color="surface.icon.gray.muted" />}
        notFoundContent={<NoResultFound />}
        maxTagCount={maxTagCountToShow}
        maxTagTextLength={maxTagTextLength}
        placeholder={placeholder}
        filterTreeNode={treeSelectFilterTreeNode}
        virtual={true}
      />
    </Box>
  );
};

export default TreeSelect;
