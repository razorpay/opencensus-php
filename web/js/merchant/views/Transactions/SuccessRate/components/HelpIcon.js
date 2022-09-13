import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';

export default function HelpIcon({ text = '' }) {
  return (
    <small className="help-content">
      <i className="i i-help-outline" />
      <Popover align="right">
        <PopoverBody>
          <div>{text}</div>
        </PopoverBody>
      </Popover>
    </small>
  );
}
