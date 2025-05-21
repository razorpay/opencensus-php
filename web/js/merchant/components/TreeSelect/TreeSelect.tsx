import React from 'react';
import './rc_tree_select.min.css';
import RcTreeSelect from 'rc-tree-select';
import {
  Box,
  Text,
  XCircleIcon,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon
} from '@razorpay/blade/components';
import { TreeSelectProps, ShowCheckedStrategy } from './types';
import { treeSelectFilterTreeNode, NoResultFound, renderSwitcherIcon } from './utils';

const TreeSelect: React.FC<TreeSelectProps> = ({
  treeData,
  value,
  onChange,
  width = '100%',
  showCheckedStrategy = ShowCheckedStrategy.SHOW_CHILD,
  disabled = false,
  placeholder = 'Please select',
  maxTagCountToShow = 5,
  maxTagTextLength = 20,
  label = 'Select Items',
  maxCount = 10,
  labelColor = 'surface.text.gray.normal',
  labelSize = 'medium',
  showTooltip = false,
  tooltipText = "Choose a hierarchy level to view your data",
  showDisabledText = false,
  disabledText = "This feature is currently disabled"
}) => {
  return (
    <Box width="100%">
      <Box display="flex" flexDirection="row">
      <Text
        variant="body"
        size={labelSize}
        weight="semibold"
        color={labelColor}
        marginBottom="spacing.3"
        marginRight="spacing.2"
      >
        {label}
      </Text>
      {showTooltip ? (
        <Tooltip content={tooltipText} placement="top">
          <TooltipInteractiveWrapper>
            <InfoIcon color="surface.icon.gray.muted" size="small" />
          </TooltipInteractiveWrapper>
        </Tooltip>
      ) : null}
      </Box>
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
        maxCount={maxCount}
      />
      {disabled && showDisabledText ? (
      <Text variant="caption" size="small" color="surface.text.gray.muted" marginTop="spacing.2">
        {disabledText}
      </Text>
      ) : null}
    </Box>
  );
};

export default TreeSelect;
