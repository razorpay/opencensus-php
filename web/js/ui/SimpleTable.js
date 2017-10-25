import React from 'react';

/*
  Description: View only table where row can be clickable if item clicked.
  Note: don't pass arrow fn. as onClick as item's scope has to be bound
  Example: Check entity-resources.js
  Props:
    Fields: Array of array,
    Items: Array of objects where keys depend upon above fields
    onClick: pass generic function which would receive `this` as item when clicked on item. (Don't use arrow-fn for this)
*/
export default ({ fields, items, onClick, bordered }) => {
  let trClass = onClick ? 'tr clickable' : 'tr';
  let tableClass = 'table table-striped';
  if (bordered) {
    tableClass += ' table-bordered';
  }

  return (
    <div class="table-container">
      {items && items.length ? (
        <div class={tableClass}>
          <div class="tr thead">
            {fields.map((field, index) => (
              <div class="th" key={index}>
                {field[0]}
              </div>
            ))}
          </div>
          {items.map((item, index) => {
            return (
              <div
                class={trClass}
                key={index}
                onClick={onClick && item::onClick}
              >
                {fields.map((field, index) => (
                  <div class="td" key={index}>
                    {field[1](item)}
                  </div>
                ))}
              </div>
            );
          })}
        </div>
      ) : (
        <div class="table-empty" />
      )}
    </div>
  );
};
