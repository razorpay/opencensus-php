export const equalizeRowElementHeights = (
  pricingPlans: any[],
  columnRefs: React.RefObject<(HTMLDivElement | null)[]>,
) => {
  if (!pricingPlans.length || !columnRefs.current) return;

  // Convert HTMLCollection of row children to array and get their heights
  // columnRefs is two-dimensional array of columns. each column is an array of row DOM nodes.
  // We are getting the height of each row DOM node of each column.
  const rowHeights = columnRefs.current.map((row) => {
    const childrenArray = row ? Array.from(row.children) : []; // Convert HTMLCollection to array
    return childrenArray.map((cell) => (cell as HTMLElement).offsetHeight); // Get height of each cell in the row
  });
  // Reference:
  // const rowHeights1 = [
  //   [height1, height2, height3], // heights of each row DOM node of first column
  //   [height1, height2, height3], // heights of each row DOM node of second column
  // ];

  // get the maximum height for each row item across columns
  const maxRowHeights = rowHeights[0].map((_, index) => {
    return Math.max(...rowHeights.map((row) => row[index] || 0));
  });
  // maxRowHeights example value: [maxHeightForRow1, maxHeightForRow2, ...]

  // Set each row item's height to the maximum of it's row across columns
  columnRefs.current.forEach((row) => {
    if (row) {
      const childrenArray = Array.from(row.children);
      childrenArray.forEach((cell, cellIndex) => {
        (cell as HTMLElement).style.height = `${maxRowHeights[cellIndex]}px`;
      });
    }
  });
};
