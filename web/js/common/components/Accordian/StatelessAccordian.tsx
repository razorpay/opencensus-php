import React, { ReactElement } from 'react';
import toArray from 'common/services/children/to-array';
import { Root } from './Styled';
import { PanelPropsT } from './Panel';

export interface StatelessAccordionPropsT {
  /**
   * Determines the behaviour of the accordian by controlling how many
   * panels should be open at once */
  accordian?: boolean;
  /** Tab Panles */
  children: React.ReactNode;
  /** Array of keys which corresponds to open panels */
  expanded: React.Key[];
  /** Children panels will be disabled from toggling if set ture */
  disabled?: boolean;
  /** handle whenever a panel is toggeled */
  onChange: (key: React.Key, expanded: React.Key[]) => void;
}

const StatelessAccordian: React.FC<StatelessAccordionPropsT> = ({
  children,
  accordian,
  expanded,
  disabled,
  onChange,
}) => {
  return (
    <Root>
      {toArray(children).map((child: ReactElement<PanelPropsT>, index) => {
        const key = child.key || String(index);
        return React.cloneElement(child, {
          disabled: disabled || child.props.disabled,
          expanded: expanded.includes(key),
          onChange:
            onChange && typeof onChange === 'function'
              ? () => {
                  let nextExpanded: React.Key[] = [];
                  if (accordian) {
                    if (expanded.includes(key)) {
                      nextExpanded = [];
                    } else {
                      nextExpanded = [key];
                    }
                  } else if (expanded.includes(key)) {
                    nextExpanded = expanded.filter((k) => k !== key);
                  } else {
                    nextExpanded = [...expanded, key];
                  }
                  onChange(key, nextExpanded);
                }
              : onChange,
        });
      })}
    </Root>
  );
};

export default StatelessAccordian;
