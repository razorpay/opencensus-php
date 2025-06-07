import {
    SHOW_PARENT as RC_SHOW_PARENT,
    SHOW_ALL as RC_SHOW_ALL,
    SHOW_CHILD as RC_SHOW_CHILD,
} from 'rc-tree-select';
import { TextProps } from '@razorpay/blade/components';

export type TreeNode = {
    label: string;
    value: string;
    children?: TreeNode[];
};
  
export enum ShowCheckedStrategy {
SHOW_PARENT = RC_SHOW_PARENT,
SHOW_ALL = RC_SHOW_ALL,
SHOW_CHILD = RC_SHOW_CHILD,
}
  
export interface TreeSelectProps {
treeData: TreeNode[];
value: string | string[];
onChange: (value: string | string[]) => void;
width?: string;
showCheckedStrategy?: ShowCheckedStrategy;
disabled?: boolean;
placeholder?: string;
maxTagCountToShow?: number;
maxTagTextLength?: number;
label?: string;
maxCount?: number;
labelColor?: TextProps<{ variant: 'body' }>['color'];
labelSize?: TextProps<{ variant: 'body' }>['size'];
showTooltip?: boolean;
tooltipText?: string;
showDisabledText?: boolean;
disabledText?: string;
showFooterText?: boolean;
footerText?: string;
}
