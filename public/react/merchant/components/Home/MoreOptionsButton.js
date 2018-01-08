import React, { Component } from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';

export default ({ csvData, pngData, children, onImageExport }) => (
  <div className="more-options-button">
    <Dropdown className="dropdown-toggle">
      <DropdownTrigger>
        <button className="btn btn-default">
          <i class="fa fa-ellipsis-h" />
        </button>
      </DropdownTrigger>
      <DropdownContent>
        <div className="dropdown-menu">
          {children}
          {!!csvData && (
            <div className="option">
              <a href={csvData.url} download={csvData.name}>
                Export as CSV
              </a>
            </div>
          )}
          {!!pngData && (
            <div className="option">
              <a
                onClick={onImageExport}
                download={pngData.name}
                href={pngData.url}
              >
                Export as Image
              </a>
            </div>
          )}
        </div>
      </DropdownContent>
    </Dropdown>
  </div>
);
