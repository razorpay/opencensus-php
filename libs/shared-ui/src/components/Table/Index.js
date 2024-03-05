import React from 'react';
import PlaceholderLoader from '../PlaceholderLoader';
import { EntityItemRow } from '../../containers';

const Table = ({
  rows,
  columns,
  className,
  showHeaders = true,
  limit,
  loading,
  progressLoader = false,
  tableStyle = null,
  isMobileResolution,
  mobileColumns,
  customMobileRow,
  onCellClick,
  onRowClick,
  isDisabled,
  user = {},
  luminateRowId,
  activeEntityId,
  activeSecEntityId,
}) => {
  const rowItems = [];
  const cols = isMobileResolution && mobileColumns ? mobileColumns : columns;
  if (progressLoader && loading) {
    limit = limit || 5;
    for (let cur = 0; cur < limit; cur++) {
      rowItems.push(
        <EntityItemRow
          key={cur}
          luminateRowId={luminateRowId}
          activeEntityId={activeEntityId}
          activeSecEntityId={activeSecEntityId}
        >
          {cols.map((column, index) => (
            <td className={column.columnClass ? column.columnClass : ''} key={index}>
              <PlaceholderLoader />
            </td>
          ))}
        </EntityItemRow>,
      );
    }
  } else if (rows.length) {
    let curRow = 0;
    rows.forEach((item, index) => {
      curRow++;
      if (curRow > limit) {
        return false;
      }
      /* 
        In case you have to create a custom view for mobile:
        then you can create a custom function `mobileRows` & pass as props
        from your parent component & take each item as a param. 
        Note: You can take reference from QR Code payment's tab
      */
      const row =
        isMobileResolution && customMobileRow ? (
          customMobileRow(item)
        ) : (
          <EntityItemRow
            onRowClick={onRowClick}
            isDisabled={isDisabled}
            key={`${item.id}_${index}`}
            id={item.id}
            rowClasses={item.rowClass}
            item={item}
            luminateRowId={luminateRowId}
            activeEntityId={activeEntityId}
            activeSecEntityId={activeSecEntityId}
          >
            {cols.map((column, colIndex) => (
              <td className={column.columnClass ? column.columnClass : ''} key={colIndex}>
                {column.value(item, onCellClick, { user })}
              </td>
            ))}
          </EntityItemRow>
        );
      return rowItems.push(row);
    });
  }

  return (
    <div className="table-responsive">
      <table className={`table table-hover ${className}`} style={tableStyle}>
        {showHeaders ? (
          <thead>
            <tr>
              {cols.map((column, index) => (
                <th className={column.columnClass} key={index}>
                  {typeof column.title === 'function' ? column.title() : column.title}
                </th>
              ))}
            </tr>
          </thead>
        ) : null}
        {rows ? <tbody>{rowItems}</tbody> : null}
      </table>
    </div>
  );
};

export default Table;
