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
          <svg xmlns="http://www.w3.org/2000/svg">
            <path d="M2 0C.9 0 0 .9 0 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM8 0C6.9 0 6 .9 6 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
          </svg>
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
