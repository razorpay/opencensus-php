import React, { Component } from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';

export default ({
  csvData,
  pngData,
  children,
  handleImageDownload,
  handleCSVDownload,
  handleClick,
}) => (
  <div className="more-options-button">
    <Dropdown className="dropdown-toggle">
      <DropdownTrigger>
        <button className="btn btn-default" onClick={handleClick}>
          <i className="i i-download" />
        </button>
      </DropdownTrigger>
      <DropdownContent>
        <div className="dropdown-menu">
          {children}
          {!!csvData && (
            <div className="option">
              <a
                href={csvData.url}
                download={csvData.name}
                onClick={handleCSVDownload}
              >
                Export CSV
              </a>
            </div>
          )}
          {!!pngData && (
            <div className="option">
              <a
                onClick={handleImageDownload}
                download={pngData.name}
                href={pngData.url}
              >
                Download Image
              </a>
            </div>
          )}
        </div>
      </DropdownContent>
    </Dropdown>
  </div>
);
