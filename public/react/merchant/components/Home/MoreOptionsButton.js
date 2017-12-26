import React, { Component } from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';

export default ({ csvData }) => (
  <div className="more-options-button">
    <Dropdown className="dropdown-toggle">
      <DropdownTrigger>
        <button className="btn btn-default">
          <i class="fa fa-ellipsis-h" />
        </button>
      </DropdownTrigger>
      <DropdownContent>
        <div className="dropdown-menu">
          {!!csvData && (
            <div className="option">
              <a href={csvData.url} download={csvData.name}>
                Export as CSV
              </a>
            </div>
          )}
          <div className="option">Download as PNG</div>
        </div>
      </DropdownContent>
    </Dropdown>
  </div>
);
