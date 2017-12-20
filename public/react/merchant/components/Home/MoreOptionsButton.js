import React, { Component } from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';

export default ({ children }) => (
  <div className="more-options-button">
    <Dropdown className="dropdown-toggle">
      <DropdownTrigger>
        <button className="btn btn-default">
          <i class="fa fa-ellipsis-h" />
        </button>
      </DropdownTrigger>
      <DropdownContent>
        <div className="dropdown-menu">
          <div className="option">View detailed report</div>
          <div className="option">Export as CSV</div>
          <div className="option">Download as PNG</div>
        </div>
      </DropdownContent>
    </Dropdown>
  </div>
);
